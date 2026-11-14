<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('game', 50);
            $table->smallInteger('max_teams');
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->foreignId('champion_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
