<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNadraRecordsTable extends Migration
{

    public function up(): void
    {
        Schema::dropIfExists('nadra_records');

        Schema::create('nadra_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_upload_id')->nullable();

            $table->string('full_name');
            $table->string('father_name');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cnic_number')->unique();
            $table->string('family_id')->nullable();
            $table->text('addresses')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();

            $table->timestamps();

            $table->foreign('file_upload_id')
                  ->references('id')
                  ->on('file_uploads')
                  ->onDelete('set null');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('nadra_records');

        Schema::create('nadra_records', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->string('father_name');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cnic_number')->unique();
            $table->string('family_id')->nullable();
            $table->text('addresses')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();

            $table->timestamps();
        });
    }
}
