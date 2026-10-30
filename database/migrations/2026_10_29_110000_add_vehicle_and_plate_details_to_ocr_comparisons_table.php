<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleAndPlateDetailsToOcrComparisonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ocr_comparisons', function (Blueprint $table) {
            // Additional Best Pick columns across the 4 OCR engines
            $table->text('best_make')->nullable()->after('best_characters_json');
            $table->text('best_model')->nullable()->after('best_make');
            $table->text('best_generation')->nullable()->after('best_model');
            $table->text('best_vehicle')->nullable()->after('best_generation');
            $table->text('best_category')->nullable()->after('best_vehicle');
            $table->text('best_color')->nullable()->after('best_category');
            $table->text('best_plate_color')->nullable()->after('best_color');
            $table->text('best_orientation')->nullable()->after('best_plate_color');
            $table->text('best_quality')->nullable()->after('best_orientation');

            // 1. Candid extended details
            $table->text('candid_make')->nullable()->after('candid_vehicle');
            $table->text('candid_model')->nullable()->after('candid_make');
            $table->text('candid_generation')->nullable()->after('candid_model');
            $table->text('candid_plate_color')->nullable()->after('candid_color');
            $table->text('candid_local_text')->nullable()->after('candid_plate_color');
            $table->text('candid_bbox')->nullable()->after('candid_local_text');
            $table->longText('candid_details_json')->nullable()->after('candid_raw');

            // 2. Eydea extended details
            $table->text('eydea_make')->nullable()->after('eydea_vehicle');
            $table->text('eydea_model')->nullable()->after('eydea_make');
            $table->text('eydea_generation')->nullable()->after('eydea_model');
            $table->text('eydea_plate_color')->nullable()->after('eydea_color');
            $table->text('eydea_local_text')->nullable()->after('eydea_plate_color');
            $table->text('eydea_bbox')->nullable()->after('eydea_local_text');
            $table->longText('eydea_details_json')->nullable()->after('eydea_raw');

            // 3. ARH extended details
            $table->text('arh_make')->nullable()->after('arh_vehicle');
            $table->text('arh_model')->nullable()->after('arh_make');
            $table->text('arh_generation')->nullable()->after('arh_model');
            $table->text('arh_plate_color')->nullable()->after('arh_color');
            $table->text('arh_local_text')->nullable()->after('arh_plate_color');
            $table->text('arh_bbox')->nullable()->after('arh_local_text');
            $table->longText('arh_details_json')->nullable()->after('arh_raw');

            // 4. Parkonic extended details
            $table->text('parkonic_make')->nullable()->after('parkonic_vehicle');
            $table->text('parkonic_model')->nullable()->after('parkonic_make');
            $table->text('parkonic_generation')->nullable()->after('parkonic_model');
            $table->text('parkonic_plate_color')->nullable()->after('parkonic_color');
            $table->text('parkonic_local_text')->nullable()->after('parkonic_plate_color');
            $table->text('parkonic_bbox')->nullable()->after('parkonic_local_text');
            $table->longText('parkonic_details_json')->nullable()->after('parkonic_raw');
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
                'best_make',
                'best_model',
                'best_generation',
                'best_vehicle',
                'best_category',
                'best_color',
                'best_plate_color',
                'best_orientation',
                'best_quality',
                'candid_make',
                'candid_model',
                'candid_generation',
                'candid_plate_color',
                'candid_local_text',
                'candid_bbox',
                'candid_details_json',
                'eydea_make',
                'eydea_model',
                'eydea_generation',
                'eydea_plate_color',
                'eydea_local_text',
                'eydea_bbox',
                'eydea_details_json',
                'arh_make',
                'arh_model',
                'arh_generation',
                'arh_plate_color',
                'arh_local_text',
                'arh_bbox',
                'arh_details_json',
                'parkonic_make',
                'parkonic_model',
                'parkonic_generation',
                'parkonic_plate_color',
                'parkonic_local_text',
                'parkonic_bbox',
                'parkonic_details_json',
            ]);
        });
    }
}

