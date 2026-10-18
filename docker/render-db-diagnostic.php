<?php

declare(strict_types=1);

if (! filter_var(getenv('LEXTRACK_DB_DIAGNOSTICS') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
    exit(0);
}

/**
 * Report only booleans, counts, and categories. Never print credentials,
 * certificate contents, or connection strings.
 */
function diagnostic(string $name, string|int|bool $value): void
{
    printf(
        "[lextrack-db-diagnostics] %s=%s\n",
        $name,
        is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value,
    );
}

$expectedCaPath = '/etc/secrets/aiven-ca.pem';
$configuredCaPath = trim((string) getenv('MYSQL_ATTR_SSL_CA'));
$caPath = $configuredCaPath !== '' ? $configuredCaPath : $expectedCaPath;

$expectedCaReadable = is_readable($expectedCaPath);
$configuredCaReadable = is_readable($caPath);
$caContents = $configuredCaReadable ? @file_get_contents($caPath) : false;
$caCertificate = is_string($caContents) ? @openssl_x509_read($caContents) : false;

diagnostic('expected_ca_exists', file_exists($expectedCaPath));
diagnostic('expected_ca_readable', $expectedCaReadable);
diagnostic('configured_ca_set', $configuredCaPath !== '');
diagnostic('configured_ca_matches_expected', $configuredCaPath === $expectedCaPath);
diagnostic('configured_ca_readable', $configuredCaReadable);
diagnostic('configured_ca_bytes', is_string($caContents) ? strlen($caContents) : 0);
diagnostic('ca_x509_valid', $caCertificate !== false);

$host = trim((string) getenv('DB_HOST'));
$port = (int) (getenv('DB_PORT') ?: 3306);

diagnostic('db_host_set', $host !== '');
diagnostic('db_port_valid', $port > 0 && $port <= 65535);

$resolvedAddresses = $host !== '' ? @gethostbynamel($host) : false;
$dnsResolved = is_array($resolvedAddresses) && $resolvedAddresses !== [];

diagnostic('db_dns_resolved', $dnsResolved);
diagnostic('db_dns_address_count', $dnsResolved ? count($resolvedAddresses) : 0);

$tcpConnected = false;

if ($dnsResolved && $port > 0 && $port <= 65535) {
    $errorNumber = 0;
    $errorMessage = '';
    $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 5.0);

    if (is_resource($socket)) {
        $tcpConnected = true;
        fclose($socket);
    }
}

diagnostic('db_tcp_connected', $tcpConnected);

$database = trim((string) getenv('DB_DATABASE'));
$username = (string) getenv('DB_USERNAME');
$password = (string) getenv('DB_PASSWORD');
$credentialsConfigured = $database !== '' && $username !== '' && $password !== '';
$tlsVerification = filter_var(
    getenv('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ?: 'true',
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE,
);

diagnostic('db_credentials_configured', $credentialsConfigured);
diagnostic('tls_verification_enabled', $tlsVerification === true);

if (! extension_loaded('pdo_mysql')) {
    diagnostic('pdo_mysql', 'failed_extension_missing');
    exit(0);
}

if (! $host || ! $dnsResolved || ! $tcpConnected) {
    diagnostic('pdo_connection', 'skipped_network_unavailable');
    exit(0);
}

if (! $configuredCaReadable || $caCertificate === false) {
    diagnostic('pdo_connection', 'skipped_ca_invalid_or_unreadable');
    exit(0);
}

if (! $credentialsConfigured) {
    diagnostic('pdo_connection', 'skipped_credentials_missing');
    exit(0);
}

if ($tlsVerification !== true) {
    diagnostic('pdo_connection', 'skipped_tls_verification_disabled');
    exit(0);
}

try {
    new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
        $username,
        $password,
        [
            Pdo\Mysql::ATTR_SSL_CA => $caPath,
            Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ],
    );

    diagnostic('pdo_connection', 'verified_success');
} catch (Throwable $exception) {
    $message = strtolower($exception->getMessage());

    $category = match (true) {
        str_contains($message, 'ssl')
            || str_contains($message, 'tls')
            || str_contains($message, 'certificate')
            || str_contains($message, 'ca ') => 'tls_or_certificate_failure',
        str_contains($message, 'access denied')
            || str_contains($message, 'authentication') => 'authentication_or_permissions_failure',
        str_contains($message, 'timed out')
            || str_contains($message, 'refused')
            || str_contains($message, 'connect') => 'database_network_failure',
        default => 'pdo_connection_failure',
    };

    diagnostic('pdo_connection', $category);
}
