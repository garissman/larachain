<?php

namespace Garissman\LaraChain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $context
 * @method static create(array $array)
 */
class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function chat(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
