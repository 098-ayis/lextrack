#!/usr/bin/env bash

# Read-only verification helpers for the SAM installation scripts.

if [[ -z "${LEXTRACK_SAM_COMMON_SH_LOADED:-}" ]]; then
    # shellcheck source=common.sh
    source "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)/common.sh"
fi
if [[ -z "${LEXTRACK_SAM_SAFETY_SH_LOADED:-}" ]]; then
    # shellcheck source=safety.sh
    source "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)/safety.sh"
fi
LEXTRACK_SAM_SAFETY_SH_LOADED=1

sam_require_bash() {
    sam_require_command bash || return 1

    [[ -n "${BASH_VERSION:-}" ]] || {
        sam_error "The SAM helpers must run under Bash"
        return 1
    }
}

sam_read_os_release_value() {
    local os_release_file=${1:?os-release file is required}
    local key=${2:?os-release key is required}

    awk -F= -v key="$key" '
        $1 == key {
            value = substr($0, index($0, "=") + 1)
            gsub(/^"|"$/, "", value)
            gsub(/^'"'"'|'"'"'$/, "", value)
            print value
            exit
        }
    ' "$os_release_file"
}

sam_verify_host_platform() {
    local os_release_file=${1:-/etc/os-release}
    local architecture=${2:-$(uname -m)}
    local operating_system
    local version_id

    sam_require_file "$os_release_file" || return 1
    operating_system=$(sam_read_os_release_value "$os_release_file" ID)
    version_id=$(sam_read_os_release_value "$os_release_file" VERSION_ID)

    if [[ "$operating_system" != "ubuntu" ]]; then
        sam_error "Unsupported operating system: ${operating_system:-unknown}; Ubuntu 24.04 LTS or 26.04 LTS is required"
        return 1
    fi

    case "$version_id" in
        24.04|26.04) ;;
        *)
            sam_error "Unsupported Ubuntu release: ${version_id:-unknown}; Ubuntu 24.04 LTS or 26.04 LTS is required"
            return 1
            ;;
    esac

    case "$architecture" in
        x86_64|amd64) ;;
        *)
            sam_error "Unsupported architecture: ${architecture}; AMD64/x86_64 is required"
            return 1
            ;;
    esac
}

sam_verify_required_files() {
    local required_file
    local required_files=(
        "$SAM_PRODUCTION_COMPOSE_FILE"
        "$SAM_ROOT/Dockerfile"
        "$SAM_ROOT/.env.production.example"
        "$SAM_ROOT/artisan"
        "$SAM_ROOT/composer.json"
        "$SAM_ROOT/composer.lock"
        "$SAM_ROOT/package.json"
        "$SAM_ROOT/package-lock.json"
        "$SAM_ROOT/docker/production/entrypoint.sh"
        "$SAM_ROOT/docker/production/healthcheck.sh"
    )

    for required_file in "${required_files[@]}"; do
        sam_require_file "$required_file" || return 1
    done
}

sam_verify_sam_root() {
    sam_require_directory "$SAM_ROOT" || return 1
    sam_require_directory_access "$SAM_ROOT" || return 1
    sam_verify_required_files || return 1
}

sam_verify_directory_permissions() {
    local directory_path=${1:?directory path is required}

    sam_require_directory_access "$directory_path"
}

sam_verify_parent_permissions() {
    local path=${1:?path is required}
    local parent_directory

    parent_directory=$(dirname -- "$path")
    sam_require_directory_access "$parent_directory"
}

sam_verify_secret_file_permissions() {
    local secret_file=${1:?secret file path is required}

    sam_require_file "$secret_file" || return 1
    [[ ! -L "$secret_file" ]] || {
        sam_error "Secret file must not be a symbolic link: ${secret_file}"
        return 1
    }

    # Do not read or print the secret. Restricting access to the owner prevents
    # accidental exposure through group/world-readable deployment files.
    local mode
    mode=$(stat -c '%a' -- "$secret_file" 2>/dev/null) || {
        sam_error "Unable to inspect secret file permissions: ${secret_file}"
        return 1
    }
    [[ "$mode" == "600" || "$mode" == "400" ]] || {
        sam_error "Secret file must have mode 600 or 400: ${secret_file}"
        return 1
    }
}

