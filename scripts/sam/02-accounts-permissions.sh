#!/usr/bin/env bash

set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/safety.sh"
source "${SCRIPT_DIR}/lib/docker.sh"
source "${SCRIPT_DIR}/lib/verification.sh"

SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"
SAM_DEPLOYMENT_USER="${SUDO_USER:-${USER:-}}"
SAM_CHECK_ONLY=false
SAM_ADMIN_EMAIL=""
SAM_ADMIN_NAME=""

usage() { cat <<'USAGE'
Usage: scripts/sam/02-accounts-permissions.sh [options]
  --destination DIR       Installation/configuration directory
  --deployment-user USER  Owner of installation files (default invoking user)
  --admin-email EMAIL     Provision this existing Google account as admin
  --admin-name NAME       Display name used only when creating that account
  --check                 Validate only; do not change ownership or provision
  --help                  Show this help

Only the configured LexTrack installation directory is changed. Passwords,
keys, and credentials are never printed. Administrator provisioning uses the
existing admin:provision Artisan command and creates no password.
USAGE
}
parse_args() {
    while (($#)); do
        case "$1" in
            --destination) [[ $# -gt 1 ]] || sam_die "--destination requires DIR"; SAM_INSTALL_DIR=$2; shift 2 ;;
            --destination=*) SAM_INSTALL_DIR=${1#*=}; shift ;;
            --deployment-user) [[ $# -gt 1 ]] || sam_die "--deployment-user requires USER"; SAM_DEPLOYMENT_USER=$2; shift 2 ;;
            --deployment-user=*) SAM_DEPLOYMENT_USER=${1#*=}; shift ;;
            --admin-email) [[ $# -gt 1 ]] || sam_die "--admin-email requires EMAIL"; SAM_ADMIN_EMAIL=$2; shift 2 ;;
            --admin-email=*) SAM_ADMIN_EMAIL=${1#*=}; shift ;;
            --admin-name) [[ $# -gt 1 ]] || sam_die "--admin-name requires NAME"; SAM_ADMIN_NAME=$2; shift 2 ;;
            --admin-name=*) SAM_ADMIN_NAME=${1#*=}; shift ;;
            --check) SAM_CHECK_ONLY=true; shift ;;
            --help|-h) usage; exit 0 ;;
            *) sam_die "Unknown option: $1" ;;
        esac
    done
}
validate() {
    [[ "$SAM_DEPLOYMENT_USER" =~ ^[a-z_][a-z0-9_-]*[$]?$ ]] || sam_die "Invalid deployment user name"
    id "$SAM_DEPLOYMENT_USER" >/dev/null 2>&1 || sam_die "Deployment user does not exist: $SAM_DEPLOYMENT_USER"
    sam_reject_unsafe_destination "$SAM_INSTALL_DIR"
    sam_require_directory_access "$SAM_INSTALL_DIR"
    sam_require_directory_access "$SAM_INSTALL_DIR/secrets"
    sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/.env.production"
    for secret in app_key db_password mysql_root_password; do sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/$secret"; done
    sam_require_docker_prerequisites
    sam_validate_production_target "$SAM_PRODUCTION_PROJECT_NAME" "$SAM_PRODUCTION_COMPOSE_FILE"
}
set_permissions() {
    local path
    chown -- "$SAM_DEPLOYMENT_USER:$SAM_DEPLOYMENT_USER" "$SAM_INSTALL_DIR" "$SAM_INSTALL_DIR/.env.production" "$SAM_INSTALL_DIR/secrets" "$SAM_INSTALL_DIR/secrets"/*
    chmod 750 -- "$SAM_INSTALL_DIR" "$SAM_INSTALL_DIR/secrets"
    chmod 600 -- "$SAM_INSTALL_DIR/.env.production"
    for path in "$SAM_INSTALL_DIR/secrets"/*; do chmod 600 -- "$path"; done
}
provision_admin() {
    [[ -z "$SAM_ADMIN_EMAIL" ]] && return 0
    [[ "$SAM_ADMIN_EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || sam_die "Invalid administrator email"
    local -a command=(exec -T web php artisan admin:provision "$SAM_ADMIN_EMAIL")
    [[ -n "$SAM_ADMIN_NAME" ]] && command+=(--name="$SAM_ADMIN_NAME")
    docker compose --project-name "$SAM_PRODUCTION_PROJECT_NAME" --env-file "$SAM_INSTALL_DIR/.env.production" --file "$SAM_PRODUCTION_COMPOSE_FILE" "${command[@]}" >/dev/null
    sam_log "Administrator provisioning completed for the supplied account."
}
main() { parse_args "$@"; sam_verify_production_layout; validate; [[ "$SAM_CHECK_ONLY" == true ]] && { sam_log "Account and permission validation passed; no changes were made."; return 0; }; set_permissions; provision_admin; sam_log "Installation ownership and secret permissions are configured."; }
main "$@"
