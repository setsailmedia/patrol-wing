<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('craft_id', 30);
            $table->json('weapons');
            $table->timestamps();
            $table->unique(['user_id', 'craft_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadouts');
    }
};
