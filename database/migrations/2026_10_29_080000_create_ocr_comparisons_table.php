<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOcrComparisonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ocr_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 64)->nullable()->index();

            // Image Info
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('image_path');
            $table->string('mime_type', 64)->nullable();
            $table->string('file_extension', 16)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->string('file_hash', 64)->nullable()->index();

            // Engine target
            $table->string('selected_engine', 32)->default('all');

            // 1. Candid Engine (All Parameters + Character-wise)
            $table->string('candid_plate', 64)->nullable();
            $table->string('candid_region', 191)->nullable();
            $table->string('candid_confidence', 64)->nullable();
            $table->text('candid_characters')->nullable(); // e.g. "A=99.42%, 1=98.15%, 2=99.01%"
            $table->json('candid_characters_json')->nullable();
            $table->string('candid_vehicle', 191)->nullable(); // Make & Model with conf
            $table->string('candid_category', 128)->nullable(); // Body Type / Category with conf
            $table->string('candid_color', 128)->nullable(); // Vehicle Color with conf
            $table->string('candid_viewport', 64)->nullable(); // View Port with conf
            $table->string('candid_quality', 128)->nullable(); // Cropped Quality
            $table->text('candid_tags')->nullable(); // Active Vehicle Tags
            $table->string('candid_time_ms', 64)->nullable();
            $table->boolean('candid_success')->default(false);
            $table->json('candid_raw')->nullable();

            // 2. Eydea Engine (All Parameters + Character-wise)
            $table->string('eydea_plate', 64)->nullable();
            $table->string('eydea_region', 191)->nullable();
            $table->string('eydea_confidence', 64)->nullable();
            $table->text('eydea_characters')->nullable();
            $table->json('eydea_characters_json')->nullable();
            $table->string('eydea_vehicle', 191)->nullable();
            $table->string('eydea_category', 128)->nullable();
            $table->string('eydea_color', 128)->nullable();
            $table->string('eydea_viewport', 64)->nullable();
            $table->string('eydea_quality', 128)->nullable();
            $table->text('eydea_tags')->nullable();
            $table->string('eydea_time_ms', 64)->nullable();
            $table->boolean('eydea_success')->default(false);
            $table->json('eydea_raw')->nullable();

            // 3. ARH Engine (parkonic key - All Parameters + Character-wise)
            $table->string('arh_plate', 64)->nullable();
            $table->string('arh_region', 191)->nullable();
            $table->string('arh_confidence', 64)->nullable();
            $table->text('arh_characters')->nullable();
            $table->json('arh_characters_json')->nullable();
            $table->string('arh_vehicle', 191)->nullable();
            $table->string('arh_category', 128)->nullable();
            $table->string('arh_color', 191)->nullable();
            $table->string('arh_viewport', 64)->nullable();
            $table->string('arh_quality', 128)->nullable();
            $table->text('arh_tags')->nullable();
            $table->string('arh_time_ms', 64)->nullable();
            $table->boolean('arh_success')->default(false);
            $table->json('arh_raw')->nullable();

            // 4. Parkonic Engine (anpr2 key - All Parameters + Character-wise)
            $table->string('parkonic_plate', 64)->nullable();
            $table->string('parkonic_region', 191)->nullable();
            $table->string('parkonic_confidence', 64)->nullable();
            $table->text('parkonic_characters')->nullable();
            $table->json('parkonic_characters_json')->nullable();
            $table->string('parkonic_vehicle', 191)->nullable();
            $table->string('parkonic_category', 128)->nullable();
            $table->string('parkonic_color', 128)->nullable();
            $table->string('parkonic_viewport', 64)->nullable();
            $table->string('parkonic_quality', 128)->nullable();
            $table->text('parkonic_tags')->nullable();
            $table->string('parkonic_time_ms', 64)->nullable();
            $table->boolean('parkonic_success')->default(false);
            $table->json('parkonic_raw')->nullable();

            $table->timestamps();
        });

        Schema::create('ocr_comparison_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocr_comparison_id')
                ->constrained('ocr_comparisons')
                ->onDelete('cascade');
            $table->string('engine', 32)->index(); // candid, eydea, arh, parkonic
            $table->unsignedSmallInteger('plate_index')->default(0);
            $table->unsignedSmallInteger('char_position'); // 1, 2, 3...
            $table->string('character', 16);
            $table->string('confidence', 64)->nullable();
            $table->json('raw_char_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ocr_comparison_characters');
        Schema::dropIfExists('ocr_comparisons');
    }
}
