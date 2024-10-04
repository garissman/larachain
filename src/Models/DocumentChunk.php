<?php

namespace Garissman\LaraChain\Models;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Garissman\LaraChain\Structures\Enums\StructuredTypeEnum;
use Garissman\LaraChain\Structures\Interfaces\HasDrivers;
use Garissman\LaraChain\Structures\Interfaces\TaggableContract;
use Garissman\LaraChain\Structures\Traits\HasDriversTrait;
use Garissman\LaraChain\Structures\Traits\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Document $document
 * @property StructuredTypeEnum $type
 * @property string $content
 */
class DocumentChunk extends Model implements HasDrivers, TaggableContract
{
    use HasDriversTrait;
    use HasFactory;
    use HasNeighbors;
    use Taggable;

    protected $casts = [
        'embedding_3072' => Vector::class,
        'embedding_1536' => Vector::class,
        'embedding_2048' => Vector::class,
        'embedding_4096' => Vector::class,
        'status_embeddings' => StatusEnum::class,
        'status_tagging' => StatusEnum::class,
        'status_summary' => StatusEnum::class,
        'meta_data' => 'json',
        'type' => StructuredTypeEnum::class,
    ];

    protected $guarded = [];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($document_chunk) {
            $document_chunk->original_content = $document_chunk->getOriginal('content');
            $document_chunk->saveQuietly();
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function siblingTags(): array
    {
        return [];
    }

    public function getChatable(): HasDrivers
    {
        return $this->document->collection;
    }

    public function getChat(): ?Chat
    {
        /**
         * @TODO
         * I need to come back to this
         */
        return $this->document->collection->chats()->first();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return DocumentChunk::class;
    }

    public function getDriver(): string
    {
        return $this->document->document_driver;
    }

    public function getEmbeddingColumn(): string
    {
        $size=LaraChain::engine($this->getDriver())->getEmbeddingSize();
        return 'embedding_'.$size;
    }

    public function getEmbeddingDriver(): string
    {
        return $this->document->embedding_driver;
    }

    public function getSummary(): string
    {
        return $this->content;
    }
}
