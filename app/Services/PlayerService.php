<?php

namespace App\Services;

use App\Models\Player;

class PlayerService
{
    public function createPlayer(array $attributes): Player
    {
        return Player::query()->create($attributes);
    }

    public function updatePlayer(Player $player, array $attributes): Player
    {
        $player->update($attributes);

        return $player->refresh();
    }

    public function deletePlayer(Player $player): void
    {
        $player->delete();
    }
}
