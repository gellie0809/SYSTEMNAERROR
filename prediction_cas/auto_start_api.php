<?php
/**
 * Auto-Start CAS Prediction API Helper
 * This script checks if the API is running and starts it if needed
 */

function checkAPIStatus($port = 5003) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:$port/api/model/info");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

function startAPI() {
    $apiPath = __DIR__;
    $batFile = $apiPath . '\start_api.bat';
    
    if (!file_exists($batFile)) {
        return ['success' => false, 'error' => 'start_api.bat not found'];
    }
    
    // Start the API in background using PowerShell
    $command = 'powershell.exe -Command "Start-Process -FilePath \'' . $batFile . '\' -WorkingDirectory \'' . $apiPath . '\' -WindowStyle Hidden"';
    
    exec($command, $output, $returnCode);
    
    // Wait a few seconds for API to start
    sleep(3);
    
    // Check if it started successfully
    $isRunning = checkAPIStatus();
    
    return [
        'success' => $isRunning,
        'message' => $isRunning ? 'API started successfully' : 'API failed to start',
        'output' => $output
    ];
}

function getAPIStatus() {
    $isRunning = checkAPIStatus();
    
    return [
        'running' => $isRunning,
        'port' => 5003,
        'url' => 'http://localhost:5003'
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check':
            echo json_encode(getAPIStatus());
            break;
            
        case 'start':
            echo json_encode(startAPI());
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// If accessed directly, return status
header('Content-Type: application/json');
echo json_encode(getAPIStatus());
?>
