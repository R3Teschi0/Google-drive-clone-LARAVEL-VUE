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
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId("file_id")->constrained('files')->restrictOnDelete();
            $table->string('original_name', 1024);
            $table->string('storage_path', 1024)->nullable();
            $table->string('mime');
            $table->string('size');
            $table->integer('version');
            $table->text('hash');
            $table->string('extension', 1024);
            $table->string('uuid', 1024);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
