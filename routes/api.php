<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\StandingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMatchController;
use App\Http\Controllers\TeamPlayerController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentMatchController;
use App\Http\Controllers\TournamentTeamController;
use App\Models\GameMatch;
use Illuminate\Support\Facades\Route;

Route::model('match', GameMatch::class);
Route::patterns(array_fill_keys(['team', 'player', 'tournament', 'match'], '[0-9]{1,18}'));

Route::apiResource('teams', TeamController::class)->except('update');
Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
Route::get('teams/{team}/players', [TeamPlayerController::class, 'index'])->name('teams.players.index');
Route::post('teams/{team}/players', [TeamPlayerController::class, 'store'])->name('teams.players.store');
Route::get('teams/{team}/matches', [TeamMatchController::class, 'index'])->name('teams.matches.index');

Route::apiResource('players', PlayerController::class)->only(['index', 'show', 'destroy']);
Route::patch('players/{player}', [PlayerController::class, 'update'])->name('players.update');

Route::apiResource('tournaments', TournamentController::class)->except('update');
Route::patch('tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');

Route::prefix('tournaments/{tournament}')->name('tournaments.')->group(function () {
    Route::get('teams', [TournamentTeamController::class, 'index'])->name('teams.index');
    Route::post('teams', [TournamentTeamController::class, 'store'])->name('teams.store');
    Route::delete('teams/{team}', [TournamentTeamController::class, 'destroy'])->name('teams.destroy');

    Route::post('bracket', [BracketController::class, 'store'])->name('bracket.store');
    Route::get('bracket', [BracketController::class, 'show'])->name('bracket.show');
    Route::delete('bracket', [BracketController::class, 'destroy'])->name('bracket.destroy');

    Route::get('matches', [TournamentMatchController::class, 'index'])->name('matches.index');
    Route::get('standings', StandingsController::class)->name('standings');
});

Route::get('matches/{match}', [MatchController::class, 'show'])->name('matches.show');
Route::patch('matches/{match}', [MatchController::class, 'update'])->name('matches.update');
Route::put('matches/{match}/result', [MatchController::class, 'result'])->name('matches.result');
