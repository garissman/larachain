<?php

use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_driver')->nullable();
            $table->string('embedding_driver')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default(StatusEnum::Pending);
            $table->longText('summary')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('document_chunk_count')->nullable();
            $table->string('status_summary')->nullable();
            $table->json('meta_data')->nullable();
            $table->longText('subject')->nullable();
            $table->longText('link')->nullable();
            $table->foreignIdFor(Document::class, 'parent_id')->nullable();
            $table->string('child_type')->nullable();
            $table->longText('content')->nullable();
            $table->longText('original_content')->nullable();
            $table->string('document_md5')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
