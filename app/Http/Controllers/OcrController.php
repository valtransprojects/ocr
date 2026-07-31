<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OcrController extends Controller
{
    /**
     * Display the OCR Reader Page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ocr');
    }

    /**
     * Process the OCR request by forwarding base64 data to selected ANPR engine(s).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        try {
            $request->validate([
                'base64' => 'required|string',
                'engine' => 'nullable|string|in:candid,eydea,parkonic,anpr2,both,all',
            ]);

            $base64 = $request->input('base64');
            $engine = $request->input('engine', 'all');

            // Strip data URI prefix if present (e.g. data:image/jpeg;base64,)
            if (preg_match('/^data:image\/(\w+);base64,/', $base64)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }

            $token = env(
                'PARKONIC_API_TOKEN',
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJTTlAiLCJpYXQiOjE3MDM0MTUxOTcsImV4cCI6MTczNDk1MTE5NywiY2xpIjoiQVBJIiwid2lkIjpudWxsfQ.EIup6X0h65BjBEUMmE3BHxolQjH18lrMaCxvfoJ0_Nw'
            );

            $results = [];

            if ($engine === 'candid' || $engine === 'both' || $engine === 'all') {
                $results['candid'] = $this->queryCandid($base64, $token);
            }

            if ($engine === 'eydea' || $engine === 'both' || $engine === 'all') {
                $results['eydea'] = $this->queryEydea($base64, $token);
            }

            if ($engine === 'parkonic' || $engine === 'both' || $engine === 'all') {
                $results['parkonic'] = $this->queryParkonic($base64, $token);
            }

            if ($engine === 'anpr2' || $engine === 'both' || $engine === 'all') {
                $results['anpr2'] = $this->queryAnpr2($base64, $token);
            }

            return response()->json([
                'status' => true,
                'engine' => $engine,
                'results' => $results,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('OCR process global error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Query the Candid ANPR Engine.
     */
    private function queryCandid($base64, $token)
    {
        $url = env('CANDID_API_URL', 'http://10.10.102.55:5009/process');
        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(8)->post($url, [
                'base64' => $base64,
                'token' => $token,
            ]);

            $elapsed = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'time_ms' => $elapsed,
                ];
            }

            return [
                'success' => false,
                'message' => 'Candid API returned HTTP status ' . $response->status(),
                'time_ms' => $elapsed,
            ];

        } catch (\Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            Log::error('Candid ANPR Engine communication failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection to Candid engine failed or timed out: ' . $e->getMessage(),
                'time_ms' => $elapsed,
            ];
        }
    }

    /**
     * Query the Eydea ANPR Engine.
     */
    private function queryEydea($base64, $token)
    {
        $url = env('EYDEA_API_URL', 'http://10.10.102.56:5001/predict');
        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(8)->post($url, [
                'base64' => $base64,
                'token' => $token,
            ]);

            $elapsed = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'time_ms' => $elapsed,
                ];
            }

            return [
                'success' => false,
                'message' => 'Eydea API returned HTTP status ' . $response->status(),
                'time_ms' => $elapsed,
            ];

        } catch (\Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            Log::error('Eydea ANPR Engine communication failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection to Eydea engine failed or timed out: ' . $e->getMessage(),
                'time_ms' => $elapsed,
            ];
        }
    }

    /**
     * Query the Parkonic ANPR Engine.
     */
    private function queryParkonic($base64, $token)
    {
        $url = env('PARKONIC_API_URL', 'http://10.10.102.56/ParkonicJLT/anpr/engine/process_multi');
        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(12)->post($url, [
                'base64' => $base64,
                'token' => $token,
            ]);

            $elapsed = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                
                // Decode double-serialized JSON if present
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $decoded;
                    }
                }

                return [
                    'success' => true,
                    'data' => $data,
                    'time_ms' => $elapsed,
                ];
            }

            return [
                'success' => false,
                'message' => 'Parkonic API returned HTTP status ' . $response->status(),
                'time_ms' => $elapsed,
            ];

        } catch (\Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            Log::error('Parkonic ANPR Engine communication failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection to Parkonic engine failed or timed out: ' . $e->getMessage(),
                'time_ms' => $elapsed,
            ];
        }
    }

    /**
     * Query the ANPR 2 Engine.
     */
    private function queryAnpr2($base64, $token)
    {
        $url = 'https://anpr2.parkonic.cloud:8443/ParkonicJLT/anpr/engine/process_parkonic';
        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(12)->post($url, [
                'base64' => $base64,
                'token' => $token,
            ]);

            $elapsed = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                
                // Decode double-serialized JSON if present
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $decoded;
                    }
                }

                return [
                    'success' => true,
                    'data' => $data,
                    'time_ms' => $elapsed,
                ];
            }

            return [
                'success' => false,
                'message' => 'ANPR 2 API returned HTTP status ' . $response->status(),
                'time_ms' => $elapsed,
            ];

        } catch (\Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            Log::error('ANPR 2 Engine communication failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection to ANPR 2 engine failed or timed out: ' . $e->getMessage(),
                'time_ms' => $elapsed,
            ];
        }
    }
}
