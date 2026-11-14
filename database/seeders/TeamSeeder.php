<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    private const array TEAMS = [
        ['Northern Wolves', 'NWLV', 'SE'], ['Crimson Foxes', 'CRFX', 'DE'], ['Iron Lions', 'IRLN', 'GB'],
        ['Silent Owls', 'SOWL', 'FI'], ['Steppe Eagles', 'STEG', 'KZ'], ['Blue Sharks', 'BSHK', 'US'],
        ['Golden Tigers', 'GTGR', 'BR'], ['Frost Giants', 'FRGT', 'NO'], ['Desert Scorpions', 'DSCR', 'AE'],
        ['Thunder Bulls', 'THBL', 'ES'], ['Shadow Ravens', 'SHRV', 'PL'], ['Royal Stags', 'RSTG', 'FR'],
        ['Night Panthers', 'NPTH', 'UA'], ['Storm Falcons', 'STFL', 'DK'], ['Jade Dragons', 'JDRG', 'CN'],
        ['Red Bears', 'RBRS', 'CZ'],
    ];

    public function run(): void
    {
        foreach (self::TEAMS as [$name, $tag, $country]) {
            Team::factory()
                ->withPlayers(5)
                ->create(['name' => $name, 'tag' => $tag, 'country' => $country]);
        }

        Player::factory()->freeAgent()->count(3)->create();
    }
}
