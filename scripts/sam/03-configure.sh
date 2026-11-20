#!/usr/bin/env bash

set -euo pipefail

# Prepare a repeatable production configuration without changing containers,
# databases, volumes, or credentials that already exist.

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

# shellcheck source=lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"
# shellcheck source=lib/safety.sh
source "${SCRIPT_DIR}/lib/safety.sh"
# shellcheck source=lib/docker.sh
source "${SCRIPT_DIR}/lib/docker.sh"
# shellcheck source=lib/verification.sh
source "${SCRIPT_DIR}/lib/verification.sh"

SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"
SAM_CHECK_ONLY=false
SAM_TEMPLATE_FILE="${SAM_ROOT}/.env.production.example"

sam_config_usage() {
    cat <<'USAGE'
Usage:
  scripts/sam/03-configure.sh [--destination DIR]
  scripts/sam/03-configure.sh --check [--destination DIR]
  scripts/sam/03-configure.sh --help

Prepare LexTrack production configuration without starting Docker services.

Prerequisites:
  Ubuntu 24.04 LTS or 26.04 LTS on AMD64/x86_64, Bash, OpenSSL, and Docker
  Engine with Docker Compose 2 or newer. Docker Compose config validation is
  read-only and does not pull images or start containers.

Options:
  --destination DIR  Installation/configuration directory. Defaults to
                     LEXTRACK_INSTALL_DIR or /opt/lextrack.
  --check            Validate an existing configuration only. Does not create
                     directories, files, or secrets.
  --help             Show this help text.

Managed paths:
  DIR/.env.production
  DIR/secrets/app_key
  DIR/secrets/db_password
  DIR/secrets/mysql_root_password

The script never overwrites an existing configuration or secret. APP_URL and
external service credentials must be supplied by the operator; no domain,
password, OAuth credential, or provider value is assumed.
USAGE
}

sam_config_error_usage() {
    sam_error "$1"
    sam_error "Use --help for usage."
    return 1
}

sam_parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --check)
                SAM_CHECK_ONLY=true
                shift
                ;;
            --destination)
                [[ $# -ge 2 ]] || sam_config_error_usage "--destination requires a directory"
                SAM_INSTALL_DIR=$2
                shift 2
                ;;
            --destination=*)
                SAM_INSTALL_DIR=${1#*=}
                [[ -n "$SAM_INSTALL_DIR" ]] || sam_config_error_usage "--destination requires a directory"
                shift
                ;;
            --help|-h)
                sam_config_usage
                exit 0
                ;;
            *)
                sam_config_error_usage "Unknown option: $1"
                ;;
        esac
    done
}

sam_prepare_destination() {
    local destination=$1
    local parent_directory

    sam_reject_unsafe_destination "$destination" || return 1

    if [[ -e "$destination" && ! -d "$destination" ]]; then
        sam_error "Configuration destination is not a directory: ${destination}"
        return 1
    fi

    [[ ! -L "$destination" ]] || {
        sam_error "Configuration destination must not be a symbolic link: ${destination}"
        return 1
    }

    if [[ ! -e "$destination" ]]; then
        parent_directory=$(dirname -- "$destination")
        sam_require_directory_access "$parent_directory" || return 1
        mkdir -p --mode=700 -- "$destination"
    fi

    chmod 700 -- "$destination"
    sam_require_directory_access "$destination"
}

sam_prepare_secrets_directory() {
    local secrets_directory=$1

    if [[ -e "$secrets_directory" && ! -d "$secrets_directory" ]]; then
        sam_error "Secrets path is not a directory: ${secrets_directory}"
        return 1
    fi
    [[ ! -L "$secrets_directory" ]] || {
        sam_error "Secrets directory must not be a symbolic link: ${secrets_directory}"
        return 1
    }
    if [[ ! -e "$secrets_directory" ]]; then
        mkdir -p --mode=700 -- "$secrets_directory"
    fi

    chmod 700 -- "$secrets_directory"
    sam_require_directory_access "$secrets_directory"
}

sam_create_secret_if_missing() {
    local secret_file=$1
    local generator=$2

    if [[ -e "$secret_file" ]]; then
        [[ ! -L "$secret_file" ]] || {
            sam_error "Secret must not be a symbolic link: ${secret_file}"
            return 1
        }
        sam_verify_secret_file_permissions "$secret_file"
        return 0
    fi

    umask 077
    if [[ "$generator" == "app-key" ]]; then
        (set -C; printf 'base64:%s\n' "$(openssl rand -base64 32)" > "$secret_file") || {
            sam_error "Unable to create secret; it may have appeared concurrently: ${secret_file}"
            return 1
        }
    else
        (set -C; openssl rand -hex 32 > "$secret_file") || {
            sam_error "Unable to create secret; it may have appeared concurrently: ${secret_file}"
            return 1
        }
    fi
    chmod 600 -- "$secret_file"
    sam_verify_secret_file_permissions "$secret_file"
}

