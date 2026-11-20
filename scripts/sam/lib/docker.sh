#!/usr/bin/env bash

# Safe Docker and Compose prerequisite checks/wrapper.

if [[ -z "${LEXTRACK_SAM_COMMON_SH_LOADED:-}" ]]; then
    # shellcheck source=common.sh
    source "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)/common.sh"
fi
if [[ -z "${LEXTRACK_SAM_SAFETY_SH_LOADED:-}" ]]; then
    # shellcheck source=safety.sh
    source "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)/safety.sh"
fi
LEXTRACK_SAM_SAFETY_SH_LOADED=1

sam_require_docker_engine() {
    sam_require_command docker || return 1

    local version
    version=$(docker version --format '{{.Server.Version}}' 2>/dev/null) || {
        sam_error "Docker Engine is unavailable or the current user lacks Docker access"
        return 1
    }

    [[ "$version" =~ ^v?[0-9]+\.[0-9]+\.[0-9]+([-+][0-9A-Za-z.-]+)?$ ]] || {
        sam_error "Invalid Docker Engine version response: ${version:-empty}"
        return 1
    }
}

sam_validate_docker_compose_version() {
    local version=${1-}
    local major

    if [[ "$version" =~ ^v?([0-9]+)\.([0-9]+)\.([0-9]+)([-+][0-9A-Za-z.-]+)?$ ]]; then
        major=${BASH_REMATCH[1]}
    else
        sam_error "Invalid Docker Compose version response: ${version:-empty}"
        return 1
    fi

    if (( 10#$major < 2 )); then
        sam_error "Docker Compose 2 or newer is required; detected: ${version}"
        return 1
    fi
}

sam_require_docker_compose_v2() {
    sam_require_docker_engine || return 1

    local version
    version=$(docker compose version --short 2>/dev/null) || {
        sam_error "Docker Compose 2 or newer is unavailable"
        return 1
    }

    sam_validate_docker_compose_version "$version"
}

sam_require_docker_prerequisites() {
    sam_require_docker_compose_v2 || return 1
}

sam_production_compose() {
    case "${1-}" in
        down|kill|rm|restart|stop)
            sam_error "Use sam_production_compose_destructive for: ${1}"
            return 1
            ;;
    esac

    sam_validate_project_name "$SAM_PRODUCTION_PROJECT_NAME" || return 1
    sam_validate_compose_file "$SAM_PRODUCTION_COMPOSE_FILE" || return 1
    sam_require_docker_compose_v2 || return 1

    # Every caller is pinned to both the exact project name and exact file.
    # Callers must still pass an explicit, reviewed Compose subcommand.
    docker compose \
        --project-name "$SAM_PRODUCTION_PROJECT_NAME" \
        --file "$SAM_PRODUCTION_COMPOSE_FILE" \
        "$@"
}

sam_production_compose_destructive() {
    local action=${1:?Compose action is required}
    local confirmation=${2-}

    case "$action" in
        down|kill|rm|restart|stop) ;;
        *)
            sam_error "Unsupported destructive Compose action: ${action}"
            return 1
            ;;
    esac

    sam_require_production_confirmation "$confirmation" || return 1
    shift 2
    sam_production_compose "$action" "$@"
}

sam_remove_production_volume() {
    local volume_name=${1:?production volume name is required}
    local confirmation=${2-}

    sam_validate_production_volume "$volume_name" || return 1
    sam_require_exact_confirmation "${SAM_PRODUCTION_PROJECT_NAME}:${volume_name}" "$confirmation" || return 1
    sam_require_docker_compose_v2 || return 1

    # The volume name is validated against the production allowlist above;
    # this is deliberately not `docker volume prune` or a wildcard operation.
    docker volume rm -- "$volume_name"
}

sam_validate_production_service() {
    sam_validate_project_name "$SAM_PRODUCTION_PROJECT_NAME" || return 1
    sam_validate_service_name "$1" || return 1
}

sam_validate_production_volume() {
    sam_validate_project_name "$SAM_PRODUCTION_PROJECT_NAME" || return 1
    sam_validate_volume_name "$1" || return 1
}

