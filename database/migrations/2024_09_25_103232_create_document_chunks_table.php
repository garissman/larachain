<?php


use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Garissman\LaraChain\Structures\Enums\StructuredTypeEnum;
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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Document::class);
            $table->string('guid');
            $table->string('sort_order')->default(1);
            $table->longText('content')->nullable();
            $table->vector('embedding_1024', 1024)->nullable();
            $table->vector('embedding_1536', 1536)->nullable();
            $table->vector('embedding_2048', 2048)->nullable();
            $table->vector('embedding_3072', 3072)->nullable();
            $table->vector('embedding_4096', 4096)->nullable();
            $table->longText('original_content')->nullable();
            $table->string('status_embeddings')->default(StatusEnum::Pending);
            $table->string('status_tagging')->default(StatusEnum::Pending);
            $table->string('status_summary')->default(StatusEnum::Pending);
            $table->longText('summary')->nullable();
            $table->json('meta_data')->nullable();
            $table->integer('section_number')->nullable();
            $table->string('type')->default(StructuredTypeEnum::Raw->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
