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
            $table->string('accepted_logic', 32)->nullable()->after('current_logic_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            $table->dropColumn('accepted_logic');
        });
    }
};