sam_read_env_value() {
    local env_file=$1
    local key=$2

    awk -v key="$key" '
        index($0, "#") == 1 { next }
        $0 ~ "^" key "=" {
            sub("^" key "=", "", $0)
            print $0
            exit
        }
    ' "$env_file"
}

sam_env_has_key() {
    local env_file=$1
    local key=$2

    awk -v key="$key" '$0 ~ "^" key "=" { found=1; exit } END { exit(found ? 0 : 1) }' "$env_file"
}

sam_require_env_value() {
    local env_file=$1
    local key=$2
    local value

    value=$(sam_read_env_value "$env_file" "$key")
    [[ -n "$value" ]] || {
        sam_error "Required production setting is missing or empty: ${key}"
        return 1
    }
}

sam_validate_environment() {
    local env_file=$1
    local secrets_directory=$2
    local app_url
    local app_env
    local app_debug
    local configured_secrets_directory

    sam_verify_secret_file_permissions "$env_file"
    sam_require_env_value "$env_file" APP_URL
    sam_require_env_value "$env_file" DB_DATABASE
    sam_require_env_value "$env_file" DB_USERNAME
    sam_require_env_value "$env_file" LEXTRACK_SECRETS_DIR

    app_url=$(sam_read_env_value "$env_file" APP_URL)
    [[ "$app_url" =~ ^https?://[^[:space:]]+$ && "$app_url" != *example.invalid* ]] || {
        sam_error "APP_URL must be a real HTTP(S) URL, not the template placeholder"
        return 1
    }

    app_env=$(sam_read_env_value "$env_file" APP_ENV)
    [[ "$app_env" == "production" ]] || {
        sam_error "APP_ENV must be production"
        return 1
    }

    app_debug=$(sam_read_env_value "$env_file" APP_DEBUG)
    [[ "$app_debug" == "false" || "$app_debug" == "0" ]] || {
        sam_error "APP_DEBUG must be false or 0"
        return 1
    }

    configured_secrets_directory=$(sam_absolute_path "$(sam_read_env_value "$env_file" LEXTRACK_SECRETS_DIR)")
    [[ "$configured_secrets_directory" == "$(sam_absolute_path "$secrets_directory")" ]] || {
        sam_error "LEXTRACK_SECRETS_DIR must point to the designated secrets directory"
        return 1
    }

    if sam_env_has_key "$env_file" SESSION_PARTITIONED; then
        sam_error "SESSION_PARTITIONED is obsolete; use SESSION_PARTITIONED_COOKIE"
        return 1
    fi
    sam_require_env_value "$env_file" SESSION_PARTITIONED_COOKIE
}

sam_validate_secret_contents() {
    local app_key_file=$1
    local db_password_file=$2
    local mysql_root_password_file=$3
    local app_key
    local db_password
    local mysql_root_password
    local encoded_app_key

    app_key=$(<"$app_key_file")
    [[ "$app_key" =~ ^base64:[A-Za-z0-9+/]+=*$ ]] || {
        sam_error "app_key is not a valid Laravel base64 application key"
        return 1
    }
    encoded_app_key=${app_key#base64:}
    [[ ${#encoded_app_key} -eq 44 ]] || {
        sam_error "app_key must encode exactly 32 bytes"
        return 1
    }
    printf '%s' "$encoded_app_key" | base64 --decode >/dev/null 2>&1 || {
        sam_error "app_key contains invalid base64 data"
        return 1
    }

    db_password=$(<"$db_password_file")
    [[ -n "$db_password" ]] || {
        sam_error "db_password is empty"
        return 1
    }
    mysql_root_password=$(<"$mysql_root_password_file")
    [[ -n "$mysql_root_password" ]] || {
        sam_error "mysql_root_password is empty"
        return 1
    }
}

sam_verify_production_layout() {
    sam_require_bash || return 1
    sam_verify_host_platform || return 1
    sam_verify_sam_root || return 1
    sam_validate_project_name "$SAM_PRODUCTION_PROJECT_NAME" || return 1
    sam_validate_compose_file "$SAM_PRODUCTION_COMPOSE_FILE" || return 1
}

