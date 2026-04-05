<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomLevel extends Model
{
    protected $fillable = ['user_id', 'pack_name', 'level_data', 'is_public', 'downloads'];

    protected function casts(): array
    {
        return ['level_data' => 'array', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
