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

function connectionFailureCategory(Throwable $exception): string
{
    $message = strtolower($exception->getMessage());

    return match (true) {
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
}

function diagnosticType(mixed $value): string
{
    return $value === null ? 'null' : get_debug_type($value);
}

function diagnosticDsnShape(string $dsn): string
{
    $hasSocket = str_contains($dsn, 'unix_socket=');
    $hasHost = str_contains($dsn, 'host=');
    $hasPort = str_contains($dsn, 'port=');
    $hasDatabase = str_contains($dsn, 'dbname=');
    $hasCharset = str_contains($dsn, 'charset=');

    return match (true) {
        $hasSocket && $hasDatabase => 'mysql_unix_socket_dbname',
        $hasHost && $hasPort && $hasDatabase && $hasCharset => 'mysql_host_port_dbname_charset',
        $hasHost && $hasPort && $hasDatabase => 'mysql_host_port_dbname',
        $hasHost && $hasDatabase => 'mysql_host_dbname',
        default => 'unexpected',
    };
}

$connectionPrerequisiteFailure = match (true) {
    ! $host || ! $dnsResolved || ! $tcpConnected => 'skipped_network_unavailable',
    ! $configuredCaReadable || $caCertificate === false => 'skipped_ca_invalid_or_unreadable',
    ! $credentialsConfigured => 'skipped_credentials_missing',
    $tlsVerification !== true => 'skipped_tls_verification_disabled',
    default => null,
};

if ($connectionPrerequisiteFailure !== null) {
    diagnostic('pdo_connection', $connectionPrerequisiteFailure);
    diagnostic('laravel_connection', $connectionPrerequisiteFailure);
    exit(0);
}

$directDsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $host,
    $port,
    $database,
);
$directPdoOptions = [
    Pdo\Mysql::ATTR_SSL_CA => $caPath,
    Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5,
];

diagnostic('direct_dsn_shape', diagnosticDsnShape($directDsn));
diagnostic('direct_dsn_network_shape', 'mysql_host_port_dbname');
diagnostic('direct_ssl_ca_option_present', array_key_exists(Pdo\Mysql::ATTR_SSL_CA, $directPdoOptions));
diagnostic('direct_ssl_ca_option_type', diagnosticType($directPdoOptions[Pdo\Mysql::ATTR_SSL_CA] ?? null));
diagnostic('direct_ssl_ca_option_readable', is_string($directPdoOptions[Pdo\Mysql::ATTR_SSL_CA] ?? null)
    && is_readable($directPdoOptions[Pdo\Mysql::ATTR_SSL_CA]));
diagnostic('direct_ssl_verify_option_present', array_key_exists(
    Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT,
    $directPdoOptions,
));
diagnostic('direct_ssl_verify_option_type', diagnosticType(
    $directPdoOptions[Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] ?? null,
));
diagnostic('direct_ssl_verify_option_true', ($directPdoOptions[Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] ?? null) === true);

try {
    new PDO(
        $directDsn,
        $username,
        $password,
        $directPdoOptions,
    );

    diagnostic('pdo_connection', 'verified_success');
} catch (Throwable $exception) {
    diagnostic('pdo_connection', connectionFailureCategory($exception));
}

