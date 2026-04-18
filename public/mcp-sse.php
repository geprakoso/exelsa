<?php

/**
 * SSE Bridge untuk Laravel MCP
 * 
 * Konversi HTTP JSON-RPC ke Server-Sent Events
 */

$url = 'http://localhost:8000/mcp/arabica';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Send initial SSE connection ACK
echo "event: connected\n";
echo "data: {\"status\": \"connected\"}\n\n";
ob_flush();
flush();

// Read POST data from client
$input = file_get_contents('php://input');

if (!empty($input)) {
    // Forward request to Laravel MCP
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        echo "event: message\n";
        echo "data: " . $response . "\n\n";
    }
}

ob_flush();
flush();
