<?php

declare(strict_types=1);

$allowedOrigins = explode(',', (string) getenv('ALLOWED_ORIGINS'));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-HTTP-Method-Override');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require
    file_exists(__DIR__ . '/../vendor/autoload.php')
        ? __DIR__ . '/../vendor/autoload.php'
        : __DIR__ . '/../src/App/AutoloadPSR4.php';

use G4\Api\App\Api;

$api = null;
try {
    $api = new Api();
    $api->processRequest()->sendResponse();
} catch (\Exception $e) {
    error_log('[API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error']);
    exit();
}
