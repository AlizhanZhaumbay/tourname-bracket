<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->smallInteger('round');
            $table->smallInteger('position');
            $table->foreignId('team1_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team2_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->smallInteger('team1_score')->nullable();
            $table->smallInteger('team2_score')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('next_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->smallInteger('next_match_slot')->nullable();
            $table->enum('status', ['pending', 'ready', 'completed', 'bye'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'round', 'position']);
            $table->index('team1_id');
            $table->index('team2_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
