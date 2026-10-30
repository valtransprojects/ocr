<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OcrComparison extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ocr_comparisons';

    protected $fillable = [
        'batch_id',
        'original_filename',
        'stored_filename',
        'image_path',
        'mime_type',
        'file_extension',
        'file_size_bytes',
        'image_width',
        'image_height',
        'file_hash',
        'selected_engine',

        // Best Pick / 4-OCR Consensus
        'best_plate',
        'best_region',
        'best_confidence',
        'best_characters',
        'best_characters_json',
        'best_make',
        'best_model',
        'best_generation',
        'best_vehicle',
        'best_category',
        'best_color',
        'best_plate_color',
        'best_orientation',
        'best_quality',
        'best_summary_json',

        // Current Logic (Strict 95% threshold + Highest Average)
        'current_logic_engine',
        'current_logic_plate',
        'current_logic_confidence',
        'current_logic_status',
        'current_logic_json',
        'accepted_logic',

        // 1. Candid
        'candid_plate',
        'candid_region',
        'candid_confidence',
        'candid_characters',
        'candid_characters_json',
        'candid_vehicle',
        'candid_make',
        'candid_model',
        'candid_generation',
        'candid_category',
        'candid_color',
        'candid_plate_color',
        'candid_local_text',
        'candid_bbox',
        'candid_viewport',
        'candid_quality',
        'candid_tags',
        'candid_time_ms',
        'candid_success',
        'candid_raw',
        'candid_details_json',

        // 2. Eydea
        'eydea_plate',
        'eydea_region',
        'eydea_confidence',
        'eydea_characters',
        'eydea_characters_json',
        'eydea_vehicle',
        'eydea_make',
        'eydea_model',
        'eydea_generation',
        'eydea_category',
        'eydea_color',
        'eydea_plate_color',
        'eydea_local_text',
        'eydea_bbox',
        'eydea_viewport',
        'eydea_quality',
        'eydea_tags',
        'eydea_time_ms',
        'eydea_success',
        'eydea_raw',
        'eydea_details_json',

        // 3. ARH
        'arh_plate',
        'arh_region',
        'arh_confidence',
        'arh_characters',
        'arh_characters_json',
        'arh_vehicle',
        'arh_make',
        'arh_model',
        'arh_generation',
        'arh_category',
        'arh_color',
        'arh_plate_color',
        'arh_local_text',
        'arh_bbox',
        'arh_viewport',
        'arh_quality',
        'arh_tags',
        'arh_time_ms',
        'arh_success',
        'arh_raw',
        'arh_details_json',

        // 4. Parkonic Engine
        'parkonic_plate',
        'parkonic_region',
        'parkonic_confidence',
        'parkonic_characters',
        'parkonic_characters_json',
        'parkonic_vehicle',
        'parkonic_make',
        'parkonic_model',
        'parkonic_generation',
        'parkonic_category',
        'parkonic_color',
        'parkonic_plate_color',
        'parkonic_local_text',
        'parkonic_bbox',
        'parkonic_viewport',
        'parkonic_quality',
        'parkonic_tags',
        'parkonic_time_ms',
        'parkonic_success',
        'parkonic_raw',
        'parkonic_details_json',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'image_width' => 'integer',
        'image_height' => 'integer',

        'best_characters_json' => 'array',
        'best_summary_json' => 'array',
        'current_logic_json' => 'array',

        'candid_characters_json' => 'array',
        'candid_success' => 'boolean',
        'candid_raw' => 'array',
        'candid_details_json' => 'array',

        'eydea_characters_json' => 'array',
        'eydea_success' => 'boolean',
        'eydea_raw' => 'array',
        'eydea_details_json' => 'array',

        'arh_characters_json' => 'array',
        'arh_success' => 'boolean',
        'arh_raw' => 'array',
        'arh_details_json' => 'array',

        'parkonic_characters_json' => 'array',
        'parkonic_success' => 'boolean',
        'parkonic_raw' => 'array',
        'parkonic_details_json' => 'array',
    ];

    protected $appends = [
        'image_url',
        'formatted_file_size',
    ];

    public function characters()
    {
        return $this->hasMany(OcrComparisonCharacter::class, 'ocr_comparison_id')
            ->orderBy('engine')
            ->orderBy('plate_index')
            ->orderBy('char_position');
    }

    public function getImageUrlAttribute(): string
    {
        return route('ocr.image', ['filename' => $this->stored_filename]);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (int) $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
