#!/usr/bin/env bash

# LexTrack SAM target and destructive-operation guards.

if [[ -z "${LEXTRACK_SAM_COMMON_SH_LOADED:-}" ]]; then
    # shellcheck source=common.sh
    source "$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)/common.sh"
fi

SAM_PROTECTED_PROJECT_NAMES=(
    "lextrack"
    "lextrack-phase2-runtime-test"
    "lextrack-sam-test"
)

SAM_PROTECTED_VOLUME_NAMES=(
    "lextrack_sail-mysql"
)

SAM_PRODUCTION_SERVICE_NAMES=(web mysql queue scheduler)
SAM_PRODUCTION_VOLUME_NAMES=(
    "lextrack-production-mysql"
    "lextrack-production-documents"
    "lextrack-production-public"
    "lextrack-production-knowledge"
)

sam_reject_unsafe_destination() {
    local destination=$1
    local absolute_destination
    local protected_path

    absolute_destination=$(sam_absolute_path "$destination") || return 1
    for protected_path in \
        "/home/ayis/LaravelProjs/lextrack" \
        "/home/ayis/LaravelProjs/lextrack-phase2-runtime-test" \
        "/home/ayis/LaravelProjs/lextrack-sam-test"; do
        if [[ "$absolute_destination" == "$protected_path" || "$absolute_destination" == "$protected_path"/* ]]; then
            sam_error "Refusing to use protected LexTrack path: ${destination}"
            return 1
        fi
    done
}

sam_is_member() {
    local candidate=${1:?candidate is required}
    shift
    local item

    for item in "$@"; do
        [[ "$candidate" == "$item" ]] && return 0
    done
    return 1
}

sam_validate_project_name() {
    local project_name=${1:?project name is required}

    if sam_is_member "$project_name" "${SAM_PROTECTED_PROJECT_NAMES[@]}"; then
        sam_error "Protected Compose project rejected: ${project_name}"
        return 1
    fi

    if [[ "$project_name" != "$SAM_PRODUCTION_PROJECT_NAME" ]]; then
        sam_error "Only the production Compose project is permitted: ${SAM_PRODUCTION_PROJECT_NAME}"
        return 1
    fi
}

sam_validate_compose_file() {
    local compose_file=${1:?Compose file path is required}
    local expected_path
    local actual_path

    expected_path=$(sam_absolute_path "$SAM_PRODUCTION_COMPOSE_FILE") || return 1
    actual_path=$(sam_absolute_path "$compose_file") || return 1

    [[ "$actual_path" == "$expected_path" ]] || {
        sam_error "Only ${SAM_PRODUCTION_COMPOSE_FILENAME} in the SAM root is permitted: ${compose_file}"
        return 1
    }

    sam_require_file "$actual_path"
}

sam_validate_service_name() {
    local service_name=${1:?service name is required}

    sam_is_member "$service_name" "${SAM_PRODUCTION_SERVICE_NAMES[@]}" || {
        sam_error "Invalid production service name: ${service_name}"
        return 1
    }
}

sam_validate_volume_name() {
    local volume_name=${1:?volume name is required}

    if sam_is_member "$volume_name" "${SAM_PROTECTED_VOLUME_NAMES[@]}"; then
        sam_error "Protected Docker volume rejected: ${volume_name}"
        return 1
    fi

    sam_is_member "$volume_name" "${SAM_PRODUCTION_VOLUME_NAMES[@]}" || {
        sam_error "Invalid production volume name: ${volume_name}"
        return 1
    }
}

sam_validate_production_target() {
    local project_name=${1:?project name is required}
    local compose_file=${2:?Compose file path is required}

    sam_validate_project_name "$project_name" || return 1
    sam_validate_compose_file "$compose_file" || return 1
}

sam_require_exact_confirmation() {
    local expected=${1:?expected confirmation is required}
    local supplied=${2-}

    [[ -n "$supplied" && "$supplied" == "$expected" ]] || {
        sam_error "Exact confirmation required: ${expected}"
        return 1
    }
}

sam_require_production_confirmation() {
    local supplied=${1-}

    sam_require_exact_confirmation "${SAM_PRODUCTION_PROJECT_NAME}" "$supplied"
}

sam_require_safe_path() {
    local path=${1:?path is required}
    local allowed_root=${2:?allowed root is required}
    local absolute_path
    local absolute_root

    absolute_path=$(sam_absolute_path "$path") || return 1
    absolute_root=$(sam_absolute_path "$allowed_root") || return 1

    [[ "$absolute_path" == "$absolute_root" || "$absolute_path" == "${absolute_root}/"* ]] || {
        sam_error "Path is outside the permitted root: ${path}"
        return 1
    }
}

