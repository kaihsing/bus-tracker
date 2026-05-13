<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 你的 TDX 憑證（只存在伺服器端，瀏覽器看不到）
$CLIENT_ID = 'kaihsing-561392ff-c837-4911';
$CLIENT_SECRET = 'b00759f1-166d-41ca-8cbc-c9a55503350c';

// 取得 access token
function getToken($clientId, $clientSecret) {
    $url = 'https://tdx.transportdata.tw/auth/realms/TDXConnect/protocol/openid-connect/token';
    
    $data = [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

// 處理請求
$action = $_GET['action'] ?? '';

if ($action === 'token') {
    $result = getToken($CLIENT_ID, $CLIENT_SECRET);
    if ($result) {
        echo json_encode([
            'success' => true,
            'access_token' => $result['access_token'],
            'expires_in' => $result['expires_in']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to get token']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>