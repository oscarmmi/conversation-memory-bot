<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryFact extends Model
{
    protected $fillable = ['session_id', 'key', 'value', 'source_turn_id'];

    /** @return BelongsTo<ChatSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    /** @return BelongsTo<ChatTurn, $this> */
    public function sourceTurn(): BelongsTo
    {
        return $this->belongsTo(ChatTurn::class, 'source_turn_id');
    }
}
