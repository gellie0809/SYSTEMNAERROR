<?php
/**
 * CAS Prediction API - Auto-Start Helper
 * Include this at the top of prediction pages to ensure API is running
 */

function ensureCASAPIRunning() {
    $port = 5003;
    $maxRetries = 3;
    $retryDelay = 2; // seconds
    
    // Check if API is already running
    for ($i = 0; $i < $maxRetries; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:$port/api/model/info");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return ['running' => true, 'message' => 'API is running'];
        }
        
        // If first attempt failed, try to start API
        if ($i == 0) {
            $apiPath = __DIR__ . '\prediction_cas';
            $batFile = $apiPath . '\start_api.bat';
            
            if (file_exists($batFile)) {
                // Start API in background
                $command = 'powershell.exe -Command "Start-Process -FilePath \'' . $batFile . '\' -WorkingDirectory \'' . $apiPath . '\' -WindowStyle Hidden"';
                exec($command);
                
                // Wait for API to start
                sleep($retryDelay);
            }
        } else {
            sleep($retryDelay);
        }
    }
    
    return ['running' => false, 'message' => 'Could not start API'];
}

// Auto-start when included
$apiStatus = ensureCASAPIRunning();
define('CAS_API_RUNNING', $apiStatus['running']);
define('CAS_API_MESSAGE', $apiStatus['message']);
?>