sam_validate_compose_without_starting() {
    local env_file=$1

    sam_validate_project_name "$SAM_PRODUCTION_PROJECT_NAME" || return 1
    sam_validate_compose_file "$SAM_PRODUCTION_COMPOSE_FILE" || return 1
    sam_require_docker_compose_v2 || return 1
    docker compose \
        --project-name "$SAM_PRODUCTION_PROJECT_NAME" \
        --env-file "$env_file" \
        --file "$SAM_PRODUCTION_COMPOSE_FILE" \
        config --quiet
}

sam_configuration_paths() {
    SAM_ENV_FILE="${SAM_INSTALL_DIR}/.env.production"
    SAM_SECRETS_DIR="${SAM_INSTALL_DIR}/secrets"
    SAM_APP_KEY_FILE="${SAM_SECRETS_DIR}/app_key"
    SAM_DB_PASSWORD_FILE="${SAM_SECRETS_DIR}/db_password"
    SAM_MYSQL_ROOT_PASSWORD_FILE="${SAM_SECRETS_DIR}/mysql_root_password"
}

sam_create_configuration() {
    sam_prepare_destination "$SAM_INSTALL_DIR"
    sam_configuration_paths

    if [[ -e "$SAM_ENV_FILE" ]]; then
        [[ ! -L "$SAM_ENV_FILE" ]] || {
            sam_error "Production configuration must not be a symbolic link: ${SAM_ENV_FILE}"
            return 1
        }
        chmod 600 -- "$SAM_ENV_FILE"
    else
        (set -C; cat -- "$SAM_TEMPLATE_FILE" > "$SAM_ENV_FILE") || {
            sam_error "Unable to create production configuration; it may have appeared concurrently: ${SAM_ENV_FILE}"
            return 1
        }
        chmod 600 -- "$SAM_ENV_FILE"
        local escaped_secrets_directory
        escaped_secrets_directory=${SAM_SECRETS_DIR//\\/\\\\}
        escaped_secrets_directory=${escaped_secrets_directory//&/\\&}
        escaped_secrets_directory=${escaped_secrets_directory//|/\\|}
        sed -i "s|^LEXTRACK_SECRETS_DIR=.*|LEXTRACK_SECRETS_DIR=${escaped_secrets_directory}|" "$SAM_ENV_FILE"
        sam_log "Created production configuration template: ${SAM_ENV_FILE}"
    fi

    sam_prepare_secrets_directory "$SAM_SECRETS_DIR"
    sam_create_secret_if_missing "$SAM_APP_KEY_FILE" app-key
    sam_create_secret_if_missing "$SAM_DB_PASSWORD_FILE" database-password
    sam_create_secret_if_missing "$SAM_MYSQL_ROOT_PASSWORD_FILE" mysql-root-password
}

sam_check_configuration() {
    sam_reject_unsafe_destination "$SAM_INSTALL_DIR"
    sam_configuration_paths
    [[ ! -L "$SAM_INSTALL_DIR" ]] || {
        sam_error "Configuration destination must not be a symbolic link: ${SAM_INSTALL_DIR}"
        return 1
    }
    sam_require_directory_access "$SAM_INSTALL_DIR"
    sam_require_file "$SAM_ENV_FILE"
    sam_verify_secret_file_permissions "$SAM_ENV_FILE"
    [[ ! -L "$SAM_SECRETS_DIR" ]] || {
        sam_error "Secrets directory must not be a symbolic link: ${SAM_SECRETS_DIR}"
        return 1
    }
    sam_require_directory_access "$SAM_SECRETS_DIR"
    sam_verify_secret_file_permissions "$SAM_APP_KEY_FILE"
    sam_verify_secret_file_permissions "$SAM_DB_PASSWORD_FILE"
    sam_verify_secret_file_permissions "$SAM_MYSQL_ROOT_PASSWORD_FILE"
}

main() {
    sam_parse_arguments "$@"
    sam_verify_production_layout
    sam_configuration_paths

    if [[ "$SAM_CHECK_ONLY" == true ]]; then
        sam_check_configuration
    else
        sam_create_configuration
    fi

    sam_validate_environment "$SAM_ENV_FILE" "$SAM_SECRETS_DIR"
    sam_validate_secret_contents "$SAM_APP_KEY_FILE" "$SAM_DB_PASSWORD_FILE" "$SAM_MYSQL_ROOT_PASSWORD_FILE"
    sam_validate_compose_without_starting "$SAM_ENV_FILE"

    sam_log "Production configuration is valid for ${SAM_PRODUCTION_PROJECT_NAME}."
    sam_log "No containers were started and no database operation was performed."
}

main "$@"

