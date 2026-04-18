<?php

namespace App\Http\Controllers\Mcp;

use App\Mcp\Servers\ArabicaServer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Mcp\Server\Transport\HttpTransport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpSseController
{
    /**
     * MCP Endpoint dengan SSE support untuk Opencode
     * 
     * Opencode menggunakan Model Context Protocol dengan HTTP+SSE transport:
     * 1. Client connects via GET untuk mendapatkan SSE stream
     * 2. Server kirim 'endpoint' event dengan URL untuk POST messages
     * 3. Client POST messages ke endpoint tersebut
     * 4. Server responses via SSE 'message' events
     */
    public function __invoke(Request $request): Response|StreamedResponse
    {
        $sessionId = $request->header('MCP-Session-Id', uniqid('mcp_', true));
        
        // POST request - handle JSON-RPC message
        if ($request->isMethod('post')) {
            return $this->handleJsonRpc($request, $sessionId);
        }
        
        // GET request - establish SSE stream
        if ($request->isMethod('get')) {
            return $this->establishSseStream($sessionId);
        }
        
        return response('', 405)->header('Allow', 'GET, POST');
    }
    
    /**
     * Handle JSON-RPC POST request
     */
    protected function handleJsonRpc(Request $request, string $sessionId): Response
    {
        $transport = new HttpTransport($request, $sessionId);
        $server = new ArabicaServer($transport);
        
        $server->start();
        
        return $transport->run();
    }
    
    /**
     * Establish SSE stream for real-time communication
     */
    protected function establishSseStream(string $sessionId): StreamedResponse
    {
        return response()->stream(function () use ($sessionId) {
            // Send initial endpoint event
            echo "event: endpoint\n";
            echo "data: /mcp/arabica\n\n";
            
            // Send connected confirmation
            echo "event: connected\n";
            echo "data: " . json_encode([
                'sessionId' => $sessionId,
                'status' => 'connected',
                'protocolVersion' => '2024-11-05'
            ]) . "\n\n";
            
            ob_flush();
            flush();
            
            // Keep connection alive with heartbeat
            $counter = 0;
            while (true) {
                if (connection_aborted() !== 0) {
                    break;
                }
                
                // Send heartbeat every 15 seconds
                if ($counter % 15 === 0 && $counter > 0) {
                    echo "event: heartbeat\n";
                    echo "data: {}\n\n";
                    ob_flush();
                    flush();
                }
                
                $counter++;
                sleep(1);
                
                // Safety break after 5 minutes (300 seconds)
                if ($counter > 300) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'MCP-Session-Id' => $sessionId,
        ]);
    }
}
