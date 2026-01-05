<?php

$operation = $_GET['operation'] ?? null;
if ($operation !== 'phone_call_api') {
    http_response_code(404);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$targetUrl = $scheme . '://' . $host . '/api/phone-calls/webhook';

$body = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

$forwardHeaders = [];
foreach ($headers as $name => $value) {
    $lower = strtolower($name);
    if (in_array($lower, ['host', 'content-length'], true)) {
        continue;
    }
    $forwardHeaders[] = $name . ': ' . $value;
}

$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
if ($forwardHeaders) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
}

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(502);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

http_response_code($statusCode ?: 200);
foreach (explode("\r\n", $rawHeaders) as $headerLine) {
    if (stripos($headerLine, 'HTTP/') === 0 || $headerLine === '') {
        continue;
    }
    if (stripos($headerLine, 'Transfer-Encoding:') === 0) {
        continue;
    }
    header($headerLine, false);
}

echo $responseBody;
