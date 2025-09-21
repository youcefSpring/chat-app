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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->foreignId('uploader_id')->constrained('users');
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('mime_type', 100);
            $table->bigInteger('size')->unsigned();
            $table->json('metadata')->default('{}');
            $table->timestamps();

            // Add check constraint for file size (100MB max)
            $table->check('size > 0 AND size <= 104857600');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};