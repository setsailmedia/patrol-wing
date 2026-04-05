<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loadout extends Model
{
    protected $fillable = ['user_id', 'craft_id', 'weapons'];

    protected function casts(): array
    {
        return ['weapons' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
