<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileUploadsTable extends Migration
{

    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('category', 100); // Category like year or department
            $table->integer('total_records')->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('category');
            $table->index('uploaded_at');
        });
    }


    public function down()
    {
        Schema::dropIfExists('file_uploads');
    }
}