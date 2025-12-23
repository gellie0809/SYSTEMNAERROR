<?php
// Test CCJE API Connection
header('Content-Type: application/json');

function testAPI($endpoint) {
    $url = "http://localhost:5001/api/" . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'endpoint' => $endpoint,
        'http_code' => $httpCode,
        'success' => $httpCode == 200,
        'error' => $error,
        'response' => $httpCode == 200 ? json_decode($response, true) : $response
    ];
}

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'health' => testAPI('health'),
        'status' => testAPI('status'),
        'predict' => testAPI('predict'),
        'model_info' => testAPI('model/info')
    ]
];

echo json_encode($results, JSON_PRETTY_PRINT);
?>
