<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 30);
            $table->unsignedInteger('score');
            $table->unsignedInteger('duration_ms');
            $table->unsignedTinyInteger('wave_reached')->default(0);
            $table->string('craft_id', 30);
            $table->string('level_name', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['mode', 'score']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
