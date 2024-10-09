<?php

namespace Garissman\LaraChain\Models;


use Garissman\LaraChain\Structures\Enums\DriversEnum;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Garissman\LaraChain\Structures\Enums\StructuredTypeEnum;
use Garissman\LaraChain\Structures\Enums\TypesEnum;
use Garissman\LaraChain\Structures\Interfaces\HasDrivers;
use Garissman\LaraChain\Structures\Interfaces\TaggableContract;
use Garissman\LaraChain\Structures\Traits\HasDriversTrait;
use Garissman\LaraChain\Structures\Traits\Taggable;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Document
 *
 * @property int $id
 * @property int $collection_id
 * @property string|null $summary
 * @property string|null $original_content
 * @property string|null $file_path
 * @property StructuredTypeEnum $child_type
 * @property DriversEnum $document_driver
 * @property DriversEnum $embedding_driver
 * @property string|null $content
 *
 */
//#[ObservedBy([DocumentObserver::class])]
class Document extends Model implements HasDrivers, TaggableContract
{
    use HasDriversTrait;
    use HasFactory;
    use Taggable;
    use BroadcastsEvents;

    protected $guarded = [];

    protected $casts = [
        'type' => TypesEnum::class,
        'child_type' => StructuredTypeEnum::class,
        'status' => StatusEnum::class,
        'meta_data' => 'array',
        'summary_status' => StatusEnum::class,
        'document_driver' => DriversEnum::class,
        'embedding_driver' => DriversEnum::class,
    ];

    public function broadcastOn(string $event): array
    {
        return [$this, "document." . $this->id];
    }
    public function broadcastWith(string $event): array
    {
        return match ($event) {
            'created' => ['title' => $this->title],
            default => ['model' => [
                'id'=>$this->id,
                'status'=>$this->status,
                'file_path'=>$this->file_path,
                'summary'=>$this->summary,
            ]],
        };
    }

    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(Filter::class);
    }

    public function siblingTags(): array
    {
        return Tag::query()
            ->select('tags.name')
            ->join('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->join('document_chunks', 'document_chunks.document_id', '=', 'taggables.taggable_id')
            ->where('taggables.taggable_type', '=', DocumentChunk::class)
            ->where('document_chunks.document_id', '=', $this->id)
            ->distinct('name')
            ->get()
            ->pluck('name')
            ->toArray();
    }

    public function getContentAttribute(): string
    {
        return $this->summary ?? '';
    }

    public function getChatable(): HasDrivers
    {
        return $this->collection;
    }

    public function document_chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    function getDriver(): DriversEnum
    {
        return $this->document_driver;
    }

    function getEmbeddingDriver(): DriversEnum
    {
        return $this->embedding_driver;
    }
}
