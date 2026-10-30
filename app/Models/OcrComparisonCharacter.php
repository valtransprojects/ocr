<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcrComparisonCharacter extends Model
{
    use HasFactory;

    protected $table = 'ocr_comparison_characters';

    protected $fillable = [
        'ocr_comparison_id',
        'engine',
        'plate_index',
        'char_position',
        'character',
        'confidence',
        'raw_char_data',
    ];

    protected $casts = [
        'ocr_comparison_id' => 'integer',
        'plate_index' => 'integer',
        'char_position' => 'integer',
        'raw_char_data' => 'array',
    ];

    public function comparison()
    {
        return $this->belongsTo(OcrComparison::class, 'ocr_comparison_id');
    }
}

