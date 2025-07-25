<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstitutionalEmailsTable extends Migration
{
    /**
     * Run the migrations.
     * Why we create this table:
     * - Store institutional email information
     * - Track email creation status
     * - Maintain audit trail
     * - Enable email management features
     */
    public function up()
    {
        Schema::create('institutional_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Link to existing users table
            $table->string('username')->unique(); // Auto-generated username from first module
            $table->string('email')->unique(); // Created institutional email
            $table->string('first_name');
            $table->string('last_name');
            $table->string('department')->nullable();
            $table->string('google_user_id')->nullable(); // Google's unique user ID
            $table->string('status')->default('pending'); // pending, active, suspended, deleted
            $table->string('password')->nullable(); // Encrypted temporary password
            $table->json('google_response')->nullable(); // Store Google API response
            $table->timestamp('email_created_at')->nullable(); // When email was created in Google
            $table->timestamp('last_synced_at')->nullable(); // Last sync with Google
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index('username');
            $table->index('email');
            
            // Foreign key constraint (assuming users table exists)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('institutional_emails');
    }
}
