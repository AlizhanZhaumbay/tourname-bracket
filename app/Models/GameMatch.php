<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id', 'round', 'position', 'team1_id', 'team2_id', 'team1_score', 'team2_score',
    'winner_id', 'next_match_id', 'next_match_slot', 'status', 'scheduled_at', 'completed_at',
])]
class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'round' => 'integer',
            'position' => 'integer',
            'team1_score' => 'integer',
            'team2_score' => 'integer',
            'next_match_slot' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function previousMatches(): HasMany
    {
        return $this->hasMany(self::class, 'next_match_id');
    }

    #[Scope]
    protected function involvingTeam(Builder $query, int $teamId): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('matches.team1_id', $teamId)
            ->orWhere('matches.team2_id', $teamId));
    }

    #[Scope]
    protected function withStatus(Builder $query, MatchStatus $status): void
    {
        $query->where('matches.status', $status);
    }

    #[Scope]
    protected function inRound(Builder $query, int $round): void
    {
        $query->where('matches.round', $round);
    }
}
