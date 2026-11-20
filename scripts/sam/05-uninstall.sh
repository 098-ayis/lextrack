#!/usr/bin/env bash

set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/safety.sh"
source "${SCRIPT_DIR}/lib/docker.sh"
source "${SCRIPT_DIR}/lib/verification.sh"
SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"
SAM_DRY_RUN=false; SAM_CONFIRM=""; SAM_REMOVE_VOLUMES=false; SAM_CONFIRM_VOLUMES=false
usage() { cat <<'USAGE'
Usage: scripts/sam/05-uninstall.sh [options]
  --destination DIR       Installation/configuration directory
  --dry-run               Display only the exact production resources affected
  --remove-volumes        Also remove the four named production volumes
  --confirm TEXT          Required exact confirmation: lextrack-production
  --confirm-volumes       Additional confirmation when --remove-volumes is used
  --help                  Show this help

By default only lextrack-production containers and network are removed; named
database/document volumes are preserved. No Docker prune operation is used.
USAGE
}
parse_args() { while (($#)); do case "$1" in
    --destination) [[ $# -gt 1 ]] || sam_die "--destination requires DIR"; SAM_INSTALL_DIR=$2; shift 2;; --destination=*) SAM_INSTALL_DIR=${1#*=}; shift;;
    --dry-run) SAM_DRY_RUN=true; shift;; --remove-volumes) SAM_REMOVE_VOLUMES=true; shift;; --confirm-volumes) SAM_CONFIRM_VOLUMES=true; shift;;
    --confirm) [[ $# -gt 1 ]] || sam_die "--confirm requires TEXT"; SAM_CONFIRM=$2; shift 2;; --confirm=*) SAM_CONFIRM=${1#*=}; shift;;
    --help|-h) usage; exit 0;; *) sam_die "Unknown option: $1";; esac; done; }
main() {
    parse_args "$@"; sam_verify_production_layout; sam_require_docker_prerequisites; sam_validate_production_target "$SAM_PRODUCTION_PROJECT_NAME" "$SAM_PRODUCTION_COMPOSE_FILE"
    sam_log "Target project: $SAM_PRODUCTION_PROJECT_NAME"
    sam_log "Affected containers: web, mysql, queue, scheduler (if present)"
    sam_log "Affected network: lextrack-production (if present)"
    sam_log "Preserved volumes by default: ${SAM_PRODUCTION_VOLUME_NAMES[*]}"
    if [[ "$SAM_REMOVE_VOLUMES" == true ]]; then sam_log "Additional destructive targets: ${SAM_PRODUCTION_VOLUME_NAMES[*]}"; fi
    [[ "$SAM_DRY_RUN" == true ]] && { sam_log "Dry run complete; no resources were changed."; return 0; }
    sam_require_production_confirmation "$SAM_CONFIRM"
    sam_production_compose_destructive down "$SAM_CONFIRM" --remove-orphans
    if [[ "$SAM_REMOVE_VOLUMES" == true ]]; then
        [[ "$SAM_CONFIRM_VOLUMES" == true ]] || sam_die "Removing volumes also requires --confirm-volumes"
        local volume; for volume in "${SAM_PRODUCTION_VOLUME_NAMES[@]}"; do sam_remove_production_volume "$volume" "${SAM_PRODUCTION_PROJECT_NAME}:$volume"; done
    fi
    sam_log "Only the lextrack-production resources listed above were targeted."
}
main "$@"
