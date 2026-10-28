<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = ['session_key'];

    /** @return HasMany<ChatTurn, $this> */
    public function turns(): HasMany
    {
        return $this->hasMany(ChatTurn::class, 'session_id');
    }

    /** @return HasMany<MemoryFact, $this> */
    public function memoryFacts(): HasMany
    {
        return $this->hasMany(MemoryFact::class, 'session_id');
    }
}
