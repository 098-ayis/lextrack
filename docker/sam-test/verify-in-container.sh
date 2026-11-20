#!/usr/bin/env bash
set -euo pipefail

php -r '
require "/var/www/html/vendor/autoload.php";
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = $app->make("db")->connection();
$database->getPdo();
if ($database->getDatabaseName() !== "lextrack_sam_test") {
    fwrite(STDERR, "Laravel database mismatch: ".$database->getDatabaseName().PHP_EOL);
    exit(1);
}
echo "Laravel database: ".$database->getDatabaseName().PHP_EOL;
'

vendor/bin/phpunit \
    -c /opt/sam-test/phpunit-isolation.xml \
    --testdox

echo "Isolation checks passed; no migration or seeder was run."
