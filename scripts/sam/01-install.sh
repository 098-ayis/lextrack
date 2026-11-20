#!/usr/bin/env bash

set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/safety.sh"
source "${SCRIPT_DIR}/lib/docker.sh"
source "${SCRIPT_DIR}/lib/verification.sh"

SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"
SAM_OFFLINE_PACKAGE=""
SAM_CHECK_ONLY=false

usage() {
    cat <<'USAGE'
Usage: scripts/sam/01-install.sh [options]

Install or validate the isolated LexTrack production Compose project.
  --destination DIR       Configuration/secrets directory (default /opt/lextrack)
  --offline-package FILE  Docker image archive produced by docker save
  --check                 Validate only; do not create, load, or start anything
  --help                  Show this help

Normal installation creates missing configuration through 03-configure.sh,
loads an optional offline image archive, starts only lextrack-production, and
waits for all four services to become healthy. Existing configuration, secrets,
databases, and persistent volumes are never replaced.
USAGE
}

parse_args() {
    while (($#)); do
        case "$1" in
            --destination) [[ $# -gt 1 ]] || sam_die "--destination requires DIR"; SAM_INSTALL_DIR=$2; shift 2 ;;
            --destination=*) SAM_INSTALL_DIR=${1#*=}; shift ;;
            --offline-package) [[ $# -gt 1 ]] || sam_die "--offline-package requires FILE"; SAM_OFFLINE_PACKAGE=$2; shift 2 ;;
            --offline-package=*) SAM_OFFLINE_PACKAGE=${1#*=}; shift ;;
            --check) SAM_CHECK_ONLY=true; shift ;;
            --help|-h) usage; exit 0 ;;
            *) sam_die "Unknown option: $1" ;;
        esac
    done
}

configuration_check() {
    local config_script=${SCRIPT_DIR}/03-configure.sh
    [[ -x "$config_script" ]] || sam_die "Missing executable configuration script: $config_script"
    if [[ "$SAM_CHECK_ONLY" == true ]]; then
        bash "$config_script" --check --destination "$SAM_INSTALL_DIR"
    else
        bash "$config_script" --destination "$SAM_INSTALL_DIR"
    fi
    SAM_ENV_FILE="${SAM_INSTALL_DIR}/.env.production"
    SAM_SECRETS_DIR="${SAM_INSTALL_DIR}/secrets"
}

compose_with_env() {
    sam_validate_production_target "$SAM_PRODUCTION_PROJECT_NAME" "$SAM_PRODUCTION_COMPOSE_FILE"
    docker compose --project-name "$SAM_PRODUCTION_PROJECT_NAME" --env-file "$SAM_ENV_FILE" --file "$SAM_PRODUCTION_COMPOSE_FILE" "$@"
}

verify_services() {
    local service container status
    for service in "${SAM_PRODUCTION_SERVICE_NAMES[@]}"; do
        container=$(compose_with_env ps -q "$service")
        [[ -n "$container" ]] || { sam_error "No container exists for production service: $service"; return 1; }
        status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container")
        [[ "$status" == healthy ]] || { sam_error "Production service is not healthy: $service"; return 1; }
    done
}

wait_for_services() {
    local attempt
    for ((attempt=1; attempt<=60; attempt++)); do
        if verify_services 2>/dev/null; then
            sam_log "All LexTrack production services are healthy."
            return 0
        fi
        sleep 2
    done
    sam_error "Timed out waiting for LexTrack production services to become healthy."
    compose_with_env ps || true
    return 1
}

main() {
    parse_args "$@"
    sam_verify_production_layout
    sam_require_command openssl
    sam_require_command base64
    sam_require_docker_prerequisites
    configuration_check
    sam_validate_environment "$SAM_ENV_FILE" "$SAM_SECRETS_DIR"
    sam_validate_secret_contents "$SAM_SECRETS_DIR/app_key" "$SAM_SECRETS_DIR/db_password" "$SAM_SECRETS_DIR/mysql_root_password"
    [[ -z "$SAM_OFFLINE_PACKAGE" || -f "$SAM_OFFLINE_PACKAGE" ]] || sam_die "Offline package is not a readable file: $SAM_OFFLINE_PACKAGE"
    [[ "$SAM_CHECK_ONLY" == true ]] && { compose_with_env config --quiet; sam_log "Installation validation passed; no changes were made."; return 0; }
    if [[ -n "$SAM_OFFLINE_PACKAGE" ]]; then
        sam_log "Loading Docker images from the offline package."
        docker load --input "$SAM_OFFLINE_PACKAGE" >/dev/null
    fi
    compose_with_env up --detach --no-build --pull never
    wait_for_services
    sam_log "LexTrack production installation completed without resetting data or credentials."
}
main "$@"
