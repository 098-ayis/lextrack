#!/usr/bin/env bash

set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/safety.sh"
source "${SCRIPT_DIR}/lib/docker.sh"
source "${SCRIPT_DIR}/lib/verification.sh"
SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"; SAM_OFFLINE_PACKAGE=""; SAM_RECREATE=false; SAM_CHECK_ONLY=false; SAM_CONFIRM=""
usage() { cat <<'USAGE'
Usage: scripts/sam/06-rebuild.sh [options]
  --destination DIR       Configuration/secrets directory
  --offline-package FILE  Load a replacement prebuilt Docker image archive
  --recreate              Explicitly recreate production application containers
  --confirm TEXT          Required exact confirmation for --recreate
  --check                 Validate only; do not load images or change containers
  --help                  Show this help

The rebuild never resets the database and never removes persistent volumes.
Without --recreate it only validates and optionally loads the image archive.
USAGE
}
parse_args() { while (($#)); do case "$1" in
    --destination) [[ $# -gt 1 ]] || sam_die "--destination requires DIR"; SAM_INSTALL_DIR=$2; shift 2;; --destination=*) SAM_INSTALL_DIR=${1#*=}; shift;;
    --offline-package) [[ $# -gt 1 ]] || sam_die "--offline-package requires FILE"; SAM_OFFLINE_PACKAGE=$2; shift 2;; --offline-package=*) SAM_OFFLINE_PACKAGE=${1#*=}; shift;;
    --recreate) SAM_RECREATE=true; shift;; --confirm) [[ $# -gt 1 ]] || sam_die "--confirm requires TEXT"; SAM_CONFIRM=$2; shift 2;; --confirm=*) SAM_CONFIRM=${1#*=}; shift;;
    --check) SAM_CHECK_ONLY=true; shift;; --help|-h) usage; exit 0;; *) sam_die "Unknown option: $1";; esac; done; }
main() {
    parse_args "$@"; sam_verify_production_layout; sam_require_docker_prerequisites; sam_reject_unsafe_destination "$SAM_INSTALL_DIR"; sam_require_directory_access "$SAM_INSTALL_DIR"; sam_require_file "$SAM_INSTALL_DIR/.env.production"; sam_validate_production_target "$SAM_PRODUCTION_PROJECT_NAME" "$SAM_PRODUCTION_COMPOSE_FILE"; sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/app_key"; sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/db_password"; sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/mysql_root_password"; [[ -z "$SAM_OFFLINE_PACKAGE" || -f "$SAM_OFFLINE_PACKAGE" ]] || sam_die "Offline package is not a readable file: $SAM_OFFLINE_PACKAGE"
    [[ "$SAM_CHECK_ONLY" == true ]] && { sam_log "Rebuild validation passed; no changes were made."; return 0; }
    if [[ -n "$SAM_OFFLINE_PACKAGE" ]]; then sam_log "Loading Docker images from the offline package."; docker load --input "$SAM_OFFLINE_PACKAGE" >/dev/null; fi
    [[ "$SAM_RECREATE" == true ]] || { sam_log "No container recreation requested; persistent data and running containers were left unchanged."; return 0; }
    sam_require_production_confirmation "$SAM_CONFIRM"
    local compose_args=(--project-name "$SAM_PRODUCTION_PROJECT_NAME" --env-file "$SAM_INSTALL_DIR/.env.production" --file "$SAM_PRODUCTION_COMPOSE_FILE")
    docker compose "${compose_args[@]}" up -d --no-build --pull never --force-recreate
    local service container status attempt
    for ((attempt=1; attempt<=60; attempt++)); do
        local all_healthy=true
        for service in "${SAM_PRODUCTION_SERVICE_NAMES[@]}"; do
            container=$(docker compose "${compose_args[@]}" ps -q "$service")
            [[ -n "$container" ]] || { all_healthy=false; break; }
            status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container")
            [[ "$status" == healthy ]] || { all_healthy=false; break; }
        done
        [[ "$all_healthy" == true ]] && break
        sleep 2
    done
    [[ "$all_healthy" == true ]] || sam_die "Timed out waiting for rebuilt production services to become healthy"
    sam_log "Application containers were recreated and verified without resetting the database or deleting volumes."
}
main "$@"
