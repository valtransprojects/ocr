<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBestPicksToOcrComparisonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            $table->string('best_plate', 128)->nullable()->after('selected_engine');
            $table->string('best_region', 191)->nullable()->after('best_plate');
            $table->string('best_confidence', 64)->nullable()->after('best_region');
            $table->text('best_characters')->nullable()->after('best_confidence');
            $table->json('best_characters_json')->nullable()->after('best_characters');
            $table->json('best_summary_json')->nullable()->after('best_characters_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            $table->dropColumn([
                'best_plate',
                'best_region',
                'best_confidence',
                'best_characters',
                'best_characters_json',
                'best_summary_json',
            ]);
        });
    }
}
