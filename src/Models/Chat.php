<?php

namespace Garissman\LaraChain\Models;

use App\Models\User;
use Garissman\LaraChain\Observers\ChatObserver;
use Garissman\LaraChain\Structures\Classes\MetaDataDto;
use Garissman\LaraChain\Structures\Classes\ToolsDto;
use Garissman\LaraChain\Structures\Enums\ChatStatuesEnum;
use Garissman\LaraChain\Structures\Enums\DriversEnum;
use Garissman\LaraChain\Structures\Enums\RoleEnum;
use Garissman\LaraChain\Structures\Interfaces\HasDrivers;
use Garissman\LaraChain\Structures\Traits\HasDriversTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property string $session_id;
 * @property Collection|Message[] $messages
 * @property mixed $title
 * @property DriversEnum $embedding_driver
 * @property DriversEnum $chat_driver
 * @property Agent $agent
 * @property mixed $metadata
 * @method static whereNull(string $string)
 * @method static find(mixed $chat_id)
 * @method addInputWithTools(string $sprintf, $param, $param1, $id, $param2, $param3, $name, $param4, $param5, $arguments)
 * @method static create(array $array)
 */
#[ObservedBy([ChatObserver::class])]
class Chat extends Model implements HasDrivers
{
    use HasDriversTrait;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'chat_status' => ChatStatuesEnum::class,
        'chat_driver' => DriversEnum::class,
        'embedding_driver' => DriversEnum::class,
        'metadata' => 'json'
    ];

    public function addInput(
        string       $message,
        RoleEnum     $role = RoleEnum::User,
        ?string      $systemPrompt = null,
        bool         $show_in_thread = true,
        ?MetaDataDto $meta_data = null,
        ?ToolsDto    $tools = null,
        bool         $is_been_whisper = false): Message
    {
        if (!$meta_data) {
            $meta_data = MetaDataDto::from([]);
        }


        return DB::transaction(function () use ($message, $role, $tools, $show_in_thread, $meta_data, $is_been_whisper) {

            $this->createSystemMessageIfNeeded();

            return $this->messages()->create(
                [
                    'body' => $message,
                    'role' => $role,
                    'in_out' => $role === RoleEnum::User,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'chat_id' => $this->id,
                    'user_id' => ($role === RoleEnum::User && auth()->check()) ? auth()->user()->id : null,
                    'is_chat_ignored' => !$show_in_thread,
                    'is_been_whisper' => $is_been_whisper,
                    'meta_data' => $meta_data,
                    'tool_name' => $meta_data->tool,
                    'tool_id' => $meta_data->tool_id,
                    'driver' => $meta_data->driver,
                    'args' => $meta_data->args,
                    'tools' => $tools,
                ]);
        });

    }

    protected function createSystemMessageIfNeeded(): void
    {
        if ($this->messages()->count() == 0) {
            $this->messages()->create(
                [
                    'body' => $this->agent->context,
                    'in_out' => false,
                    'role' => RoleEnum::System,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'chat_id' => $this->id,
                    'is_chat_ignored' => true,
                ]);
        }
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function latest_messages(): HasMany
    {
        return $this->hasMany(Message::class)->where('is_chat_ignored', false)->oldest();
    }

    function getDriver(): DriversEnum
    {
        return $this->chat_driver;
    }

    function getEmbeddingDriver(): DriversEnum
    {
        return $this->embedding_driver;
    }
}
