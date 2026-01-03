<?php
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$client = new \App\Services\RustDaemonClient();
try {
    $response = $client->call('ping');
    echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
