#!/usr/bin/env bash

set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/safety.sh"
source "${SCRIPT_DIR}/lib/docker.sh"
source "${SCRIPT_DIR}/lib/verification.sh"

SAM_INSTALL_DIR="${LEXTRACK_INSTALL_DIR:-/opt/lextrack}"
SAM_BACKUP_FILE=""
SAM_CONFIRM=""
SAM_CHECK_ONLY=false

usage() { cat <<'USAGE'
Usage: scripts/sam/04-database.sh <command> [options]

Commands:
  init                 Start/verify MySQL without changing schema or data
  migrate              Run additive Laravel migrations only
  seed                 Run the normal reference-data DatabaseSeeder
  backup               Write a validated gzip SQL backup
  restore              Restore a validated backup; requires --confirm lextrack-production
  verify               Verify database connectivity and migration status

Options:
  --destination DIR    Configuration/secrets directory
  --file FILE          Backup file for backup/restore (default backup directory for backup)
  --confirm TEXT       Required exact confirmation for restore
  --check              Validate target/configuration only; do not run a database command
  --help               Show this help

No command deletes volumes or data. Restore preserves document and other
persistent volumes and asks for exact confirmation before replacing SQL data.
USAGE
}
parse_args() {
    SAM_COMMAND=${1:-}; [[ -n "$SAM_COMMAND" ]] && shift || { usage; return 1; }
    case "$SAM_COMMAND" in init|migrate|seed|backup|restore|verify) ;; --help|-h) usage; exit 0 ;; *) sam_die "Unknown database command: $SAM_COMMAND" ;; esac
    while (($#)); do
        case "$1" in
            --destination) [[ $# -gt 1 ]] || sam_die "--destination requires DIR"; SAM_INSTALL_DIR=$2; shift 2 ;;
            --destination=*) SAM_INSTALL_DIR=${1#*=}; shift ;;
            --file) [[ $# -gt 1 ]] || sam_die "--file requires FILE"; SAM_BACKUP_FILE=$2; shift 2 ;;
            --file=*) SAM_BACKUP_FILE=${1#*=}; shift ;;
            --confirm) [[ $# -gt 1 ]] || sam_die "--confirm requires TEXT"; SAM_CONFIRM=$2; shift 2 ;;
            --confirm=*) SAM_CONFIRM=${1#*=}; shift ;;
            --check) SAM_CHECK_ONLY=true; shift ;;
            --help|-h) usage; exit 0 ;;
            *) sam_die "Unknown option: $1" ;;
        esac
    done
}
load_config() {
    sam_reject_unsafe_destination "$SAM_INSTALL_DIR"
    sam_require_directory_access "$SAM_INSTALL_DIR"
    sam_require_file "$SAM_INSTALL_DIR/.env.production"
    sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/db_password"
    sam_verify_secret_file_permissions "$SAM_INSTALL_DIR/secrets/mysql_root_password"
    SAM_ENV_FILE="$SAM_INSTALL_DIR/.env.production"
}
compose() { docker compose --project-name "$SAM_PRODUCTION_PROJECT_NAME" --env-file "$SAM_ENV_FILE" --file "$SAM_PRODUCTION_COMPOSE_FILE" "$@"; }
app() { compose exec -T web php artisan "$@"; }
db_name() { awk -F= '$1=="DB_DATABASE" {print $2; exit}' "$SAM_ENV_FILE"; }
db_user() { awk -F= '$1=="DB_USERNAME" {print $2; exit}' "$SAM_ENV_FILE"; }
validate_identifier() { [[ "$1" =~ ^[A-Za-z0-9_]+$ ]] || sam_die "Unsafe database identifier in production configuration"; }
validate_backup() {
    [[ -f "$1" && -r "$1" && ! -L "$1" ]] || sam_die "Backup file is missing, unreadable, or a symlink: $1"
    [[ -s "$1" ]] || sam_die "Backup file is empty: $1"
    gzip -t -- "$1" >/dev/null 2>&1 || sam_die "Backup is not a valid gzip stream: $1"
}
wait_mysql() {
    local i
    for ((i=1; i<=30; i++)); do
        if compose exec -T mysql sh -c 'mysqladmin ping -h 127.0.0.1 -u root -p"$(cat /run/secrets/mysql_root_password)"' >/dev/null 2>&1; then return 0; fi
        sleep 2
    done
    sam_die "MySQL did not become available"
}
do_backup() {
    local target=${SAM_BACKUP_FILE:-"$SAM_INSTALL_DIR/backups/lextrack-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"}
    sam_require_safe_path "$target" "$SAM_INSTALL_DIR/backups" 2>/dev/null || { mkdir -p --mode=700 "$SAM_INSTALL_DIR/backups"; sam_require_safe_path "$target" "$SAM_INSTALL_DIR/backups"; }
    [[ ! -e "$target" ]] || sam_die "Refusing to overwrite existing backup: $target"
    local database username
    database=$(db_name); username=$(db_user); validate_identifier "$database"; validate_identifier "$username"
    umask 077
    compose exec -T mysql sh -c 'exec mysqldump --single-transaction --routines --triggers -h 127.0.0.1 -u "$1" --password="$(cat /run/secrets/db_password)" "$2"' sh "$username" "$database" | gzip -n > "$target"
    validate_backup "$target"
    sam_log "Created validated database backup: $target"
}
do_restore() {
    [[ -n "$SAM_BACKUP_FILE" ]] || sam_die "restore requires --file FILE"
    validate_backup "$SAM_BACKUP_FILE"
    sam_require_production_confirmation "$SAM_CONFIRM"
    local database username; database=$(db_name); username=$(db_user); validate_identifier "$database"; validate_identifier "$username"
    gzip -dc -- "$SAM_BACKUP_FILE" | compose exec -T mysql sh -c 'exec mysql -h 127.0.0.1 -u "$1" --password="$(cat /run/secrets/db_password)" "$2"' sh "$username" "$database"
    sam_log "Database restore completed; persistent document volumes were not changed."
}
main() {
    parse_args "$@"; sam_verify_production_layout; sam_require_docker_prerequisites; load_config; compose config --quiet
    if [[ "$SAM_CHECK_ONLY" == true ]]; then sam_log "Database target validation passed; no database command was run."; return 0; fi
    case "$SAM_COMMAND" in
        init) compose up -d mysql; wait_mysql; sam_log "Production MySQL is available." ;;
        migrate) compose up -d mysql web; wait_mysql; app migrate --force ;;
        seed) compose up -d mysql web; wait_mysql; app db:seed --force ;;
        backup) compose up -d mysql; wait_mysql; do_backup ;;
        restore) compose up -d mysql; wait_mysql; do_restore ;;
        verify) compose up -d mysql web; wait_mysql; app migrate:status ;;
    esac
}
main "$@"
