<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'tag', 'country'])]
class Team extends Model
{
    use HasFactory;

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team')
            ->withPivot('seed')
            ->withTimestamps();
    }

    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(fn (Builder $q) => $q
            ->where('teams.name', 'ilike', $like)
            ->orWhere('teams.tag', 'ilike', $like));
    }

    #[Scope]
    protected function fromCountry(Builder $query, string $country): void
    {
        $query->where('teams.country', strtoupper($country));
    }
}
