<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoom extends Model
{
    protected $fillable = ['code', 'host_user_id', 'guest_user_id', 'mode', 'status', 'settings', 'team_size', 'teams'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'teams' => 'array'];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (self::where('code', $code)->where('status', '!=', 'finished')->exists());
        return $code;
    }
}
