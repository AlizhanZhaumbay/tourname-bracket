<?php

namespace Tests\Feature\Api;

use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorFormatTest extends TestCase
{
    use RefreshDatabase;

    public static function missingModels(): array
    {
        return [
            'team' => ['/api/teams/999', 'Team not found.'],
            'player' => ['/api/players/999', 'Player not found.'],
            'tournament' => ['/api/tournaments/999', 'Tournament not found.'],
            'match' => ['/api/matches/999', 'Match not found.'],
            'nested' => ['/api/tournaments/999/standings', 'Tournament not found.'],
        ];
    }

    #[Test]
    #[DataProvider('missingModels')]
    public function missing_models_return_readable_json_404_without_accept_header(string $uri, string $message): void
    {
        $this->get($uri)
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => $message]);
    }

    #[Test]
    public function unknown_endpoint_returns_json_404(): void
    {
        $this->get('/api/unknown')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Endpoint not found.']);
    }

    #[Test]
    public function non_numeric_or_too_large_ids_return_404(): void
    {
        $this->get('/api/teams/abc')->assertNotFound()->assertJsonStructure(['message']);
        $this->get('/api/matches/99999999999999999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    #[Test]
    public function validation_errors_are_json_without_accept_header(): void
    {
        $this->post('/api/teams', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['name', 'tag']]);
    }

    #[Test]
    public function conflicts_are_rendered_as_409_with_message(): void
    {
        $tournament = Tournament::factory()->inProgress()->create();

        $this->delete("/api/tournaments/{$tournament->id}")
            ->assertConflict()
            ->assertExactJson(['message' => 'Cannot delete a tournament that is in progress.']);
    }
}
