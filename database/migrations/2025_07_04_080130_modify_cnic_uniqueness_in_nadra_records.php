<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class ModifyCnicUniquenessInNadraRecords extends Migration
{
    public function up()
    {
        Schema::table('nadra_records', function (Blueprint $table) {
            // Drop the existing unique index on cnic_number
            if (Schema::hasIndex('nadra_records', 'cnic_number_unique')) {
                $table->dropUnique(['cnic_number']);
            }

            // Add composite unique index on cnic_number + file_upload_id
            $table->unique(['cnic_number', 'file_upload_id'], 'unique_cnic_per_upload');
        });
    }

    public function down()
    {
        Schema::table('nadra_records', function (Blueprint $table) {
            // Drop the composite index
            if (Schema::hasIndex('nadra_records', 'unique_cnic_per_upload')) {
                $table->dropUnique(['cnic_number', 'file_upload_id']);
            }

            // Restore the original unique index on cnic_number
            $table->unique('cnic_number', 'cnic_number_unique');
        });
    }
}
