<?php

namespace App\Services\Bracket;

use InvalidArgumentException;

class SeedOrder
{
    public function forSize(int $size): array
    {
        if ($size < 2 || ($size & ($size - 1)) !== 0) {
            throw new InvalidArgumentException("Bracket size must be a power of two >= 2, got {$size}.");
        }

        $order = [1];

        while (count($order) < $size) {
            $newSize = count($order) * 2;
            $order = array_merge(...array_map(fn (int $seed) => [$seed, $newSize + 1 - $seed], $order));
        }

        return $order;
    }

    public function bracketSize(int $teams): int
    {
        $size = 2;

        while ($size < $teams) {
            $size *= 2;
        }

        return $size;
    }

    public static function roundName(int $round, int $totalRounds): string
    {
        return match ($totalRounds - $round) {
            0 => 'Final',
            1 => 'Semifinal',
            2 => 'Quarterfinal',
            default => 'Round of '.(2 ** ($totalRounds - $round + 1)),
        };
    }
}
