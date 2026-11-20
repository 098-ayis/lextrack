#!/bin/sh
set -eu

# Load the database password for Docker health checks.
# Health checks do not inherit variables exported by the entrypoint.
if [ -z "${DB_PASSWORD:-}" ] && [ -f /run/secrets/db_password ]; then
    DB_PASSWORD="$(cat /run/secrets/db_password)"
    export DB_PASSWORD
fi

case "${LEXTRACK_ROLE:-}" in
    queue)
        pattern='(^|[[:space:]/])php([[:space:]]|$).*artisan[[:space:]]+queue:work([[:space:]]|$)'
        ;;
    scheduler)
        pattern='(^|[[:space:]/])php([[:space:]]|$).*artisan[[:space:]]+schedule:work([[:space:]]|$)'
        ;;
    web)
        ;;
    *)
        echo "Unsupported or missing LEXTRACK_ROLE" >&2
        exit 1
        ;;
esac

if [ "${LEXTRACK_ROLE}" != "web" ]; then
    worker_pid="$(pgrep -u www-data -f "$pattern" | head -n 1 || true)"
    if [ -z "$worker_pid" ] || ! kill -0 "$worker_pid" 2>/dev/null; then
        echo "Expected ${LEXTRACK_ROLE} worker is not running as www-data" >&2
        exit 1
    fi
    worker_state="$(ps -o stat= -p "$worker_pid" | tr -d '[:space:]')"
    case "$worker_state" in
        *Z*)
            echo "Expected ${LEXTRACK_ROLE} worker is a zombie" >&2
            exit 1
            ;;
    esac
fi

php -r '
if (getenv("LEXTRACK_ROLE") === "web") {
    $port = getenv("PORT") ?: "10000";
    $url = "http://127.0.0.1:".$port."/up";
    $context = stream_context_create(["http" => ["timeout" => 5, "ignore_errors" => true]]);
    $body = @file_get_contents($url, false, $context);
    $status = $http_response_header[0] ?? "";
    if ($body === false || ! preg_match("~\\s2\\d{2}(?:\\s|$)~", $status)) {
        exit(1);
    }
}

$dsn = "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE");
try {
    new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 5]);
} catch (Throwable) {
    exit(1);
}
'
