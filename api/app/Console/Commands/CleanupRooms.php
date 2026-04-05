<?php

namespace App\Console\Commands;

use App\Models\GameRoom;
use Illuminate\Console\Command;

class CleanupRooms extends Command
{
    protected $signature = 'rooms:cleanup';
    protected $description = 'Clean up orphaned game rooms older than 30 minutes';

    public function handle()
    {
        $count = GameRoom::whereIn('status', ['waiting', 'playing'])
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'finished']);

        $deleted = GameRoom::where('status', 'finished')
            ->where('updated_at', '<', now()->subHours(24))
            ->delete();

        $this->info("Closed {$count} stale rooms, deleted {$deleted} old finished rooms.");
    }
}
