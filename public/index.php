<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");

require file_exists(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../src/App/AutoloadPSR4.php';

use G4\Api\App\Api;

$api = null;
try {
    $api = new Api();
    $api->processRequest()->sendResponse();
} catch (\Exception $e) {
    if ($api !== null) {
        $api->setCode(500);
        $api->setResponse($e->getMessage());
        $api->sendResponse();
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($e->getMessage());
    exit;
}
