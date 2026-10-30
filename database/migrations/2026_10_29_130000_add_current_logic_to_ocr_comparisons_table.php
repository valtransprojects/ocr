<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            $table->string('current_logic_engine', 32)->nullable()->after('best_summary_json');
            $table->string('current_logic_plate', 128)->nullable()->after('current_logic_engine');
            $table->string('current_logic_confidence', 32)->nullable()->after('current_logic_plate');
            $table->string('current_logic_status', 32)->nullable()->after('current_logic_confidence'); // 'qualified', 'none_qualified'
            $table->json('current_logic_json')->nullable()->after('current_logic_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            $table->dropColumn([
                'current_logic_engine',
                'current_logic_plate',
                'current_logic_confidence',
                'current_logic_status',
                'current_logic_json',
            ]);
        });
    }
};