if (method_exists(PDO::class, 'connect')) {
    try {
        PDO::connect(
            $directDsn,
            $username,
            $password,
            [
                ...$directPdoOptions,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        diagnostic('pdo_static_connect', 'verified_success');
    } catch (Throwable $exception) {
        diagnostic('pdo_static_connect', connectionFailureCategory($exception));
    }
} else {
    diagnostic('pdo_static_connect', 'not_available');
}

try {
    $laravelStage = 'load';
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $laravelStage = 'bootstrap';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    diagnostic('laravel_bootstrap', 'completed');

    try {
        $laravelStage = 'config';
        $dbManager = $app->make('db');
        $rawConfig = config('database.connections.mysql');
        $configurationMethod = new \ReflectionMethod($dbManager, 'configuration');
        $resolvedConfig = $configurationMethod->invoke($dbManager, 'mysql');
        $resolvedOptions = (new \Illuminate\Database\Connectors\MySqlConnector)
            ->getOptions($resolvedConfig);
        $connector = new \Illuminate\Database\Connectors\MySqlConnector;
        $dsnMethod = new \ReflectionMethod($connector, 'getDsn');
        $laravelDsn = $dsnMethod->invoke($connector, $resolvedConfig);

        $resolvedCa = $resolvedOptions[Pdo\Mysql::ATTR_SSL_CA] ?? null;
        $resolvedVerify = $resolvedOptions[Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] ?? null;
        $resolvedSocket = trim((string) ($resolvedConfig['unix_socket'] ?? ''));

        diagnostic('laravel_driver_is_mysql', ($resolvedConfig['driver'] ?? null) === 'mysql');
        diagnostic('laravel_config_cached', $app->configurationIsCached());
        diagnostic('laravel_url_set', is_array($rawConfig)
            && trim((string) ($rawConfig['url'] ?? '')) !== '');
        diagnostic('laravel_host_set', trim((string) ($resolvedConfig['host'] ?? '')) !== '');
        diagnostic('laravel_port_valid', (int) ($resolvedConfig['port'] ?? 0) > 0
            && (int) ($resolvedConfig['port'] ?? 0) <= 65535);
        diagnostic('laravel_database_set', trim((string) ($resolvedConfig['database'] ?? '')) !== '');
        diagnostic('laravel_username_set', trim((string) ($resolvedConfig['username'] ?? '')) !== '');
        diagnostic('laravel_password_set', ($resolvedConfig['password'] ?? '') !== '');
        diagnostic('laravel_unix_socket_set', $resolvedSocket !== '');
        diagnostic('laravel_dsn_shape', diagnosticDsnShape((string) $laravelDsn));
        diagnostic('laravel_dsn_network_shape_matches_direct', diagnosticDsnShape((string) $laravelDsn)
            === 'mysql_host_port_dbname');
        diagnostic('laravel_host_matches_env', (string) ($resolvedConfig['host'] ?? '') === $host);
        diagnostic('laravel_port_matches_env', (string) ($resolvedConfig['port'] ?? '') === (string) $port);
        diagnostic('laravel_database_matches_env', (string) ($resolvedConfig['database'] ?? '') === $database);
        diagnostic('laravel_ssl_ca_option_present', array_key_exists(Pdo\Mysql::ATTR_SSL_CA, $resolvedOptions));
        diagnostic('laravel_ssl_ca_option_type', diagnosticType($resolvedCa));
        diagnostic('laravel_ssl_ca_option_readable', is_string($resolvedCa) && is_readable($resolvedCa));
        diagnostic('laravel_ssl_ca_matches_configured', is_string($resolvedCa)
            && $configuredCaPath !== '' && $resolvedCa === $configuredCaPath);
        diagnostic('laravel_ssl_ca_matches_direct', is_string($resolvedCa)
            && $resolvedCa === $caPath);
        diagnostic('laravel_ssl_verify_option_present', array_key_exists(
            Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT,
            $resolvedOptions,
        ));
        diagnostic('laravel_ssl_verify_option_type', diagnosticType($resolvedVerify));
        diagnostic('laravel_ssl_verify_option_true', $resolvedVerify === true);
        diagnostic('laravel_default_options_merged', array_key_exists(PDO::ATTR_EMULATE_PREPARES, $resolvedOptions)
            && array_key_exists(PDO::ATTR_ERRMODE, $resolvedOptions));
        diagnostic('laravel_option_count', count($resolvedOptions));

        try {
            $connector->connect($resolvedConfig);
            diagnostic('laravel_connector_connection', 'verified_success');
        } catch (Throwable $exception) {
            diagnostic('laravel_connector_connection', connectionFailureCategory($exception));
        }
    } catch (Throwable) {
        diagnostic('laravel_config_comparison', 'unavailable');
    }

    $laravelStage = 'query';
    \Illuminate\Support\Facades\DB::connection('mysql')->select('SELECT 1');

    diagnostic('laravel_query_result', 'select_1_completed');
    diagnostic('laravel_connection', 'verified_success');
} catch (Throwable $exception) {
    diagnostic('laravel_failure_stage', $laravelStage ?? 'unknown');
    diagnostic('laravel_connection', connectionFailureCategory($exception));
}
