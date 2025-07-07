<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNadraRecordsTable extends Migration
{

    public function up()
    {
        Schema::create('nadra_records', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to file_uploads table
            $table->unsignedBigInteger('file_upload_id');
            
            // Personal information fields
            $table->string('full_name', 255);
            $table->string('father_name', 255);
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('date_of_birth');
            $table->string('cnic_number', 15); // Format: 12345-1234567-1
            $table->string('family_id', 255)->nullable();
            $table->text('addresses')->nullable();
            $table->string('province', 255)->nullable();
            $table->string('district', 255)->nullable();
            
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('file_upload_id')
                  ->references('id')
                  ->on('file_uploads')
                  ->onDelete('cascade'); // Delete records when file upload is deleted
            
            // Composite unique index: CNIC must be unique within each file upload
            $table->unique(['cnic_number', 'file_upload_id'], 'unique_cnic_per_upload');
            
            // Additional indexes for better query performance
            $table->index('file_upload_id');
            $table->index('cnic_number');
            $table->index('full_name');
            $table->index(['province', 'district']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('nadra_records');
    }
}