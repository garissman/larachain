<?php

namespace Garissman\LaraChain\Models;


use App\Models\User;
use Garissman\LaraChain\Observers\MessageObserver;
use Garissman\LaraChain\Structures\Classes\MetaDataDto;
use Garissman\LaraChain\Structures\Classes\ToolsDto;
use Garissman\LaraChain\Structures\Enums\RoleEnum;
use Garissman\LaraChain\Structures\Interfaces\HasDrivers;
use Garissman\LaraChain\Structures\Traits\HasDriversTrait;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Bus;

/**
 * @property mixed|string $body
 * @property RoleEnum $role
 * @property Chat $chat
 * @property mixed $chat_id
 * @property array $args
 * @property bool $is_been_whisper
 *
 * @method static where(string $string, mixed $get)
 */
#[ObservedBy([MessageObserver::class])]
class Message extends Model implements HasDrivers
{
    use HasDriversTrait;
    use BroadcastsEvents;
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'role' => RoleEnum::class,
        'tools' => ToolsDto::class,
        'meta_data' => MetaDataDto::class,
        'args' => 'array',
        'in_out' => 'boolean',
    ];

    public function broadcastOn(string $event): array
    {
        return [$this, "chat." . $this->chat_id];
    }

    /**
     * Return true if the message is from the user.
     */
    public function getFromUserAttribute(): bool
    {
        return $this->role === RoleEnum::User;
    }

    /**
     * Return true if the message is from the AI.
     */
    public function getFromAiAttribute(): bool
    {
        return $this->role !== RoleEnum::User;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNotSystem(Builder $query)
    {
        return $query->where('role', '!=', RoleEnum::System->value);
    }

    public function scopeNotTool(Builder $query)
    {
        return $query->where('role', '!=', RoleEnum::Tool->value);
    }

    /**
     * Return a compressed message
     */
    public function getCompressedBodyAttribute(): string
    {
        return $this->compressMessage($this->body);
    }

    /**
     * Compress a message
     */
    public function compressMessage($message): array|string|null
    {
        if (!config('temp.compressed_messages')) {
            return $message;
        }

        // Remove spaces
        $body = str_replace(' ', '', $message);

        // Remove punctuation
        return preg_replace('/\p{P}/', '', $body);

    }

    /**
     * Return the chat that the message belongs to.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    protected function batchJob(array $jobs, Chat $chat, string $function): void
    {
        $driver = $chat->getDriver();
        Bus::batch($jobs)
            ->name("Orchestrate Chat - {$chat->id} {$function} {$driver}")
            ->then(function (Batch $batch) use ($chat) {
                ChatUiUpdateEvent::dispatch(
                    $chat->getChatable(),
                    $chat,
                    UiStatusEnum::Complete->name
                );
            })
            ->allowFailures()
            ->dispatch();
    }

    public function getChatable(): HasDrivers
    {
        return $this->chat->getChatable();
    }
}
