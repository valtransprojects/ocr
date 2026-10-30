<?php

namespace App\Http\Controllers;

use App\Models\OcrComparison;
use App\Models\OcrComparisonCharacter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
     * Display the Batch / Back-to-Back Upload & Database Persistence Page.
     *
     * @return \Illuminate\View\View
     */
    public function batchIndex()
    {
        $stats = $this->getLogicStats();
        return view('ocr-batch', compact('stats'));
    }

    /**
     * Process a single image from the Batch / Back-to-Back queue,
     * save the physical image to plateImages/, extract image metadata,
     * query the ANPR engines, and persist everything in ocr_comparisons.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchProcess(Request $request)
    {
        try {
            $request->validate([
                'base64' => 'required|string',
                'engine' => 'nullable|string|in:candid,eydea,parkonic,anpr2,both,all',
                'original_filename' => 'nullable|string|max:255',
                'batch_id' => 'nullable|string|max:64',
            ]);

            $rawBase64Input = $request->input('base64');
            $engine = $request->input('engine', 'all');
            $originalFilename = $request->input('original_filename') ?: ('upload_' . date('His') . '.jpg');
            $batchId = $request->input('batch_id');

            $detectedMime = 'image/jpeg';
            $base64 = $rawBase64Input;

            if (preg_match('/^data:(image\/[\w\+\-\.]+);base64,/', $rawBase64Input, $matches)) {
                $detectedMime = strtolower($matches[1]);
                $base64 = substr($rawBase64Input, strpos($rawBase64Input, ',') + 1);
            }

            $binaryData = base64_decode($base64, true);
            if ($binaryData === false || strlen($binaryData) === 0) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid base64 image payload.',
                ], 422);
            }

            // Extract image metadata (width, height, mime)
            $imageWidth = null;
            $imageHeight = null;
            $imageInfo = @getimagesizefromstring($binaryData);
            if (is_array($imageInfo)) {
                $imageWidth = $imageInfo[0] ?? null;
                $imageHeight = $imageInfo[1] ?? null;
                if (!empty($imageInfo['mime'])) {
                    $detectedMime = strtolower($imageInfo['mime']);
                }
            }

            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
            ];

            $origExt = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $extension = $mimeToExt[$detectedMime] ?? ($origExt ?: 'jpg');

            $fileSizeBytes = strlen($binaryData);
            $fileHash = hash('sha256', $binaryData);
            $plateImagesDir = base_path('plateImages');

            if (!File::exists($plateImagesDir)) {
                File::makeDirectory($plateImagesDir, 0755, true);
            }

            // Query ANPR Engines
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

            $candidSummary = $this->extractCandidSummary($results['candid'] ?? null);
            $eydeaSummary = $this->extractEydeaSummary($results['eydea'] ?? null);
            $arhSummary = $this->extractArhSummary($results['parkonic'] ?? null);
            $parkonicSummary = $this->extractParkonicEngineSummary($results['anpr2'] ?? null);

            $bestPicks = $this->computeBestPicks([
                'candid' => $candidSummary,
                'eydea' => $eydeaSummary,
                'arh' => $arhSummary,
                'parkonic' => $parkonicSummary,
            ]);

            $currentLogic = $this->computeCurrentLogic([
                'candid' => $candidSummary,
                'eydea' => $eydeaSummary,
                'arh' => $arhSummary,
                'parkonic' => $parkonicSummary,
            ]);

            $record = OcrComparison::create([
                'batch_id' => $batchId,
                'original_filename' => $originalFilename,
                'stored_filename' => 'pending',
                'image_path' => 'pending',
                'mime_type' => $detectedMime,
                'file_extension' => $extension,
                'file_size_bytes' => $fileSizeBytes,
                'image_width' => $imageWidth,
                'image_height' => $imageHeight,
                'file_hash' => $fileHash,
                'selected_engine' => $engine,

                // Best Picks Across 4 OCR Engines
                'best_plate' => $bestPicks['best_plate'],
                'best_region' => $bestPicks['best_region'],
                'best_confidence' => $bestPicks['best_confidence'],
                'best_characters' => $bestPicks['best_characters'],
                'best_characters_json' => $bestPicks['best_characters_json'],
                'best_make' => $bestPicks['best_make'],
                'best_model' => $bestPicks['best_model'],
                'best_generation' => $bestPicks['best_generation'],
                'best_vehicle' => $bestPicks['best_vehicle'],
                'best_category' => $bestPicks['best_category'],
                'best_color' => $bestPicks['best_color'],
                'best_plate_color' => $bestPicks['best_plate_color'],
                'best_orientation' => $bestPicks['best_orientation'],
                'best_quality' => $bestPicks['best_quality'],
                'best_summary_json' => $bestPicks['best_summary_json'],

                // Current Logic (Strict 95% threshold + Highest Average)
                'current_logic_engine' => $currentLogic['selected_engine'] ?? $currentLogic['candidate_engine'] ?? null,
                'current_logic_plate' => $currentLogic['selected_plate'] ?? $currentLogic['candidate_plate'] ?? null,
                'current_logic_confidence' => $currentLogic['selected_confidence'] ?? $currentLogic['candidate_confidence'] ?? null,
                'current_logic_status' => $currentLogic['status'] ?? null,
                'current_logic_json' => $currentLogic,

                // 1. Candid
                'candid_plate' => $candidSummary['plate'],
                'candid_region' => $candidSummary['region'],
                'candid_confidence' => $candidSummary['confidence'],
                'candid_characters' => $candidSummary['characters_text'],
                'candid_characters_json' => $candidSummary['characters_list'],
                'candid_vehicle' => $candidSummary['vehicle'],
                'candid_make' => $candidSummary['make'],
                'candid_model' => $candidSummary['model'],
                'candid_generation' => $candidSummary['generation'],
                'candid_category' => $candidSummary['category'],
                'candid_color' => $candidSummary['color'],
                'candid_plate_color' => $candidSummary['plate_color'],
                'candid_local_text' => $candidSummary['local_text'],
                'candid_bbox' => $candidSummary['bbox'],
                'candid_viewport' => $candidSummary['viewport'],
                'candid_quality' => $candidSummary['quality'],
                'candid_tags' => $candidSummary['tags'],
                'candid_time_ms' => $candidSummary['time_ms'],
                'candid_success' => $candidSummary['success'],
                'candid_raw' => $results['candid'] ?? null,
                'candid_details_json' => $candidSummary['details_json'],

                // 2. Eydea
                'eydea_plate' => $eydeaSummary['plate'],
                'eydea_region' => $eydeaSummary['region'],
                'eydea_confidence' => $eydeaSummary['confidence'],
                'eydea_characters' => $eydeaSummary['characters_text'],
                'eydea_characters_json' => $eydeaSummary['characters_list'],
                'eydea_vehicle' => $eydeaSummary['vehicle'],
                'eydea_make' => $eydeaSummary['make'],
                'eydea_model' => $eydeaSummary['model'],
                'eydea_generation' => $eydeaSummary['generation'],
                'eydea_category' => $eydeaSummary['category'],
                'eydea_color' => $eydeaSummary['color'],
                'eydea_plate_color' => $eydeaSummary['plate_color'],
                'eydea_local_text' => $eydeaSummary['local_text'],
                'eydea_bbox' => $eydeaSummary['bbox'],
                'eydea_viewport' => $eydeaSummary['viewport'],
                'eydea_quality' => $eydeaSummary['quality'],
                'eydea_tags' => $eydeaSummary['tags'],
                'eydea_time_ms' => $eydeaSummary['time_ms'],
                'eydea_success' => $eydeaSummary['success'],
                'eydea_raw' => $results['eydea'] ?? null,
                'eydea_details_json' => $eydeaSummary['details_json'],

                // 3. ARH
                'arh_plate' => $arhSummary['plate'],
                'arh_region' => $arhSummary['region'],
                'arh_confidence' => $arhSummary['confidence'],
                'arh_characters' => $arhSummary['characters_text'],
                'arh_characters_json' => $arhSummary['characters_list'],
                'arh_vehicle' => $arhSummary['vehicle'],
                'arh_make' => $arhSummary['make'],
                'arh_model' => $arhSummary['model'],
                'arh_generation' => $arhSummary['generation'],
                'arh_category' => $arhSummary['category'],
                'arh_color' => $arhSummary['color'],
                'arh_plate_color' => $arhSummary['plate_color'],
                'arh_local_text' => $arhSummary['local_text'],
                'arh_bbox' => $arhSummary['bbox'],
                'arh_viewport' => $arhSummary['viewport'],
                'arh_quality' => $arhSummary['quality'],
                'arh_tags' => $arhSummary['tags'],
                'arh_time_ms' => $arhSummary['time_ms'],
                'arh_success' => $arhSummary['success'],
                'arh_raw' => $results['parkonic'] ?? null,
                'arh_details_json' => $arhSummary['details_json'],

                // 4. Parkonic Engine
                'parkonic_plate' => $parkonicSummary['plate'],
                'parkonic_region' => $parkonicSummary['region'],
                'parkonic_confidence' => $parkonicSummary['confidence'],
                'parkonic_characters' => $parkonicSummary['characters_text'],
                'parkonic_characters_json' => $parkonicSummary['characters_list'],
                'parkonic_vehicle' => $parkonicSummary['vehicle'],
                'parkonic_make' => $parkonicSummary['make'],
                'parkonic_model' => $parkonicSummary['model'],
                'parkonic_generation' => $parkonicSummary['generation'],
                'parkonic_category' => $parkonicSummary['category'],
                'parkonic_color' => $parkonicSummary['color'],
                'parkonic_plate_color' => $parkonicSummary['plate_color'],
                'parkonic_local_text' => $parkonicSummary['local_text'],
                'parkonic_bbox' => $parkonicSummary['bbox'],
                'parkonic_viewport' => $parkonicSummary['viewport'],
                'parkonic_quality' => $parkonicSummary['quality'],
                'parkonic_tags' => $parkonicSummary['tags'],
                'parkonic_time_ms' => $parkonicSummary['time_ms'],
                'parkonic_success' => $parkonicSummary['success'],
                'parkonic_raw' => $results['anpr2'] ?? null,
                'parkonic_details_json' => $parkonicSummary['details_json'],
            ]);

            // Save file using uploaded name first + db ID: currentname_pi_id.jpg
            $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
            $sanitizedBase = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '_', $baseName);
            $sanitizedBase = trim($sanitizedBase);
            if ($sanitizedBase === '') {
                $sanitizedBase = 'upload';
            }
            $storedFilename = $sanitizedBase . '_pi_' . $record->id . '.' . $extension;
            $relativePath = 'plateImages/' . $storedFilename;
            $fullPath = $plateImagesDir . DIRECTORY_SEPARATOR . $storedFilename;
            File::put($fullPath, $binaryData);

            $record->update([
                'stored_filename' => $storedFilename,
                'image_path' => $relativePath,
            ]);

            // Insert character-wise rows into ocr_comparison_characters
            $charRows = [];
            $now = now();
            $engineCharGroups = [
                'candid' => $candidSummary['characters_list'] ?? [],
                'eydea' => $eydeaSummary['characters_list'] ?? [],
                'arh' => $arhSummary['characters_list'] ?? [],
                'parkonic' => $parkonicSummary['characters_list'] ?? [],
            ];

            foreach ($engineCharGroups as $engineName => $charsList) {
                foreach ($charsList as $cItem) {
                    $charRows[] = [
                        'ocr_comparison_id' => $record->id,
                        'engine' => $engineName,
                        'plate_index' => (int) ($cItem['plate_index'] ?? 0),
                        'char_position' => (int) ($cItem['position'] ?? 1),
                        'character' => substr((string) ($cItem['character'] ?? ''), 0, 16),
                        'confidence' => isset($cItem['confidence']) && $cItem['confidence'] !== '' ? (string) $cItem['confidence'] : null,
                        'raw_char_data' => isset($cItem['raw']) ? json_encode($cItem['raw']) : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($charRows)) {
                OcrComparisonCharacter::insert($charRows);
            }

            $record->load('characters');

            return response()->json([
                'status' => true,
                'engine' => $engine,
                'results' => $results,
                'record' => $record,
                'best_picks' => $bestPicks,
                'current_logic' => $currentLogic,
                'stats' => $this->getLogicStats(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('OCR batchProcess error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Fetch saved OCR comparison records from the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchRecords(Request $request)
    {
        try {
            $query = OcrComparison::with('characters')
                ->orderByRaw("CASE WHEN accepted_logic IS NULL OR accepted_logic = '' OR accepted_logic = 'none' THEN 0 ELSE 1 END ASC")
                ->orderByDesc('id');

            if ($status = trim((string) $request->query('status', $request->query('filter', '')))) {
                if ($status === 'pending') {
                    $query->where(function ($q) {
                        $q->whereNull('accepted_logic')
                            ->orWhere('accepted_logic', 'none')
                            ->orWhere('accepted_logic', '');
                    });
                } elseif ($status === 'current_logic') {
                    $query->where('accepted_logic', 'current_logic');
                } elseif ($status === 'best_pick') {
                    $query->where('accepted_logic', 'best_pick');
                } elseif ($status === 'reject_all' || $status === 'rejected') {
                    $query->whereIn('accepted_logic', ['reject_all', 'rejected']);
                } elseif ($status === 'decided') {
                    $query->whereNotNull('accepted_logic')
                        ->whereNotIn('accepted_logic', ['none', '']);
                }
            }

            if ($search = trim((string) $request->query('search', ''))) {
                $query->where(function ($q) use ($search) {
                    $q->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('stored_filename', 'like', "%{$search}%")
                        ->orWhere('best_plate', 'like', "%{$search}%")
                        ->orWhere('best_region', 'like', "%{$search}%")
                        ->orWhere('best_make', 'like', "%{$search}%")
                        ->orWhere('best_model', 'like', "%{$search}%")
                        ->orWhere('best_color', 'like', "%{$search}%")
                        ->orWhere('best_orientation', 'like', "%{$search}%")
                        ->orWhere('best_category', 'like', "%{$search}%")
                        ->orWhere('candid_plate', 'like', "%{$search}%")
                        ->orWhere('eydea_plate', 'like', "%{$search}%")
                        ->orWhere('arh_plate', 'like', "%{$search}%")
                        ->orWhere('parkonic_plate', 'like', "%{$search}%")
                        ->orWhere('accepted_logic', 'like', "%{$search}%")
                        ->orWhere('batch_id', 'like', "%{$search}%");
                });
            }

            $perPage = min(max((int) $request->query('per_page', 25), 5), 100);
            $paginated = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'records' => $paginated->items(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
                'stats' => $this->getLogicStats(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OCR batchRecords error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept / Confirm chosen logic (current_logic or best_pick) for an OCR comparison record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptLogic(Request $request, $id)
    {
        try {
            $record = OcrComparison::find($id);
            if (!$record) {
                return response()->json([
                    'status' => false,
                    'error' => 'Record not found',
                ], 404);
            }

            $logic = $request->input('logic', $request->input('accepted_logic', null));
            if (!in_array($logic, ['current_logic', 'best_pick', 'reject_all', 'rejected', 'none', null], true)) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid logic specified. Must be current_logic, best_pick, reject_all, or none.',
                ], 422);
            }

            if ($logic === 'rejected') {
                $logic = 'reject_all';
            } elseif ($logic === 'none') {
                $logic = null;
            }

            $record->accepted_logic = $logic;
            $record->save();

            return response()->json([
                'status' => true,
                'message' => $logic ? "Successfully accepted {$logic}" : "Accepted logic cleared",
                'record_id' => $record->id,
                'accepted_logic' => $record->accepted_logic,
                'stats' => $this->getLogicStats(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OCR acceptLogic error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete an OCR comparison record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRecord($id)
    {
        try {
            $record = OcrComparison::find($id);
            if (!$record) {
                return response()->json([
                    'status' => false,
                    'error' => 'Record not found or already deleted',
                ], 404);
            }

            // Perform Soft Delete
            $record->delete();

            return response()->json([
                'status' => true,
                'message' => "Record #{$id} soft deleted successfully",
                'record_id' => (int) $id,
                'stats' => $this->getLogicStats(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OCR deleteRecord error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compute aggregate statistics on accepted logic decisions across all comparison records.
     *
     * @return array
     */
    protected function getLogicStats()
    {
        try {
            $logicCounts = OcrComparison::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN accepted_logic = 'current_logic' THEN 1 ELSE 0 END) as current_logic_count,
                SUM(CASE WHEN accepted_logic = 'best_pick' THEN 1 ELSE 0 END) as best_pick_count,
                SUM(CASE WHEN accepted_logic IN ('reject_all', 'rejected') THEN 1 ELSE 0 END) as reject_all_count,
                SUM(CASE WHEN accepted_logic IS NULL OR accepted_logic = 'none' THEN 1 ELSE 0 END) as pending_count
            ")->first();

            $totalAll = (int) ($logicCounts->total ?? 0);
            $currentCount = (int) ($logicCounts->current_logic_count ?? 0);
            $bestCount = (int) ($logicCounts->best_pick_count ?? 0);
            $rejectCount = (int) ($logicCounts->reject_all_count ?? 0);
            $pendingCount = (int) ($logicCounts->pending_count ?? 0);

            // Picks comparison: total decided picks between Current Logic and Best Pick
            $decidedPicks = $currentCount + $bestCount;
            $allDecided = $decidedPicks + $rejectCount;

            $currentPct = $decidedPicks > 0 ? round(($currentCount / $decidedPicks) * 100, 1) : 0.0;
            $bestPct = $decidedPicks > 0 ? round(($bestCount / $decidedPicks) * 100, 1) : 0.0;
            $currentPctTotal = $totalAll > 0 ? round(($currentCount / $totalAll) * 100, 1) : 0.0;
            $bestPctTotal = $totalAll > 0 ? round(($bestCount / $totalAll) * 100, 1) : 0.0;

            return [
                'total' => $totalAll,
                'current_logic_count' => $currentCount,
                'current_logic_pct' => $currentPct,
                'current_logic_pct_total' => $currentPctTotal,
                'best_pick_count' => $bestCount,
                'best_pick_pct' => $bestPct,
                'best_pick_pct_total' => $bestPctTotal,
                'reject_all_count' => $rejectCount,
                'pending_count' => $pendingCount,
                'decided_picks' => $decidedPicks,
                'all_decided' => $allDecided,
            ];
        } catch (\Throwable $e) {
            Log::warning('getLogicStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'current_logic_count' => 0,
                'current_logic_pct' => 0.0,
                'current_logic_pct_total' => 0.0,
                'best_pick_count' => 0,
                'best_pick_pct' => 0.0,
                'best_pick_pct_total' => 0.0,
                'reject_all_count' => 0,
                'pending_count' => 0,
                'decided_picks' => 0,
                'all_decided' => 0,
            ];
        }
    }

    /**
     * Stream a saved image from the root plateImages directory.
     *
     * @param  string  $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function serveImage($filename)
    {
        $safeName = basename($filename);
        $path = base_path('plateImages' . DIRECTORY_SEPARATOR . $safeName);

        if (!File::exists($path)) {
            abort(404, 'Plate image not found.');
        }

        $mime = File::mimeType($path) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
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

    /**
     * Convert 0..1 probabilities to 0..100 percentage scale WITHOUT rounding off decimals
     * (e.g. 0.97666 => "97.666", 91.666 => "91.666", 100 => "100").
     */
    private function normalizeConfidence($val): ?string
    {
        if ($val === null || $val === '' || !is_numeric($val)) {
            return null;
        }
        $num = (float) $val;
        if ($num > 0.0 && $num <= 1.0) {
            return rtrim(rtrim(sprintf('%.10f', $num * 100.0), '0'), '.');
        }
        return (string) $val;
    }

    /**
     * Helper to format a value with exact confidence value without rounding.
     */
    private function formatLabelWithConf($label, $conf): ?string
    {
        $clean = trim((string) ($label ?? ''));
        if ($clean === '' || strtoupper($clean) === 'N/A') {
            return null;
        }
        $exactConf = $this->normalizeConfidence($conf);
        if ($exactConf !== null) {
            return $clean . ' (' . $exactConf . '%)';
        }
        return $clean;
    }

    /**
     * Extract vehicle tags as a comma-separated string (active tags first, or verified negative summary).
     */
    private function extractTagsText($tags): ?string
    {
        if (empty($tags) || !is_array($tags)) {
            return null;
        }
        $active = [];
        $allChecked = [];
        foreach ($tags as $tagKey => $tagData) {
            if (is_array($tagData)) {
                $label = ucwords(str_replace('_', ' ', (string) $tagKey));
                $conf = $tagData['confidence'] ?? $tagData['conf'] ?? null;
                $val = $tagData['value'] ?? null;
                if ($val === 'yes' || $val === true) {
                    $active[] = $this->formatLabelWithConf($label, $conf) ?: $label;
                } elseif ($val === 'no' || $val === false) {
                    $allChecked[] = $label . ': No' . ($conf !== null ? ' (' . $this->normalizeConfidence($conf) . '%)' : '');
                }
            }
        }
        if (!empty($active)) {
            return implode(', ', $active);
        }
        if (!empty($allChecked)) {
            return 'Standard (' . implode(', ', $allChecked) . ')';
        }
        return null;
    }

    /**
     * Format a 4-point polygon or corner array into a compact bounding box string.
     */
    private function formatCornersBbox($corners): ?string
    {
        if (empty($corners) || !is_array($corners)) {
            return null;
        }
        $xs = [];
        $ys = [];
        foreach ($corners as $pt) {
            if (is_array($pt) && isset($pt[0], $pt[1]) && is_numeric($pt[0]) && is_numeric($pt[1])) {
                $xs[] = round((float) $pt[0]);
                $ys[] = round((float) $pt[1]);
            }
        }
        if (empty($xs) || empty($ys)) {
            return null;
        }
        $minX = min($xs);
        $minY = min($ys);
        $maxX = max($xs);
        $maxY = max($ys);
        $w = $maxX - $minX;
        $h = $maxY - $minY;
        return "[{$minX},{$minY} → {$maxX},{$maxY}] ({$w}×{$h}px)";
    }

    /**
     * Base empty summary structure for an engine.
     */
    private function emptyEngineSummary($timeMs = null): array
    {
        return [
            'plate' => null,
            'region' => null,
            'confidence' => null,
            'characters_text' => null,
            'characters_list' => [],
            'vehicle' => null,
            'make' => null,
            'model' => null,
            'generation' => null,
            'category' => null,
            'color' => null,
            'plate_color' => null,
            'local_text' => null,
            'bbox' => null,
            'viewport' => null,
            'quality' => null,
            'tags' => null,
            'time_ms' => $timeMs,
            'success' => false,
            'details_json' => [],
        ];
    }

    /**
     * Normalize Candid result for all database columns + character-wise list.
     */
    public function extractCandidSummary(?array $candid): array
    {
        $summary = $this->emptyEngineSummary($candid['time_ms'] ?? null);

        if (!$candid || empty($candid['success']) || empty($candid['data']['plates'][0])) {
            return $summary;
        }

        $plate = $candid['data']['plates'][0];
        $summary['success'] = true;
        $summary['plate'] = isset($plate['text']) ? substr(trim((string) $plate['text']), 0, 64) : null;

        // Local / Arabic text & text groups
        $localParts = [];
        $localText = trim((string) ($plate['local_text'] ?? ''));
        if ($localText === '' && !empty($plate['local_text_groups']) && is_array($plate['local_text_groups'])) {
            $localText = trim(implode(' ', $plate['local_text_groups']));
        }
        if ($localText !== '') {
            $localParts[] = $localText;
        }
        if (!empty($plate['text_groups']) && is_array($plate['text_groups'])) {
            $localParts[] = 'Groups: [' . implode(' | ', $plate['text_groups']) . ']';
        }
        $summary['local_text'] = !empty($localParts) ? implode(' | ', $localParts) : null;

        $catConf = $this->normalizeConfidence($plate['category_confidence'] ?? $plate['category']['confidence'] ?? null);

        if (!empty($plate['category']) && is_array($plate['category'])) {
            $city = trim((string) ($plate['category']['city'] ?? ''));
            $country = trim((string) ($plate['category']['country'] ?? ''));
            if ($city !== '' && strcasecmp($city, $country) === 0) {
                $reg = $city;
            } else {
                $reg = trim($city . ' ' . $country);
            }
            if (!empty($plate['category']['description'])) {
                $reg .= ' (' . $plate['category']['description'] . ')';
            }
            if ($reg !== '' && $catConf !== null) {
                $reg .= ' (' . $catConf . '%)';
            }
            $summary['region'] = substr(trim($reg), 0, 191) ?: null;

            // Plate Type, Color & Plate Size
            $pDesc = trim((string) ($plate['category']['description'] ?? ''));
            $pColor = trim((string) ($plate['category']['colour'] ?? $plate['category']['color'] ?? ''));
            $pSize = trim((string) ($plate['category']['size'] ?? ''));
            $plateColorParts = [];
            if ($pDesc !== '') {
                $plateColorParts[] = 'Type: ' . $pDesc;
            }
            if ($pColor !== '') {
                $plateColorParts[] = 'Color: ' . $pColor;
            }
            if ($pSize !== '') {
                $plateColorParts[] = 'Size: ' . $pSize;
            }
            if (!empty($plateColorParts)) {
                $pColStr = implode(' | ', $plateColorParts);
                if ($catConf !== null) {
                    $pColStr .= ' (' . $catConf . '%)';
                }
                $summary['plate_color'] = $pColStr;
            }
        }

        $summary['confidence'] = $this->normalizeConfidence($plate['confidence'] ?? $plate['confidance'] ?? null);

        // Character-wise extraction
        $rawChars = $plate['characters'] ?? $plate['plate_detail'] ?? [];
        if (is_array($rawChars) && count($rawChars) > 0) {
            $charTextParts = [];
            $charList = [];
            foreach (array_values($rawChars) as $idx => $c) {
                $ch = (string) ($c['character'] ?? '');
                $cConf = $this->normalizeConfidence($c['confidence'] ?? $c['conf'] ?? null);
                $charList[] = [
                    'plate_index' => 0,
                    'position' => $idx + 1,
                    'character' => $ch,
                    'confidence' => $cConf,
                    'raw' => $c,
                ];
                $charTextParts[] = $ch . '=' . ($cConf !== null ? $cConf . '%' : '-');
            }
            $summary['characters_list'] = $charList;
            $summary['characters_text'] = implode(', ', $charTextParts);
        }

        $bboxParts = [];
        if (!empty($plate['corners'])) {
            $pBbox = $this->formatCornersBbox($plate['corners']);
            if ($pBbox) {
                $bboxParts[] = 'Plate: ' . $pBbox;
            }
        }

        if (!empty($plate['vehicle']) && is_array($plate['vehicle'])) {
            $v = $plate['vehicle'];
            $makeConf = $v['maker_confidence'] ?? $v['make_confidence'] ?? $v['make_conf'] ?? $v['maker_conf'] ?? $v['mmr_confidence'] ?? null;
            $modelConf = $v['model_confidence'] ?? $v['model_conf'] ?? $v['mmr_confidence'] ?? null;
            $makeStr = $this->formatLabelWithConf($v['maker'] ?? $v['make'] ?? null, $makeConf);
            $modelStr = $this->formatLabelWithConf($v['model'] ?? null, $modelConf);
            $genStr = $this->formatLabelWithConf($v['generation'] ?? $v['sub_model'] ?? null, $v['generation_confidence'] ?? null);

            $summary['make'] = $makeStr;
            $summary['model'] = $modelStr;
            $summary['generation'] = $genStr;

            $vehText = trim(($makeStr ?? '') . ' ' . ($modelStr ?? ''));
            $summary['vehicle'] = $vehText !== '' ? substr($vehText, 0, 191) : null;

            $catStr = $this->formatLabelWithConf($v['type'] ?? null, $v['type_confidence'] ?? $v['type_conf'] ?? null);
            $summary['category'] = $catStr ? substr($catStr, 0, 128) : null;

            $colStr = $this->formatLabelWithConf($v['color'] ?? null, $v['color_confidence'] ?? $v['color_conf'] ?? null);
            $summary['color'] = $colStr ? substr($colStr, 0, 128) : null;

            $vpRaw = $v['viewpoint'] ?? $v['view'] ?? $v['orientation'] ?? null;
            if ($vpRaw) {
                $vpConf = $v['viewpoint_confidence'] ?? $v['view_confidence'] ?? $v['viewpoint_conf'] ?? $v['view_conf'] ?? $v['orientation_confidence'] ?? $v['orientation_conf'] ?? null;
                $vpStr = $this->formatLabelWithConf(strtoupper((string) $vpRaw), $vpConf);
                $summary['viewport'] = $vpStr ? substr($vpStr, 0, 64) : null;
            }

            if (!empty($v['corners'])) {
                $vBbox = $this->formatCornersBbox($v['corners']);
                if ($vBbox) {
                    $bboxParts[] = 'Vehicle: ' . $vBbox;
                }
            }

            $summary['tags'] = $this->extractTagsText($v['tags'] ?? null);
        }

        if (!$summary['viewport'] && !empty($plate['viewpoint'])) {
            $vpStr = $this->formatLabelWithConf(strtoupper((string) $plate['viewpoint']), $plate['viewpoint_conf'] ?? $plate['viewpoint_confidence'] ?? null);
            $summary['viewport'] = $vpStr ? substr($vpStr, 0, 64) : null;
        }

        $summary['bbox'] = !empty($bboxParts) ? implode(' | ', $bboxParts) : null;
        $summary['quality'] = $catConf !== null ? ('Category Conf: ' . $catConf . '%') : 'N/A';

        $summary['details_json'] = [
            'uuid' => $candid['data']['uuid'] ?? null,
            'datetime' => $candid['data']['datetime'] ?? null,
            'engine_time_ms' => $candid['data']['time_ms'] ?? null,
            'detection_area_id' => $plate['detection_area_id'] ?? null,
            'file_name' => $plate['file_name'] ?? null,
            'text_groups' => $plate['text_groups'] ?? null,
            'local_text' => $summary['local_text'],
            'local_text_groups' => $plate['local_text_groups'] ?? null,
            'plate_color' => $summary['plate_color'],
            'make' => $summary['make'],
            'model' => $summary['model'],
            'generation' => $summary['generation'],
            'category' => $summary['category'],
            'vehicle_color' => $summary['color'],
            'orientation' => $summary['viewport'],
            'bbox' => $summary['bbox'],
            'plate_corners' => $plate['corners'] ?? null,
            'vehicle_corners' => $plate['vehicle']['corners'] ?? null,
        ];

        return $summary;
    }

    /**
     * Normalize Eydea result for all database columns + character-wise list.
     */
    public function extractEydeaSummary(?array $eydea): array
    {
        $summary = $this->emptyEngineSummary($eydea['time_ms'] ?? null);

        if (!$eydea || empty($eydea['success']) || empty($eydea['data']['anpr'][0])) {
            return $summary;
        }

        $anprList = $eydea['data']['anpr'];
        $summary['success'] = true;

        $platesText = [];
        $regionsText = [];
        $localsText = [];
        $confs = [];
        $charTextParts = [];
        $charList = [];
        $makesText = [];
        $modelsText = [];
        $generationsText = [];
        $vehiclesText = [];
        $categoriesText = [];
        $colorsText = [];
        $plateColorsText = [];
        $bboxesText = [];
        $viewportsText = [];
        $qualitiesText = [];
        $tagsText = [];

        foreach ($anprList as $pIdx => $anpr) {
            $mmrRaw = $eydea['data']['mmr_classification'] ?? null;
            $mmr = is_array($mmrRaw) ? ($mmrRaw[$pIdx] ?? ($pIdx === 0 ? $mmrRaw : [])) : [];

            if (!empty($anpr['lines']) && is_array($anpr['lines'])) {
                $texts = array_map(function ($line) {
                    return $line['text'] ?? '';
                }, $anpr['lines']);
                $compiled = trim(implode(' ', array_filter($texts)));
                if ($compiled !== '') {
                    $platesText[] = $compiled;
                }

                $posCounter = 1;
                foreach ($anpr['lines'] as $line) {
                    $lineConfNorm = $this->normalizeConfidence($line['confidence'] ?? null);
                    if ($lineConfNorm !== null) {
                        $qualitiesText[] = 'Line OCR: ' . $lineConfNorm . '%';
                    }
                    if (!empty($line['characters_confidences']) && is_array($line['characters_confidences'])) {
                        foreach ($line['characters_confidences'] as $cc) {
                            $ch = (string) ($cc['character'] ?? '');
                            $cConf = $this->normalizeConfidence($cc['confidence'] ?? null);
                            $charList[] = [
                                'plate_index' => $pIdx,
                                'position' => $posCounter++,
                                'character' => $ch,
                                'confidence' => $cConf,
                                'raw' => $cc,
                            ];
                            $charTextParts[] = $ch . '=' . ($cConf !== null ? $cConf . '%' : '-');
                        }
                    } else {
                        $lineText = (string) ($line['text'] ?? '');
                        $len = strlen($lineText);
                        for ($i = 0; $i < $len; $i++) {
                            $ch = $lineText[$i];
                            if (trim($ch) !== '') {
                                $charList[] = [
                                    'plate_index' => $pIdx,
                                    'position' => $posCounter++,
                                    'character' => $ch,
                                    'confidence' => $lineConfNorm,
                                    'raw' => ['character' => $ch, 'confidence' => $lineConfNorm],
                                ];
                                $charTextParts[] = $ch . '=' . ($lineConfNorm !== null ? $lineConfNorm . '%' : '-');
                            }
                        }
                    }
                }
            }

            if (!empty($anpr['plate_type'])) {
                $ptStr = $this->formatLabelWithConf($anpr['plate_type'], $anpr['plate_type_confidence'] ?? null);
                $regionsText[] = $ptStr ?: trim((string) $anpr['plate_type']);
            }

            // Physical plate dimensions (mm) & line count
            $dimParts = [];
            if (!empty($anpr['dimensions_mm']) && is_array($anpr['dimensions_mm'])) {
                $wMm = $anpr['dimensions_mm']['width'] ?? null;
                $hMm = $anpr['dimensions_mm']['height'] ?? null;
                $dConf = $this->normalizeConfidence($anpr['dimensions_mm']['confidence'] ?? null);
                if ($wMm && $hMm) {
                    $dimParts[] = "Dim: {$wMm}×{$hMm} mm" . ($dConf !== null ? " ({$dConf}%)" : '');
                }
            }
            if (!empty($anpr['num_lines'])) {
                $dimParts[] = $anpr['num_lines'] . ($anpr['num_lines'] == 1 ? ' Line' : ' Lines');
            }
            if (!empty($dimParts)) {
                $plateColorsText[] = implode(' | ', $dimParts);
            }

            $linesCount = $anpr['num_lines'] ?? (!empty($anpr['lines']) ? count($anpr['lines']) : 0);
            if ($linesCount > 1 && $compiled !== '') {
                $localsText[] = "Lines ({$linesCount}): {$compiled}";
            }

            // Bounding box
            if (!empty($anpr['bbox']) && is_array($anpr['bbox'])) {
                $tl = $anpr['bbox']['top_left'] ?? null;
                $br = $anpr['bbox']['bottom_right'] ?? null;
                $bw = isset($anpr['bbox_width']) ? round((float) $anpr['bbox_width']) : null;
                $bh = isset($anpr['bbox_height']) ? round((float) $anpr['bbox_height']) : null;
                if (is_array($tl) && is_array($br) && isset($tl[0], $tl[1], $br[0], $br[1])) {
                    $x1 = round((float) $tl[0]);
                    $y1 = round((float) $tl[1]);
                    $x2 = round((float) $br[0]);
                    $y2 = round((float) $br[1]);
                    $bboxesText[] = "Plate: [{$x1},{$y1} → {$x2},{$y2}]" . ($bw && $bh ? " ({$bw}×{$bh}px)" : '');
                }
            }

            $cVal = $this->normalizeConfidence($anpr['confidence'] ?? null);
            if ($cVal !== null) {
                $confs[] = $cVal;
            }

            if (is_array($mmr)) {
                $makeStr = $this->formatLabelWithConf($mmr['make'] ?? null, $mmr['make_confidence'] ?? null);
                $modelStr = $this->formatLabelWithConf($mmr['model'] ?? null, $mmr['model_confidence'] ?? null);
                $genLabel = trim((string) ($mmr['generation'] ?? ''));
                if (!empty($mmr['variation'])) {
                    $genLabel = trim($genLabel . ' ' . $mmr['variation']);
                }
                $genStr = $this->formatLabelWithConf($genLabel !== '' ? $genLabel : null, $mmr['generation_confidence'] ?? $mmr['variation_confidence'] ?? null);

                if ($makeStr) {
                    $makesText[] = $makeStr;
                }
                if ($modelStr) {
                    $modelsText[] = $modelStr;
                }
                if ($genStr) {
                    $generationsText[] = $genStr;
                }

                $vStr = trim(($makeStr ?? '') . ' ' . ($modelStr ?? '') . ($genStr ? ' [' . $genStr . ']' : ''));
                if ($vStr !== '') {
                    $vehiclesText[] = $vStr;
                }

                $catStr = $this->formatLabelWithConf($mmr['category'] ?? null, $mmr['category_confidence'] ?? null);
                if ($catStr) {
                    $categoriesText[] = $catStr;
                }

                $colStr = $this->formatLabelWithConf($mmr['color'] ?? null, $mmr['color_confidence'] ?? null);
                if ($colStr) {
                    $colorsText[] = $colStr;
                }

                if (!empty($mmr['view'])) {
                    $vpStr = $this->formatLabelWithConf(strtoupper((string) $mmr['view']), $mmr['view_confidence'] ?? null);
                    if (!empty($mmr['view8'])) {
                        $vp8 = $this->formatLabelWithConf(strtoupper((string) $mmr['view8']), $mmr['view8_confidence'] ?? null);
                        if ($vp8) {
                            $vpStr = ($vpStr ? $vpStr . ' / ' : '') . $vp8;
                        }
                    }
                    if ($vpStr) {
                        $viewportsText[] = $vpStr;
                    }
                }

                $tStr = $this->extractTagsText($mmr['tags'] ?? null);
                if ($tStr) {
                    $tagsText[] = $tStr;
                }
            }
        }

        $summary['plate'] = !empty($platesText) ? substr(implode(' | ', $platesText), 0, 64) : null;
        $summary['local_text'] = !empty($localsText) ? implode(' | ', $localsText) : null;
        $summary['region'] = !empty($regionsText) ? substr(implode(' | ', $regionsText), 0, 191) : null;
        $summary['confidence'] = $confs[0] ?? null;
        $summary['characters_list'] = $charList;
        $summary['characters_text'] = !empty($charTextParts) ? implode(', ', $charTextParts) : null;
        $summary['make'] = !empty($makesText) ? implode(' | ', $makesText) : null;
        $summary['model'] = !empty($modelsText) ? implode(' | ', $modelsText) : null;
        $summary['generation'] = !empty($generationsText) ? implode(' | ', $generationsText) : null;
        $summary['vehicle'] = !empty($vehiclesText) ? substr(implode(' | ', $vehiclesText), 0, 191) : null;
        $summary['category'] = !empty($categoriesText) ? substr(implode(' | ', $categoriesText), 0, 128) : null;
        $summary['color'] = !empty($colorsText) ? substr(implode(' | ', $colorsText), 0, 128) : null;
        $summary['plate_color'] = !empty($plateColorsText) ? implode(' | ', $plateColorsText) : null;
        $summary['bbox'] = !empty($bboxesText) ? implode(' | ', $bboxesText) : null;
        $summary['viewport'] = !empty($viewportsText) ? substr(implode(' | ', $viewportsText), 0, 64) : null;
        $summary['quality'] = !empty($qualitiesText) ? implode(' | ', $qualitiesText) : 'N/A';
        $summary['tags'] = !empty($tagsText) ? implode(' | ', $tagsText) : null;

        $summary['details_json'] = [
            'inference_ms' => $eydea['data']['inference_ms'] ?? null,
            'make' => $summary['make'],
            'model' => $summary['model'],
            'generation' => $summary['generation'],
            'category' => $summary['category'],
            'vehicle_color' => $summary['color'],
            'plate_dimensions' => $summary['plate_color'],
            'orientation' => $summary['viewport'],
            'bbox' => $summary['bbox'],
            'quality' => $summary['quality'],
            'tags' => $eydea['data']['mmr_classification']['tags'] ?? null,
        ];

        return $summary;
    }

    /**
     * Normalize ARH (parkonic key) result for all database columns + character-wise list.
     */
    public function extractArhSummary(?array $parkonic): array
    {
        $summary = $this->emptyEngineSummary($parkonic['time_ms'] ?? null);

        if (!$parkonic || empty($parkonic['success']) || empty($parkonic['data'])) {
            return $summary;
        }

        $data = $parkonic['data'];
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        if (!is_array($data)) {
            return $summary;
        }

        $plate = (!empty($data['plate_']) && is_array($data['plate_'])) ? $data['plate_'][0] : $data;
        if (empty($plate['text'])) {
            return $summary;
        }

        $summary['success'] = true;
        $code = trim((string) ($plate['category'] ?? ''));
        $text = trim((string) ($plate['text'] ?? ''));
        $fullPlate = ($code && stripos($text, $code) !== 0) ? ($code . ' ' . $text) : $text;
        $summary['plate'] = substr(trim($fullPlate), 0, 64) ?: null;

        $reg = trim((string) ($plate['cityName'] ?? ''));
        if (!empty($plate['platetype'])) {
            $reg .= ($reg ? ' ' : '') . '(' . $plate['platetype'] . ')';
        }
        $plateDetConf = $this->normalizeConfidence($plate['plate_conf'] ?? null);
        if ($reg !== '' && $plateDetConf !== null) {
            $reg .= ' (' . $plateDetConf . '%)';
        }
        $summary['region'] = substr(trim($reg), 0, 191) ?: null;

        $summary['confidence'] = $this->normalizeConfidence($plate['confidance'] ?? $plate['confidence'] ?? null);

        // Character-wise extraction & Arabic/Local characters
        $rawChars = $plate['plate_detail'] ?? $plate['characters'] ?? [];
        $localCharsParts = [];
        if (is_array($rawChars) && count($rawChars) > 0) {
            $charTextParts = [];
            $charList = [];
            foreach (array_values($rawChars) as $idx => $c) {
                $ch = (string) ($c['character'] ?? '');
                $cConf = $this->normalizeConfidence($c['conf'] ?? $c['confidence'] ?? null);
                $charList[] = [
                    'plate_index' => 0,
                    'position' => $idx + 1,
                    'character' => $ch,
                    'confidence' => $cConf,
                    'raw' => $c,
                ];
                $charTextParts[] = $ch . '=' . ($cConf !== null ? $cConf . '%' : '-');
                if ($ch !== '' && !preg_match('/^[A-Za-z0-9]$/', $ch)) {
                    $localCharsParts[] = $ch . ($cConf !== null ? ' (' . $cConf . '%)' : '');
                }
            }
            $summary['characters_list'] = $charList;
            $summary['characters_text'] = implode(', ', $charTextParts);
        }
        $summary['local_text'] = !empty($localCharsParts) ? implode(' ', $localCharsParts) : null;

        $makeStr = $this->formatLabelWithConf($plate['make'] ?? $plate['maker'] ?? null, $plate['mmr_make_conf'] ?? null);
        $modelStr = $this->formatLabelWithConf($plate['model'] ?? null, $plate['mmr_model_conf'] ?? null);
        $genStr = $this->formatLabelWithConf($plate['sub_model'] ?? null, null);

        $summary['make'] = $makeStr;
        $summary['model'] = $modelStr;
        $summary['generation'] = $genStr;

        $vehText = trim(($makeStr ?? '') . ' ' . ($modelStr ?? '') . ($genStr ? ' ' . $genStr : ''));
        $summary['vehicle'] = $vehText !== '' ? substr($vehText, 0, 191) : null;

        $catStr = $this->formatLabelWithConf($plate['mmr_category'] ?? $plate['bodytype'] ?? null, $plate['mmr_category_conf'] ?? null);
        $summary['category'] = $catStr ? substr($catStr, 0, 128) : null;

        // Vehicle Color vs Plate Color
        $vehColorStr = $this->formatLabelWithConf($plate['mmr_color'] ?? null, $plate['mmr_color_conf'] ?? null);
        $summary['color'] = $vehColorStr ? substr($vehColorStr, 0, 191) : null;

        $plateColorStr = null;
        $plateColorParts = [];
        if (!empty($plate['platetype'])) {
            $plateColorParts[] = 'Type: ' . trim((string) $plate['platetype']);
        }
        if (!empty($plate['backcolor']) && !empty($plate['textcolor'])) {
            $plateColorParts[] = 'Color: ' . $plate['textcolor'] . ' on ' . $plate['backcolor'];
        } elseif (!empty($plate['backcolor'])) {
            $plateColorParts[] = 'Color: ' . (string) $plate['backcolor'];
        }
        if (!empty($plateColorParts)) {
            $plateColorStr = implode(' | ', $plateColorParts);
            if ($plateDetConf !== null) {
                $plateColorStr .= ' (' . $plateDetConf . '%)';
            }
        }
        $summary['plate_color'] = $plateColorStr;

        if (!empty($plate['viewpoint'])) {
            $vpStr = $this->formatLabelWithConf(strtoupper((string) $plate['viewpoint']), $plate['viewpoint_conf'] ?? null);
            if (!empty($plate['heading'])) {
                $hdStr = $this->formatLabelWithConf((string) $plate['heading'], $plate['heading_conf'] ?? null);
                if ($hdStr) {
                    $vpStr = ($vpStr ? $vpStr . ' / ' : '') . $hdStr;
                }
            }
            $summary['viewport'] = $vpStr ? substr($vpStr, 0, 64) : null;
        }

        // Bounding Box from x1..x4, y1..y4
        if (isset($plate['x1'], $plate['y1'], $plate['x2'], $plate['y2'], $plate['x3'], $plate['y3'], $plate['x4'], $plate['y4'])) {
            $corners = [
                [$plate['x1'], $plate['y1']],
                [$plate['x2'], $plate['y2']],
                [$plate['x3'], $plate['y3']],
                [$plate['x4'], $plate['y4']],
            ];
            $pBbox = $this->formatCornersBbox($corners);
            if ($pBbox) {
                $summary['bbox'] = 'Plate: ' . $pBbox;
            }
        }

        $summary['quality'] = $plateDetConf !== null ? ('Plate Conf: ' . $plateDetConf . '%') : 'N/A';
        $summary['tags'] = $this->extractTagsText($plate['tags'] ?? null);

        if (isset($data['Time_ms']) && $data['Time_ms'] !== '') {
            $summary['time_ms'] = (string) $data['Time_ms'];
        } elseif (isset($data['time_ms']) && $data['time_ms'] !== '') {
            $summary['time_ms'] = (string) $data['time_ms'];
        }

        $summary['details_json'] = [
            'uuid' => $data['uuid'] ?? null,
            'datetime' => $data['datetime'] ?? null,
            'engine_matched' => $data['engine_matched'] ?? null,
            'citycode' => $plate['citycode'] ?? null,
            'fileName' => $plate['fileName'] ?? null,
            'plate_number' => $text,
            'plate_code' => $code,
            'local_text' => $summary['local_text'],
            'make' => $summary['make'],
            'model' => $summary['model'],
            'sub_model' => $plate['sub_model'] ?? null,
            'category' => $summary['category'],
            'bodytype' => $plate['bodytype'] ?? null,
            'vehicle_color' => $summary['color'],
            'plate_color' => $summary['plate_color'],
            'backcolor' => $plate['backcolor'] ?? null,
            'textcolor' => $plate['textcolor'] ?? null,
            'orientation' => $summary['viewport'],
            'heading' => $plate['heading'] ?? null,
            'plate_conf' => $plateDetConf,
            'bbox' => $summary['bbox'],
        ];

        return $summary;
    }

    /**
     * Normalize Parkonic Engine (anpr2 key) result for all database columns + character-wise list.
     */
    public function extractParkonicEngineSummary(?array $anpr2): array
    {
        $summary = $this->emptyEngineSummary($anpr2['time_ms'] ?? null);

        if (!$anpr2 || empty($anpr2['success']) || empty($anpr2['data'])) {
            return $summary;
        }

        $data = $anpr2['data'];
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        if (!is_array($data)) {
            return $summary;
        }

        if (!empty($data['vehicles']) && is_array($data['vehicles'])) {
            $summary['success'] = true;
            $platesText = [];
            $regionsText = [];
            $localsText = [];
            $confs = [];
            $charTextParts = [];
            $charList = [];
            $makesText = [];
            $modelsText = [];
            $generationsText = [];
            $vehiclesText = [];
            $categoriesText = [];
            $colorsText = [];
            $plateColorsText = [];
            $bboxesText = [];
            $viewportsText = [];
            $qualitiesText = [];

            foreach ($data['vehicles'] as $vIdx => $vehicle) {
                $ocr = $vehicle['ocr'] ?? [];
                $number = trim((string) ($ocr['number'] ?? ''));
                $category = trim((string) ($ocr['category'] ?? ''));

                if ($number !== '' || $category !== '') {
                    $fullPlate = ($category && stripos($number, $category) !== 0) ? ($category . ' ' . $number) : $number;
                    $platesText[] = trim($fullPlate);
                }

                if ($category !== '') {
                    $localsText[] = 'Code: ' . $category;
                }

                $regionParts = [];
                if (!empty($vehicle['city']['type'])) {
                    $cityName = ucwords(str_replace('_', ' ', (string) $vehicle['city']['type']));
                    $regionParts[] = $this->formatLabelWithConf($cityName, $vehicle['city']['confidence'] ?? null) ?: $cityName;
                }
                if (!empty($vehicle['plate_type']['type'])) {
                    $plateType = ucwords(str_replace('_', ' ', (string) $vehicle['plate_type']['type']));
                    $ptFormatted = $this->formatLabelWithConf($plateType, $vehicle['plate_type']['confidence'] ?? null) ?: $plateType;
                    $regionParts[] = $ptFormatted;
                    $plateColorsText[] = 'Type: ' . $ptFormatted;
                }
                if (!empty($regionParts)) {
                    $regionsText[] = implode(' / ', $regionParts);
                }

                $cVal = $this->normalizeConfidence($ocr['confidence'] ?? null);
                if ($cVal !== null) {
                    $confs[] = $cVal;
                }

                // Character-wise extraction
                $rawChars = $ocr['characters'] ?? [];
                if (is_array($rawChars) && count($rawChars) > 0) {
                    foreach (array_values($rawChars) as $cIdx => $c) {
                        $ch = (string) ($c['character'] ?? '');
                        $cConf = $this->normalizeConfidence($c['confidence'] ?? $c['conf'] ?? null);
                        $charList[] = [
                            'plate_index' => $vIdx,
                            'position' => $cIdx + 1,
                            'character' => $ch,
                            'confidence' => $cConf,
                            'raw' => $c,
                        ];
                        $charTextParts[] = $ch . '=' . ($cConf !== null ? $cConf . '%' : '-');
                    }
                }

                if (!empty($vehicle['mmr']) && is_array($vehicle['mmr'])) {
                    $rawMake = isset($vehicle['mmr']['make']) ? ucwords((string) $vehicle['mmr']['make']) : null;
                    $rawModel = isset($vehicle['mmr']['model']) ? ucwords((string) $vehicle['mmr']['model']) : null;
                    $makeStr = $this->formatLabelWithConf($rawMake, $vehicle['mmr']['make_confidence'] ?? null);
                    $modelStr = $this->formatLabelWithConf($rawModel, $vehicle['mmr']['model_confidence'] ?? null);
                    if ($makeStr) {
                        $makesText[] = $makeStr;
                    }
                    if ($modelStr) {
                        $modelsText[] = $modelStr;
                    }
                    if (!empty($vehicle['mmr']['color'])) {
                        $cStr = $this->formatLabelWithConf($vehicle['mmr']['color'], $vehicle['mmr']['color_confidence'] ?? null);
                        if ($cStr) {
                            $colorsText[] = $cStr;
                        }
                    }
                    if (!empty($vehicle['mmr']['generation']) || !empty($vehicle['mmr']['sub_model']) || !empty($vehicle['mmr']['year'])) {
                        $gRaw = trim(($vehicle['mmr']['generation'] ?? '') . ' ' . ($vehicle['mmr']['sub_model'] ?? '') . ' ' . ($vehicle['mmr']['year'] ?? ''));
                        if ($gRaw !== '') {
                            $generationsText[] = $this->formatLabelWithConf($gRaw, $vehicle['mmr']['generation_confidence'] ?? null) ?: $gRaw;
                        }
                    }
                    $vStr = trim(($makeStr ?? '') . ' ' . ($modelStr ?? ''));
                    if ($vStr !== '') {
                        $vehiclesText[] = $vStr;
                    }
                }

                if (!empty($vehicle['vehicle_type']['type'])) {
                    $catStr = $this->formatLabelWithConf(ucwords((string) $vehicle['vehicle_type']['type']), $vehicle['vehicle_type']['confidence'] ?? null);
                    if ($catStr) {
                        $categoriesText[] = $catStr;
                    }
                }

                if (!empty($vehicle['viewpoint']['type'])) {
                    $vpStr = $this->formatLabelWithConf(strtoupper((string) $vehicle['viewpoint']['type']), $vehicle['viewpoint']['confidence'] ?? null);
                    if ($vpStr) {
                        $viewportsText[] = $vpStr;
                    }
                }

                if (!empty($vehicle['cropped_Quality']['type'])) {
                    $qStr = $this->formatLabelWithConf('Cropped: ' . $vehicle['cropped_Quality']['type'], $vehicle['cropped_Quality']['confidence'] ?? null);
                    if ($qStr) {
                        $qualitiesText[] = $qStr;
                    }
                }

                if (!empty($vehicle['coordinates']['points'])) {
                    $pBbox = $this->formatCornersBbox($vehicle['coordinates']['points']);
                    if ($pBbox) {
                        $bboxesText[] = 'Plate: ' . $pBbox;
                    }
                }
            }

            $summary['plate'] = !empty($platesText) ? substr(implode(' | ', $platesText), 0, 64) : null;
            $summary['local_text'] = !empty($localsText) ? implode(' | ', $localsText) : null;
            $summary['region'] = !empty($regionsText) ? substr(implode(' | ', $regionsText), 0, 191) : null;
            $summary['confidence'] = $confs[0] ?? null;
            $summary['characters_list'] = $charList;
            $summary['characters_text'] = !empty($charTextParts) ? implode(', ', $charTextParts) : null;
            $summary['make'] = !empty($makesText) ? implode(' | ', $makesText) : null;
            $summary['model'] = !empty($modelsText) ? implode(' | ', $modelsText) : null;
            $summary['generation'] = !empty($generationsText) ? implode(' | ', $generationsText) : null;
            $summary['vehicle'] = !empty($vehiclesText) ? substr(implode(' | ', $vehiclesText), 0, 191) : null;
            $summary['category'] = !empty($categoriesText) ? substr(implode(' | ', $categoriesText), 0, 128) : null;
            $summary['color'] = !empty($colorsText) ? implode(' | ', $colorsText) : null;
            $summary['plate_color'] = !empty($plateColorsText) ? implode(' | ', $plateColorsText) : null;
            $summary['bbox'] = !empty($bboxesText) ? implode(' | ', $bboxesText) : null;
            $summary['viewport'] = !empty($viewportsText) ? substr(implode(' | ', $viewportsText), 0, 64) : null;
            $summary['quality'] = !empty($qualitiesText) ? substr(implode(' | ', $qualitiesText), 0, 128) : 'N/A';
            $summary['tags'] = null;

            if (isset($data['processing_time_ms']) && $data['processing_time_ms'] !== '') {
                $summary['time_ms'] = (string) $data['processing_time_ms'];
            }

            $summary['details_json'] = [
                'uuid' => $data['uuid'] ?? null,
                'processing_time_ms' => $data['processing_time_ms'] ?? null,
                'make' => $summary['make'],
                'model' => $summary['model'],
                'category' => $summary['category'],
                'orientation' => $summary['viewport'],
                'quality' => $summary['quality'],
                'bbox' => $summary['bbox'],
            ];

            return $summary;
        }

        // Fallback if legacy structure returned
        return $this->extractArhSummary($anpr2);
    }

    /**
     * Extract numeric confidence from a formatted string like "Toyota (98.45%)" or raw confidence string.
     */
    private function parseConfidenceFromText(?string $text, $fallbackConf = null): ?string
    {
        if ($text && preg_match_all('/\(([0-9]+(?:\.[0-9]+)?)%\)/', $text, $matches) && !empty($matches[1])) {
            return $matches[1][0];
        }
        return $this->normalizeConfidence($fallbackConf);
    }

    /**
     * Strip trailing "(98.45%)" from a formatted label to get the clean value.
     */
    private function stripConfidenceFromText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }
        $cleaned = trim(preg_replace('/\s*\([0-9]+(?:\.[0-9]+)?%\)/', '', $text));
        return ($cleaned !== '' && strtoupper($cleaned) !== 'N/A') ? $cleaned : null;
    }

    /**
     * Pick the best value across the 4 OCR engines for a given field.
     * If only one engine has data, that engine's value is always preserved.
     */
    private function pickBestFieldValue(array $summaries, string $field, array $engineNames): ?array
    {
        $best = null;
        foreach ($summaries as $engKey => $sum) {
            $rawVal = $sum[$field] ?? null;
            if (!$rawVal || strtoupper(trim((string) $rawVal)) === 'N/A' || trim((string) $rawVal) === '-' || trim((string) $rawVal) === 'None') {
                continue;
            }
            $hasExplicitPct = (bool) preg_match('/\(([0-9]+(?:\.[0-9]+)?)%\)/', (string) $rawVal);
            $confStr = $this->parseConfidenceFromText((string) $rawVal, $sum['confidence'] ?? null);
            $confNum = $confStr !== null ? (float) $confStr : 0.0;
            // Prefer values with explicit attribute confidence over fallback plate confidence
            $effectiveScore = $hasExplicitPct ? ($confNum + 1000.0) : $confNum;
            $cleanVal = $this->stripConfidenceFromText((string) $rawVal) ?: trim((string) $rawVal);

            if ($best === null || $effectiveScore > $best['effective_score']) {
                $best = [
                    'value' => $cleanVal,
                    'raw_formatted' => (string) $rawVal,
                    'conf' => $confStr ?? '100',
                    'confidence' => $confStr ?? '100',
                    'conf_num' => $confNum,
                    'effective_score' => $effectiveScore,
                    'engine_key' => $engKey,
                    'engine' => $engineNames[$engKey] ?? ucfirst($engKey),
                    'engine_label' => $engineNames[$engKey] ?? ucfirst($engKey),
                    'formatted' => $cleanVal . ' (' . ($confStr ?? '100') . '% From ' . ($engineNames[$engKey] ?? ucfirst($engKey)) . ')',
                ];
            }
        }
        return $best;
    }

    /**
     * Composite Best Pick for Plate Attributes (Type, Color, Size, Dimensions).
     * Gathers all distinct sub-facets across all 4 engines so unique information
     * (e.g. Type: Taxi (71.276%) from Parkonic, Size: Long from Candid, Dim from Eydea)
     * is NEVER lost in Best Pick.
     */
    private function computeCompositePlateAttributes(array $summaries, array $engineNames): ?array
    {
        $types = [];
        $colors = [];
        $sizes = [];
        $dims = [];

        foreach ($summaries as $engKey => $sum) {
            $val = $sum['plate_color'] ?? null;
            if (!$val || strtoupper(trim((string) $val)) === 'N/A' || trim((string) $val) === '-') {
                continue;
            }
            $engLabel = $engineNames[$engKey] ?? ucfirst($engKey);
            $parts = explode('|', (string) $val);

            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;

                $conf = $this->parseConfidenceFromText($p, $sum['confidence'] ?? null) ?? '100';
                $clean = $this->stripConfidenceFromText($p) ?: $p;
                $confNum = (float) $conf;

                if (stripos($clean, 'Type:') !== false || stripos($clean, 'Taxi') !== false || stripos($clean, 'Private') !== false || stripos($clean, 'Commercial') !== false) {
                    $typeVal = trim(preg_replace('/^Type:\s*/i', '', $clean));
                    $isSpecific = (bool) preg_match('/Taxi|Commercial|Police|Diplomatic/i', $typeVal);
                    $score = $confNum + ($isSpecific ? 500 : 0);
                    $types[] = [
                        'label' => 'Plate Type',
                        'value' => 'Type: ' . $typeVal,
                        'clean_value' => $typeVal,
                        'conf' => $conf,
                        'score' => $score,
                        'engine_key' => $engKey,
                        'engine_label' => $engLabel,
                        'formatted' => 'Type: ' . $typeVal . ' (' . $conf . '% From ' . $engLabel . ')',
                    ];
                } elseif (stripos($clean, 'Color:') !== false || stripos($clean, ' on ') !== false || preg_match('/^(Yellow|White|Blue|Green|Red|Black)/i', $clean)) {
                    $colorVal = trim(preg_replace('/^Color:\s*/i', '', $clean));
                    $colors[] = [
                        'label' => 'Plate Color',
                        'value' => 'Color: ' . $colorVal,
                        'clean_value' => $colorVal,
                        'conf' => $conf,
                        'score' => $confNum,
                        'engine_key' => $engKey,
                        'engine_label' => $engLabel,
                        'formatted' => 'Color: ' . $colorVal . ' (' . $conf . '% From ' . $engLabel . ')',
                    ];
                } elseif (stripos($clean, 'Size:') !== false || preg_match('/^(Long|Short|Square)/i', $clean)) {
                    $sizeVal = trim(preg_replace('/^Size:\s*/i', '', $clean));
                    $sizes[] = [
                        'label' => 'Plate Size',
                        'value' => 'Size: ' . $sizeVal,
                        'clean_value' => $sizeVal,
                        'conf' => $conf,
                        'score' => $confNum,
                        'engine_key' => $engKey,
                        'engine_label' => $engLabel,
                        'formatted' => 'Size: ' . $sizeVal . ' (' . $conf . '% From ' . $engLabel . ')',
                    ];
                } elseif (stripos($clean, 'Dim:') !== false || stripos($clean, 'mm') !== false || stripos($clean, 'Line') !== false) {
                    $dims[] = [
                        'label' => 'Plate Dimensions',
                        'value' => $clean,
                        'clean_value' => $clean,
                        'conf' => $conf,
                        'score' => $confNum,
                        'engine_key' => $engKey,
                        'engine_label' => $engLabel,
                        'formatted' => $clean . ' (' . $conf . '% From ' . $engLabel . ')',
                    ];
                }
            }
        }

        $items = [];
        if (!empty($types)) {
            usort($types, function ($a, $b) { return $b['score'] <=> $a['score']; });
            $items[] = $types[0];
        }
        if (!empty($colors)) {
            usort($colors, function ($a, $b) { return $b['score'] <=> $a['score']; });
            $items[] = $colors[0];
        }
        if (!empty($sizes)) {
            usort($sizes, function ($a, $b) { return $b['score'] <=> $a['score']; });
            $items[] = $sizes[0];
        }
        if (!empty($dims)) {
            usort($dims, function ($a, $b) { return $b['score'] <=> $a['score']; });
            $items[] = $dims[0];
        }

        if (empty($items)) {
            return $this->pickBestFieldValue($summaries, 'plate_color', $engineNames);
        }

        $formattedParts = array_map(function ($it) { return $it['formatted']; }, $items);
        $cleanValues = array_map(function ($it) { return $it['value']; }, $items);

        return [
            'value' => implode(' | ', $cleanValues),
            'formatted' => implode(' | ', $formattedParts),
            'conf' => $items[0]['conf'] ?? '100',
            'engine_label' => $items[0]['engine_label'] ?? 'Best',
            'items' => $items,
        ];
    }

    /**
     * Composite Best Pick for Quality / Plate Detection.
     * Preserves Cropped Image Quality (Parkonic) + Plate Detection / Line OCR (ARH/Eydea).
     */
    private function computeCompositeQuality(array $summaries, array $engineNames): ?array
    {
        $items = [];
        $croppedQuality = null;
        $bestConfMetric = null;

        foreach ($summaries as $engKey => $sum) {
            $val = $sum['quality'] ?? null;
            if (!$val || strtoupper(trim((string) $val)) === 'N/A' || trim((string) $val) === '-') {
                continue;
            }
            $engLabel = $engineNames[$engKey] ?? ucfirst($engKey);
            $parts = explode('|', (string) $val);

            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $conf = $this->parseConfidenceFromText($p, $sum['confidence'] ?? null) ?? '100';
                $clean = $this->stripConfidenceFromText($p) ?: $p;
                $confNum = (float) $conf;

                if (stripos($clean, 'Cropped') !== false) {
                    if (!$croppedQuality || $confNum > $croppedQuality['score']) {
                        $croppedQuality = [
                            'label' => 'Cropped Quality',
                            'value' => $clean,
                            'conf' => $conf,
                            'score' => $confNum,
                            'engine_label' => $engLabel,
                            'formatted' => $clean . ' (' . $conf . '% From ' . $engLabel . ')',
                        ];
                    }
                } else {
                    if (!$bestConfMetric || $confNum > $bestConfMetric['score']) {
                        $bestConfMetric = [
                            'label' => 'Detection Quality',
                            'value' => $clean,
                            'conf' => $conf,
                            'score' => $confNum,
                            'engine_label' => $engLabel,
                            'formatted' => $clean . ' (' . $conf . '% From ' . $engLabel . ')',
                        ];
                    }
                }
            }
        }

        if ($croppedQuality) {
            $items[] = $croppedQuality;
        }
        if ($bestConfMetric) {
            $items[] = $bestConfMetric;
        }

        if (empty($items)) {
            return $this->pickBestFieldValue($summaries, 'quality', $engineNames);
        }

        $formattedParts = array_map(function ($it) { return $it['formatted']; }, $items);
        $cleanValues = array_map(function ($it) { return $it['value']; }, $items);

        return [
            'value' => implode(' | ', $cleanValues),
            'formatted' => implode(' | ', $formattedParts),
            'conf' => $items[0]['conf'] ?? '100',
            'engine_label' => $items[0]['engine_label'] ?? 'Best',
            'items' => $items,
        ];
    }

    /**
     * Composite Best Pick for Bounding Box (Plate BBox + Vehicle BBox from Candid).
     */
    private function computeCompositeBbox(array $summaries, array $engineNames): ?array
    {
        $bestPlateBbox = null;
        $vehicleBbox = null;

        foreach ($summaries as $engKey => $sum) {
            $val = $sum['bbox'] ?? null;
            if (!$val || strtoupper(trim((string) $val)) === 'N/A' || trim((string) $val) === '-') {
                continue;
            }
            $engLabel = $engineNames[$engKey] ?? ucfirst($engKey);
            $parts = explode('|', (string) $val);

            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $conf = $sum['confidence'] ?? '100';
                $confNum = (float) $conf;

                if (stripos($p, 'Vehicle:') !== false) {
                    $vehicleBbox = [
                        'label' => 'Vehicle BBox',
                        'value' => $p,
                        'conf' => $conf,
                        'engine_label' => $engLabel,
                        'formatted' => $p . ' From ' . $engLabel,
                    ];
                } else {
                    if (!$bestPlateBbox || $confNum > $bestPlateBbox['score']) {
                        $bestPlateBbox = [
                            'label' => 'Plate BBox',
                            'value' => $p,
                            'conf' => $conf,
                            'score' => $confNum,
                            'engine_label' => $engLabel,
                            'formatted' => $p . ' From ' . $engLabel,
                        ];
                    }
                }
            }
        }

        $items = [];
        if ($bestPlateBbox) $items[] = $bestPlateBbox;
        if ($vehicleBbox) $items[] = $vehicleBbox;

        if (empty($items)) {
            return $this->pickBestFieldValue($summaries, 'bbox', $engineNames);
        }

        $formattedParts = array_map(function ($it) { return $it['formatted']; }, $items);
        $cleanValues = array_map(function ($it) { return $it['value']; }, $items);

        return [
            'value' => implode(' | ', $cleanValues),
            'formatted' => implode(' | ', $formattedParts),
            'conf' => $items[0]['conf'] ?? '100',
            'engine_label' => $items[0]['engine_label'] ?? 'Best',
            'items' => $items,
        ];
    }

    /**
     * Composite Best Pick for Local / Arabic Text & Code & Groups.
     */
    private function computeCompositeLocalText(array $summaries, array $engineNames): ?array
    {
        $items = [];
        $bestArabic = null;
        $bestCode = null;
        $bestGroups = null;
        $bestLines = null;

        foreach ($summaries as $engKey => $sum) {
            $val = $sum['local_text'] ?? null;
            if (!$val || strtoupper(trim((string) $val)) === 'N/A' || trim((string) $val) === '-') {
                continue;
            }
            $engLabel = $engineNames[$engKey] ?? ucfirst($engKey);
            $parts = explode('|', (string) $val);

            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $conf = $this->parseConfidenceFromText($p, $sum['confidence'] ?? null) ?? '100';
                $clean = $this->stripConfidenceFromText($p) ?: $p;
                $confNum = (float) $conf;

                if (stripos($clean, 'Code:') !== false) {
                    $bestCode = [
                        'label' => 'Plate Code',
                        'value' => $clean,
                        'conf' => $conf,
                        'engine_label' => $engLabel,
                        'formatted' => $clean . ' From ' . $engLabel,
                    ];
                } elseif (stripos($clean, 'Groups:') !== false) {
                    $bestGroups = [
                        'label' => 'Text Groups',
                        'value' => $clean,
                        'conf' => $conf,
                        'engine_label' => $engLabel,
                        'formatted' => $clean . ' From ' . $engLabel,
                    ];
                } elseif (stripos($clean, 'Lines') !== false) {
                    $bestLines = [
                        'label' => 'Line Breakdown',
                        'value' => $clean,
                        'conf' => $conf,
                        'engine_label' => $engLabel,
                        'formatted' => $clean . ' From ' . $engLabel,
                    ];
                } else {
                    if (!$bestArabic || $confNum > $bestArabic['score']) {
                        $bestArabic = [
                            'label' => 'Arabic / Local Text',
                            'value' => $clean,
                            'conf' => $conf,
                            'score' => $confNum,
                            'engine_label' => $engLabel,
                            'formatted' => $clean . ' (' . $conf . '% From ' . $engLabel . ')',
                        ];
                    }
                }
            }
        }

        if ($bestArabic) $items[] = $bestArabic;
        if ($bestCode) $items[] = $bestCode;
        if ($bestGroups) $items[] = $bestGroups;
        if ($bestLines) $items[] = $bestLines;

        if (empty($items)) {
            return $this->pickBestFieldValue($summaries, 'local_text', $engineNames);
        }

        $formattedParts = array_map(function ($it) { return $it['formatted']; }, $items);
        $cleanValues = array_map(function ($it) { return $it['value']; }, $items);

        return [
            'value' => implode(' | ', $cleanValues),
            'formatted' => implode(' | ', $formattedParts),
            'conf' => $items[0]['conf'] ?? '100',
            'engine_label' => $items[0]['engine_label'] ?? 'Best',
            'items' => $items,
        ];
    }

    /**
     * Compute the Best Picks across all 4 OCR engines (Candid, Eydea, ARH, Parkonic):
     * - Best Region / City (e.g. "Dubai Best 100 from Candid")
     * - Character-by-character Best Pick (e.g. "4 - 91.666 From ARH", "5 - 97.666 From Eydea")
     * - Assembled Best Plate (e.g. "DUBAI 45023")
     * - Best pick for each vehicle/parameter attribute (Orientation, Color, Make, Model, Generation, Category, Plate Color, Local Text, Quality, BBox)
     */
    public function computeBestPicks(array $summaries): array
    {
        $engineNames = [
            'candid' => 'Candid',
            'eydea' => 'Eydea',
            'arh' => 'ARH',
            'parkonic' => 'Parkonic',
        ];

        // 1. Best Region / City
        $bestRegionObj = null;
        foreach ($summaries as $engKey => $sum) {
            $regText = (string) ($sum['region'] ?? '');
            if (!$regText || strtoupper(trim($regText)) === 'N/A' || trim($regText) === '-') {
                continue;
            }
            $regConfStr = $this->parseConfidenceFromText($regText, $engKey === 'candid' ? '100' : ($sum['confidence'] ?? null));
            $regConfNum = $regConfStr !== null ? (float) $regConfStr : 0.0;
            $cleanReg = $this->stripConfidenceFromText($regText) ?: $regText;

            if ($bestRegionObj === null || $regConfNum > $bestRegionObj['conf_num']) {
                $bestRegionObj = [
                    'value' => $cleanReg,
                    'conf' => $regConfStr ?? '100',
                    'confidence' => $regConfStr ?? '100',
                    'conf_num' => $regConfNum,
                    'engine_key' => $engKey,
                    'engine' => $engineNames[$engKey],
                    'engine_label' => $engineNames[$engKey],
                    'formatted' => $cleanReg . ' Best ' . ($regConfStr ?? '100') . '% from ' . $engineNames[$engKey],
                ];
            }
        }

        // 2. Character-by-Character Best Pick Across the 4 Engines (compare primary plate ASCII alphanumeric characters)
        $engineCharLists = [];
        $maxCharLen = 0;
        foreach ($summaries as $engKey => $sum) {
            $list = $sum['characters_list'] ?? ($sum['characters'] ?? ($sum['characters_json'] ?? []));
            if (!empty($list) && is_array($list)) {
                $primaryList = array_values(array_filter($list, function ($item) {
                    return (int) ($item['plate_index'] ?? 0) === 0;
                }));
                if (empty($primaryList)) {
                    $primaryList = array_values($list);
                }
                $filtered = [];
                foreach ($primaryList as $item) {
                    $ch = trim((string) ($item['character'] ?? ''));
                    if ($ch !== '' && preg_match('/^[A-Za-z0-9]$/', $ch)) {
                        $filtered[] = $item;
                    }
                }
                if (empty($filtered)) {
                    $filtered = $primaryList;
                }
                $engineCharLists[$engKey] = $filtered;
                if (count($filtered) > $maxCharLen) {
                    $maxCharLen = count($filtered);
                }
            }
        }

        $bestCharsJson = [];
        $bestCharsFormattedParts = [];
        $assembledChars = [];

        if ($maxCharLen > 0) {
            $alignedByPos = array_fill(0, $maxCharLen, []);

            foreach ($engineCharLists as $engKey => $list) {
                $len = count($list);
                $offset = 0;
                if ($len < $maxCharLen) {
                    $firstChar = (string) ($list[0]['character'] ?? '');
                    if (ctype_digit($firstChar)) {
                        $offset = $maxCharLen - $len;
                    }
                }
                foreach ($list as $idx => $cItem) {
                    $targetPos = $idx + $offset;
                    if ($targetPos >= 0 && $targetPos < $maxCharLen) {
                        $cConfStr = $this->normalizeConfidence($cItem['confidence'] ?? null) ?? '0';
                        $alignedByPos[$targetPos][] = [
                            'engine_key' => $engKey,
                            'engine' => $engineNames[$engKey],
                            'engine_label' => $engineNames[$engKey],
                            'character' => (string) ($cItem['character'] ?? ''),
                            'confidence' => $cConfStr,
                            'conf_num' => (float) $cConfStr,
                        ];
                    }
                }
            }

            foreach ($alignedByPos as $posIdx => $candidates) {
                if (empty($candidates)) {
                    continue;
                }
                $winner = $candidates[0];
                foreach ($candidates as $cand) {
                    if ($cand['conf_num'] > $winner['conf_num']) {
                        $winner = $cand;
                    }
                }

                $assembledChars[] = $winner['character'];
                $formattedLine = $winner['character'] . ' - ' . $winner['confidence'] . '% From ' . $winner['engine'];
                $bestCharsFormattedParts[] = $formattedLine;

                $bestCharsJson[] = [
                    'position' => $posIdx + 1,
                    'character' => $winner['character'],
                    'confidence' => $winner['confidence'],
                    'engine_key' => $winner['engine_key'],
                    'engine' => $winner['engine'],
                    'engine_label' => $winner['engine'],
                    'formatted' => $formattedLine,
                    'candidates' => $candidates,
                ];
            }
        }

        // 3. Best Overall Plate & Assembled Plate
        $bestPlateEngine = null;
        foreach ($summaries as $engKey => $sum) {
            if (empty($sum['plate']) || strtoupper(trim((string) $sum['plate'])) === 'N/A') {
                continue;
            }
            $cStr = $sum['confidence'] ?? '0';
            $cNum = (float) $cStr;
            if ($bestPlateEngine === null || $cNum > $bestPlateEngine['conf_num']) {
                $bestPlateEngine = [
                    'value' => $sum['plate'],
                    'plate' => $sum['plate'],
                    'conf' => $cStr,
                    'confidence' => $cStr,
                    'conf_num' => $cNum,
                    'engine_key' => $engKey,
                    'engine' => $engineNames[$engKey],
                    'engine_label' => $engineNames[$engKey],
                ];
            }
        }

        $assembledPlateNumber = !empty($assembledChars) ? implode('', $assembledChars) : ($bestPlateEngine['plate'] ?? null);
        $shortRegionLabel = null;
        if ($bestRegionObj && !empty($bestRegionObj['value'])) {
            $shortReg = trim(preg_replace('/\s*[\(\/\|].*$/', '', $bestRegionObj['value']));
            $shortReg = trim(str_ireplace('UAE', '', $shortReg), " \t\n\r\0\x0B-_");
            if ($shortReg !== '') {
                $shortRegionLabel = strtoupper($shortReg);
            }
        }

        $compositeBestPlate = $assembledPlateNumber;
        if ($shortRegionLabel && $assembledPlateNumber && stripos($assembledPlateNumber, $shortRegionLabel) === false) {
            $compositeBestPlate = $shortRegionLabel . ' ' . $assembledPlateNumber;
        }

        // 4. Best Vehicle / Plate / Metadata Picks Across the 4 Engines
        // (Using composite multi-item pickers to ensure no engine's unique sub-facet like Type: Taxi is lost)
        $bestMake = $this->pickBestFieldValue($summaries, 'make', $engineNames);
        $bestModel = $this->pickBestFieldValue($summaries, 'model', $engineNames);
        $bestGeneration = $this->pickBestFieldValue($summaries, 'generation', $engineNames);
        $bestVehicle = $this->pickBestFieldValue($summaries, 'vehicle', $engineNames);
        $bestCategory = $this->pickBestFieldValue($summaries, 'category', $engineNames);
        $bestColor = $this->pickBestFieldValue($summaries, 'color', $engineNames);
        $bestPlateColor = $this->computeCompositePlateAttributes($summaries, $engineNames);
        $bestLocalText = $this->computeCompositeLocalText($summaries, $engineNames);
        $bestBbox = $this->computeCompositeBbox($summaries, $engineNames);
        $bestViewport = $this->pickBestFieldValue($summaries, 'viewport', $engineNames);
        $bestQuality = $this->computeCompositeQuality($summaries, $engineNames);
        $bestTags = $this->pickBestFieldValue($summaries, 'tags', $engineNames);

        // Fastest Latency
        $bestLatency = null;
        foreach ($summaries as $engKey => $sum) {
            if (!isset($sum['time_ms']) || !is_numeric($sum['time_ms'])) {
                continue;
            }
            $tNum = (float) $sum['time_ms'];
            if ($bestLatency === null || $tNum < $bestLatency['time_num']) {
                $bestLatency = [
                    'time_ms' => (string) $sum['time_ms'],
                    'time_num' => $tNum,
                    'engine_key' => $engKey,
                    'engine' => $engineNames[$engKey],
                    'engine_label' => $engineNames[$engKey],
                ];
            }
        }

        return [
            'best_plate' => $compositeBestPlate ? substr($compositeBestPlate, 0, 128) : null,
            'best_region' => $bestRegionObj ? substr($bestRegionObj['formatted'], 0, 191) : null,
            'best_confidence' => $bestPlateEngine ? ($bestPlateEngine['confidence'] . '% (' . $bestPlateEngine['engine'] . ')') : null,
            'best_characters' => !empty($bestCharsFormattedParts) ? implode(' | ', $bestCharsFormattedParts) : null,
            'best_characters_json' => $bestCharsJson,
            'best_make' => $bestMake['formatted'] ?? null,
            'best_model' => $bestModel['formatted'] ?? null,
            'best_generation' => $bestGeneration['formatted'] ?? null,
            'best_vehicle' => $bestVehicle['formatted'] ?? null,
            'best_category' => $bestCategory['formatted'] ?? null,
            'best_color' => $bestColor['formatted'] ?? null,
            'best_plate_color' => $bestPlateColor['formatted'] ?? null,
            'best_orientation' => $bestViewport['formatted'] ?? null,
            'best_quality' => $bestQuality['formatted'] ?? null,
            'best_summary_json' => [
                'final_plate' => $compositeBestPlate,
                'composite_plate' => $compositeBestPlate,
                'assembled_number' => $assembledPlateNumber,
                'plate' => $bestPlateEngine,
                'region' => $bestRegionObj,
                'characters' => $bestCharsJson,
                'make' => $bestMake,
                'model' => $bestModel,
                'generation' => $bestGeneration,
                'vehicle' => $bestVehicle,
                'category' => $bestCategory,
                'color' => $bestColor,
                'plate_color' => $bestPlateColor,
                'local_text' => $bestLocalText,
                'bbox' => $bestBbox,
                'viewport' => $bestViewport,
                'orientation' => $bestViewport,
                'quality' => $bestQuality,
                'tags' => $bestTags,
                'fastest' => $bestLatency,
            ],
        ];
    }

    /**
     * Compute Current Logic selection:
     * 1. Reject any OCR where overall confidence < 95% OR any individual character confidence < 95%.
     * 2. Among qualified OCRs, pick the one with highest average character confidence.
     * 3. If tie (both same, e.g. 99%), pick deterministically.
     * 4. If all rejected (<95%), mark status as 'none_qualified' with reasons.
     */
    public function computeCurrentLogic(array $summaries): array
    {
        $engineNames = [
            'candid' => 'Candid',
            'eydea' => 'Eydea',
            'arh' => 'ARH',
            'parkonic' => 'Parkonic',
        ];

        $evaluations = [];
        $qualifiedList = [];

        foreach ($engineNames as $engKey => $engLabel) {
            $sum = $summaries[$engKey] ?? [];
            $plate = isset($sum['plate']) ? trim((string) $sum['plate']) : '';
            $isSuccess = !empty($sum['success']) && $plate !== '' && $plate !== 'N/A' && $plate !== '-';

            if (!$isSuccess) {
                $evaluations[$engKey] = [
                    'engine_key' => $engKey,
                    'engine_label' => $engLabel,
                    'plate' => null,
                    'overall_conf' => null,
                    'avg_conf' => null,
                    'avg_score' => 0.0,
                    'is_qualified' => false,
                    'status' => 'rejected',
                    'reason' => 'No Plate / Failed',
                    'failing_items' => ['No Plate / Failed'],
                    'characters' => [],
                    'summary' => $sum,
                ];
                continue;
            }

            $overallConfStr = $this->normalizeConfidence($sum['confidence'] ?? 0) ?? '0';
            $overallConfNum = (float) $overallConfStr;

            $chars = $sum['characters_list'] ?? ($sum['characters'] ?? ($sum['characters_json'] ?? []));
            $failingItems = [];
            $charConfSum = 0.0;
            $charCount = 0;
            $cleanChars = [];

            if ($overallConfNum < 95.0) {
                $failingItems[] = 'Overall OCR Conf ' . $overallConfStr . '% < 95%';
            }

            if (is_array($chars) && count($chars) > 0) {
                foreach ($chars as $c) {
                    if (!is_array($c)) continue;
                    $chVal = trim((string) ($c['character'] ?? ''));
                    if ($chVal === '') continue;
                    $chConfStr = $this->normalizeConfidence($c['confidence'] ?? ($c['conf'] ?? 0)) ?? '0';
                    $chConfNum = (float) $chConfStr;
                    $charConfSum += $chConfNum;
                    $charCount++;
                    $cleanChars[] = [
                        'character' => $chVal,
                        'confidence' => $chConfStr,
                        'score' => $chConfNum,
                    ];
                    if ($chConfNum < 95.0) {
                        $failingItems[] = "'" . $chVal . "' (" . $chConfStr . "% < 95%)";
                    }
                }
            }

            $avgScore = ($charCount > 0) ? ($charConfSum / $charCount) : $overallConfNum;
            $avgScoreStr = $this->normalizeConfidence($avgScore) ?? '0';

            $isQualified = empty($failingItems);
            $reason = $isQualified ? 'All values >= 95%' : implode(', ', $failingItems);

            $eval = [
                'engine_key' => $engKey,
                'engine_label' => $engLabel,
                'plate' => $plate,
                'overall_conf' => $overallConfStr,
                'avg_conf' => $avgScoreStr,
                'avg_score' => $avgScore,
                'is_qualified' => $isQualified,
                'status' => $isQualified ? 'qualified' : 'rejected',
                'reason' => $reason,
                'failing_items' => $failingItems,
                'characters' => $cleanChars,
                'summary' => $sum,
            ];

            $evaluations[$engKey] = $eval;

            if ($isQualified) {
                $qualifiedList[$engKey] = $eval;
            }
        }

        // 1. Check for ANY character/plate mismatch among all engines that detected a plate
        $detectedPlates = [];
        $normalizedPlates = [];
        $mismatchDetails = [];

        foreach ($engineNames as $engKey => $engLabel) {
            $sum = $summaries[$engKey] ?? [];
            $plate = isset($sum['plate']) ? trim((string) $sum['plate']) : '';
            $isSuccess = !empty($sum['success']) && $plate !== '' && $plate !== 'N/A' && $plate !== '-';
            if ($isSuccess) {
                $detectedPlates[$engKey] = $plate;
                $clean = preg_replace('/[^A-Za-z0-9\x{0600}-\x{06FF}]/u', '', $plate);
                $norm = mb_strtoupper((string) $clean, 'UTF-8');
                $normalizedPlates[$engKey] = $norm;
                $mismatchDetails[] = $engLabel . ": '" . $plate . "'";
            }
        }

        // Find best candidate among evaluated engines
        $candidateKey = null;
        if (!empty($qualifiedList)) {
            $sortedQual = $qualifiedList;
            uasort($sortedQual, function ($a, $b) {
                if ($b['avg_score'] > $a['avg_score']) return 1;
                if ($b['avg_score'] < $a['avg_score']) return -1;
                return 0;
            });
            $candidateKey = array_key_first($sortedQual);
        } else {
            $detected = array_filter($evaluations, function ($e) {
                return !empty($e['plate']) && $e['reason'] !== 'No Plate / Failed';
            });
            if (!empty($detected)) {
                uasort($detected, function ($a, $b) {
                    $scoreA = $a['avg_score'] ?? 0;
                    $scoreB = $b['avg_score'] ?? 0;
                    if ($scoreB > $scoreA) return 1;
                    if ($scoreB < $scoreA) return -1;
                    return 0;
                });
                $candidateKey = array_key_first($detected);
            }
        }
        $candidate = $candidateKey ? ($evaluations[$candidateKey] ?? null) : null;

        $distinctPlates = array_unique(array_values($normalizedPlates));
        if (count($distinctPlates) > 1) {
            // "Any ocr will missmatch then that plate mismatch, any condition missmatch all engins rejected"
            return [
                'status' => 'mismatch',
                'selected_engine' => null,
                'selected_engine_label' => null,
                'selected_plate' => null,
                'selected_confidence' => null,
                'selected_summary' => null,
                'candidate_engine' => $candidate['engine_key'] ?? null,
                'candidate_engine_label' => $candidate['engine_label'] ?? null,
                'candidate_plate' => $candidate['plate'] ?? null,
                'candidate_confidence' => $candidate['avg_conf'] ?? null,
                'candidate_summary' => $candidate['summary'] ?? null,
                'candidate' => $candidate,
                'winner' => null,
                'is_fallback' => false,
                'mismatch_reason' => 'Character mismatch: ' . implode(' vs ', $mismatchDetails),
                'evaluations' => $evaluations,
                'qualified_engines' => array_keys($qualifiedList),
            ];
        }

        // 2. Minimum 2 qualified engines required rule:
        // "want atleaset min 2 OCR aengines need, if only 1 engine meets also Mismatch"
        if (count($qualifiedList) === 1) {
            $singleKey = array_key_first($qualifiedList);
            $single = $qualifiedList[$singleKey];
            return [
                'status' => 'mismatch',
                'selected_engine' => null,
                'selected_engine_label' => null,
                'selected_plate' => null,
                'selected_confidence' => null,
                'selected_summary' => null,
                'candidate_engine' => $single['engine_key'] ?? null,
                'candidate_engine_label' => $single['engine_label'] ?? null,
                'candidate_plate' => $single['plate'] ?? null,
                'candidate_confidence' => $single['avg_conf'] ?? null,
                'candidate_summary' => $single['summary'] ?? null,
                'candidate' => $single,
                'winner' => null,
                'is_fallback' => false,
                'mismatch_reason' => 'Minimum 2 OCR engines required (only ' . $single['engine_label'] . ' qualified >= 95%)',
                'evaluations' => $evaluations,
                'qualified_engines' => array_keys($qualifiedList),
            ];
        }

        // 3. If >= 2 qualified engines (all agree and >= 95%), pick from qualified engines (highest avg confidence)
        if (count($qualifiedList) >= 2) {
            uasort($qualifiedList, function ($a, $b) {
                if ($b['avg_score'] > $a['avg_score']) return 1;
                if ($b['avg_score'] < $a['avg_score']) return -1;
                return 0; // Maintain deterministic order if equal
            });

            $winnerKey = array_key_first($qualifiedList);
            $winner = $qualifiedList[$winnerKey];

            return [
                'status' => 'qualified',
                'selected_engine' => $winner['engine_key'],
                'selected_engine_label' => $winner['engine_label'],
                'selected_plate' => $winner['plate'],
                'selected_confidence' => $winner['avg_conf'],
                'selected_summary' => $winner['summary'] ?? null,
                'candidate_engine' => $winner['engine_key'],
                'candidate_engine_label' => $winner['engine_label'],
                'candidate_plate' => $winner['plate'],
                'candidate_confidence' => $winner['avg_conf'],
                'candidate_summary' => $winner['summary'] ?? null,
                'candidate' => $winner,
                'winner' => $winner,
                'is_fallback' => false,
                'evaluations' => $evaluations,
                'qualified_engines' => array_keys($qualifiedList),
            ];
        }

        // 4. None qualified (all engines had values < 95%) -> Directly reject, no OCR takes decision
        return [
            'status' => 'none_qualified',
            'selected_engine' => null,
            'selected_engine_label' => null,
            'selected_plate' => null,
            'selected_confidence' => null,
            'selected_summary' => null,
            'candidate_engine' => $candidate['engine_key'] ?? null,
            'candidate_engine_label' => $candidate['engine_label'] ?? null,
            'candidate_plate' => $candidate['plate'] ?? null,
            'candidate_confidence' => $candidate['avg_conf'] ?? null,
            'candidate_summary' => $candidate['summary'] ?? null,
            'candidate' => $candidate,
            'winner' => null,
            'is_fallback' => false,
            'evaluations' => $evaluations,
            'qualified_engines' => [],
        ];
    }
}
