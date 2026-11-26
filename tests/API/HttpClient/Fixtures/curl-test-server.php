<?php

/**
 * Router for the PHP built-in web server that CurlHttpClientTest starts.
 * Each path answers with a fixed response and echoes nothing from the request.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/v1/ok':
        header('Content-Type: application/json');
        echo '{"id":"checkout_123","resource":"checkout"}';

        break;
    case '/v1/no-content':
        http_response_code(204);

        break;
    case '/v1/validation':
        http_response_code(422);
        header('Content-Type: application/json');
        echo '{"message":"The email field must be a valid email address.","errors":{"email":["The email field must be a valid email address."]}}';

        break;
    case '/v1/rate-limited':
        http_response_code(429);
        header('Retry-After: 30');
        header('Content-Type: application/json');
        echo '{"message":"Too Many Attempts."}';

        break;
    case '/v1/unauthorized-empty':
        http_response_code(401);

        break;
    case '/v1/bad-gateway':
        http_response_code(502);
        header('Content-Type: text/html');
        echo '<html><body>Bad Gateway</body></html>';

        break;
    case '/v1/not-json':
        echo '<html>Maintenance</html>';

        break;
    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo '{"message":"Not found."}';
}
