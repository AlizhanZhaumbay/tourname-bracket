<?php

namespace App\Models;

use App\Enums\TournamentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'game', 'max_teams', 'status', 'champion_team_id', 'starts_at'])]
class Tournament extends Model
{
    use HasFactory;

    public const array ALLOWED_SIZES = [4, 8, 16, 32];

    protected function casts(): array
    {
        return [
            'status' => TournamentStatus::class,
            'max_teams' => 'integer',
            'starts_at' => 'datetime',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team')
            ->withPivot('seed')
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }

    #[Scope]
    protected function withStatus(Builder $query, TournamentStatus $status): void
    {
        $query->where('tournaments.status', $status);
    }

    #[Scope]
    protected function forGame(Builder $query, string $game): void
    {
        $query->where('tournaments.game', 'ilike', $game);
    }
}
