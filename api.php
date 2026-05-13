<?php
declare(strict_types=1);

// 載入 .env 檔案
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

header('Content-Type: application/json');

$allowedOrigin = getenv('ALLOWED_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$CLIENT_ID = getenv('TDX_CLIENT_ID');
$CLIENT_SECRET = getenv('TDX_CLIENT_SECRET');

if (!$CLIENT_ID || !$CLIENT_SECRET) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Missing TDX credentials in environment']);
    exit;
}

$cacheFile = __DIR__ . '/.tdx_token_cache.json';
$refreshMargin = 60;

function loadCachedToken(string $file): ?array {
    if (!file_exists($file)) return null;
    $json = @file_get_contents($file);
    if ($json === false) return null;
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['access_token']) || empty($data['expiry'])) return null;
    if (time() >= intval($data['expiry'])) return null;
    return $data;
}

function saveCachedToken(string $file, string $token, int $expiresIn, int $refreshMargin): array {
    $expiry = time() + max(0, intval($expiresIn) - $refreshMargin);
    $data = [
        'access_token' => $token,
        'expiry' => $expiry,
        'saved_at' => time()
    ];
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($data));
    rename($tmp, $file);
    return $data;
}

function fetchTokenFromTDX(string $clientId, string $clientSecret) {
    $url = 'https://tdx.transportdata.tw/auth/realms/TDXConnect/protocol/openid-connect/token';
    $postFields = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'cURL error: ' . $curlErr];
    }

    $data = json_decode($response, true);
    if ($httpCode !== 200 || !is_array($data) || empty($data['access_token'])) {
        return ['error' => 'TDX token error', 'http_code' => $httpCode, 'body' => $data];
    }

    return ['access_token' => $data['access_token'], 'expires_in' => $data['expires_in'] ?? 86400];
}

$action = $_GET['action'] ?? '';

if ($action !== 'token') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$cached = loadCachedToken($cacheFile);
if ($cached) {
    $remaining = intval($cached['expiry']) - time();
    echo json_encode([
        'success' => true,
        'access_token' => $cached['access_token'],
        'expires_in' => $remaining
    ]);
    exit;
}

$result = fetchTokenFromTDX($CLIENT_ID, $CLIENT_SECRET);
if (!empty($result['error'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => $result['error'],
        'detail' => $result['body'] ?? null
    ]);
    exit;
}

$saved = saveCachedToken($cacheFile, $result['access_token'], intval($result['expires_in']), $refreshMargin);
$remaining = intval($saved['expiry']) - time();
echo json_encode([
    'success' => true,
    'access_token' => $saved['access_token'],
    'expires_in' => $remaining
]);
?>