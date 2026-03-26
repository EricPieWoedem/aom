<?php

header('Content-Type: application/json');

$allowedOrigin = getenv('APP_ORIGIN') ?: '';
if (!empty($allowedOrigin) && isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../classes/PawaPay.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$amount = isset($input['amount']) ? trim((string) $input['amount']) : '';
$currency = isset($input['currency']) ? strtoupper(trim((string) $input['currency'])) : '';
$description = isset($input['description']) ? trim((string) $input['description']) : 'Coaching / Consulting Session';
$country = isset($input['country']) ? strtoupper(trim((string) $input['country'])) : '';
$phoneNumber = isset($input['phoneNumber']) ? trim((string) $input['phoneNumber']) : '';

$defaultCountry = getenv('PAWAPAY_DEFAULT_COUNTRY');
if ($country === '' && $defaultCountry !== false && $defaultCountry !== '') {
    $country = strtoupper(trim((string) $defaultCountry));
}

$phoneDigits = preg_replace('/\D/', '', $phoneNumber);

if ($country === '' && $phoneDigits === '') {
    http_response_code(422);
    echo json_encode([
        'error' => 'country or phoneNumber required',
        'hint' => 'With a fixed amount, PawaPay requires ISO country (e.g. CIV, GHA) or phoneNumber. Send JSON country, or set PAWAPAY_DEFAULT_COUNTRY, or phoneNumber.',
    ]);
    exit;
}

if ($country !== '' && strlen($country) !== 3) {
    http_response_code(422);
    echo json_encode(['error' => 'country must be a 3-letter ISO 3166-1 alpha-3 code (e.g. CIV)']);
    exit;
}

if ($amount === '' || !is_numeric($amount)) {
    http_response_code(422);
    echo json_encode(['error' => 'Amount must be numeric']);
    exit;
}

if ($currency === '' || strlen($currency) !== 3) {
    http_response_code(422);
    echo json_encode(['error' => 'Currency must be 3-letter code']);
    exit;
}

// PawaPay validates returnUrl strictly (typically requires HTTPS and a public URL).
// Local http://localhost/... is usually rejected. Use ngrok (or similar) and set:
//   PAWAPAY_RETURN_URL=https://YOUR_DOMAIN/pay/success.php
$returnUrlRaw = getenv('PAWAPAY_RETURN_URL');
$returnUrl = ($returnUrlRaw !== false && $returnUrlRaw !== '') ? (string) $returnUrlRaw : '';
// Strip wrapping quotes / whitespace (common copy-paste mistake from docs or terminals)
$returnUrl = trim($returnUrl, " \t\n\r\0\x0B'\"");
if ($returnUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptName = str_replace('\\', '/', (string) $scriptName);
    $basePath = dirname($scriptName, 2);
    $returnUrl = $scheme . '://' . $host . rtrim($basePath, '/') . '/success.php';
}

$returnUrl = trim($returnUrl);
if (filter_var($returnUrl, FILTER_VALIDATE_URL) === false) {
    http_response_code(422);
    $fromEnv = ($returnUrlRaw !== false && $returnUrlRaw !== '');
    echo json_encode([
        'error' => 'Invalid returnUrl',
        'hint' => 'Use one line, https only, path ends with success.php. In PowerShell (same window as php -S): $env:PAWAPAY_RETURN_URL = "https://YOUR-HOST.ngrok-free.app/witlevels_pawapay_php_sdk/success.php"',
        'returnUrlFromEnv' => $fromEnv,
        'returnUrlPreview' => substr($returnUrl, 0, 120),
    ]);
    exit;
}

$returnParts = parse_url($returnUrl);
$schemeLower = isset($returnParts['scheme']) ? strtolower((string) $returnParts['scheme']) : '';
$hostLower = isset($returnParts['host']) ? strtolower((string) $returnParts['host']) : '';
$isLocalHost = in_array($hostLower, ['localhost', '127.0.0.1'], true);
if ($schemeLower === 'http' && $isLocalHost) {
    http_response_code(422);
    echo json_encode([
        'error' => 'returnUrl cannot use http://localhost — PawaPay rejects it.',
        'hint' => 'Expose your PHP app with HTTPS (ngrok, Cloudflare Tunnel, etc.) and set PAWAPAY_RETURN_URL to https://.../witlevels_pawapay_php_sdk/success.php',
    ]);
    exit;
}

$metadata = null;
if (!empty($input['preferredProvider'])) {
    $pp = strtoupper(trim((string) $input['preferredProvider']));
    if (preg_match('/^[A-Z0-9_]+$/', $pp)) {
        $metadata = array(array('preferredProvider' => $pp));
    }
}

$pawapay = new PawaPay();
$result = $pawapay->createPaymentPage(
    $amount,
    $currency,
    $description,
    $returnUrl,
    $country !== '' ? $country : null,
    $phoneDigits !== '' ? $phoneDigits : null,
    $metadata
);

if (!empty($result['error'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create payment page',
        'details' => $result['error'],
    ]);
    exit;
}

$res = $result['result'];
if (is_object($res) && isset($res->status) && strtoupper((string) $res->status) === 'REJECTED') {
    $fr = isset($res->failureReason) ? $res->failureReason : null;
    http_response_code(502);
    echo json_encode([
        'error' => 'Payment page session rejected',
        'httpCode' => $result['http_code'],
        'result' => $res,
        'failureReason' => $fr,
    ]);
    exit;
}

if ($result['http_code'] < 200 || $result['http_code'] >= 300 || empty($result['redirect_url'])) {
    $failureReason = null;
    if (is_object($result['result']) && isset($result['result']->failureReason)) {
        $failureReason = $result['result']->failureReason;
    } elseif (is_array($result['result']) && isset($result['result']['failureReason'])) {
        $failureReason = $result['result']['failureReason'];
    }

    http_response_code(502);
    echo json_encode([
        'error' => 'Unexpected response from PawaPay',
        'httpCode' => $result['http_code'],
        'result' => $result['result'],
        'failureReason' => $failureReason,
    ]);
    exit;
}

echo json_encode([
    'redirectUrl' => $result['redirect_url'],
    'transactionId' => $result['transaction_id'],
    'depositId' => $result['deposit_id'],
]);
