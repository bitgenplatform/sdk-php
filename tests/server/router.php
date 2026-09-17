<?php

declare(strict_types=1);

// Throwaway HTTP server for CurlTransportTest: `php -S 127.0.0.1:<port> tests/server/router.php`
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);

switch ($path) {
    case '/ok':
        header('Content-Type: application/json');
        echo json_encode(['method' => $_SERVER['REQUEST_METHOD'], 'headers' => getallheaders(), 'body' => file_get_contents('php://input')], JSON_THROW_ON_ERROR);
        return;
    case '/hang':
        sleep(1);
        header('Content-Type: application/json');
        echo '{"late":true}';
        return;
    case '/redirect':
        header('Location: /ok', true, 302);
        return;
    case '/empty':
        http_response_code(204);
        return;
    case '/text':
        header('Content-Type: text/plain');
        echo 'plain text';
        return;
    case '/error':
        http_response_code(412);
        header('Content-Type: application/json');
        echo '{"error":true,"message":"bank_rib_required","code":412}';
        return;
    default:
        http_response_code(500);
        echo 'Internal Server Error';
}
