#!/usr/bin/env bash

# Shared, dependency-free helpers for the LexTrack SAM scripts.
#
# This file is intentionally safe to source from a shell with or without
# `set -e`. It does not inspect Git metadata, Docker state, or secrets while
# being sourced.

if [[ -n "${LEXTRACK_SAM_COMMON_SH_LOADED:-}" ]]; then
    return 0
fi
LEXTRACK_SAM_COMMON_SH_LOADED=1

SAM_LIB_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
SAM_SCRIPTS_DIR="$(cd -- "${SAM_LIB_DIR}/.." && pwd -P)"
SAM_ROOT="$(cd -- "${SAM_SCRIPTS_DIR}/../.." && pwd -P)"

SAM_PRODUCTION_PROJECT_NAME="lextrack-production"
SAM_PRODUCTION_COMPOSE_FILENAME="compose.production.yaml"
SAM_PRODUCTION_COMPOSE_FILE="${SAM_ROOT}/${SAM_PRODUCTION_COMPOSE_FILENAME}"

sam_log() {
    printf '[sam] %s\n' "$*"
}

sam_warn() {
    printf '[sam][warning] %s\n' "$*" >&2
}

sam_error() {
    printf '[sam][error] %s\n' "$*" >&2
}

sam_die() {
    local message=${1:-SAM operation failed}
    local status=${2:-1}

    sam_error "$message"
    return "$status"
}

sam_require_command() {
    local command_name=${1:?command name is required}

    command -v "$command_name" >/dev/null 2>&1 || {
        sam_error "Required command is unavailable: ${command_name}"
        return 1
    }
}

sam_require_file() {
    local file_path=${1:?file path is required}

    [[ -f "$file_path" && -r "$file_path" ]] || {
        sam_error "Required readable file is missing: ${file_path}"
        return 1
    }
}

sam_require_directory() {
    local directory_path=${1:?directory path is required}

    [[ -d "$directory_path" ]] || {
        sam_error "Required directory is missing: ${directory_path}"
        return 1
    }
}

sam_require_directory_access() {
    local directory_path=${1:?directory path is required}

    sam_require_directory "$directory_path" || return 1
    [[ -r "$directory_path" && -x "$directory_path" ]] || {
        sam_error "Directory is not readable/searchable: ${directory_path}"
        return 1
    }
    [[ -w "$directory_path" ]] || {
        sam_error "Directory is not writable: ${directory_path}"
        return 1
    }
}

sam_absolute_path() {
    local path=${1:?path is required}

    command -v realpath >/dev/null 2>&1 || {
        sam_error "The realpath command is required to validate paths"
        return 1
    }

    realpath -m -- "$path"
}

