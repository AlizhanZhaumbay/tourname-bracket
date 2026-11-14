<?php

namespace Tests\Unit\Services\Bracket;

use App\Services\Bracket\SeedOrder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SeedOrderTest extends TestCase
{
    public static function sizes(): array
    {
        return [
            '4 teams' => [4, [1, 4, 2, 3]],
            '8 teams' => [8, [1, 8, 4, 5, 2, 7, 3, 6]],
            '16 teams' => [16, [1, 16, 8, 9, 4, 13, 5, 12, 2, 15, 7, 10, 3, 14, 6, 11]],
        ];
    }

    #[Test]
    #[DataProvider('sizes')]
    public function it_builds_the_standard_seed_order(int $size, array $expected): void
    {
        $this->assertSame($expected, (new SeedOrder)->forSize($size));
    }

    #[Test]
    #[DataProvider('sizes')]
    public function top_two_seeds_are_in_opposite_halves(int $size): void
    {
        $order = (new SeedOrder)->forSize($size);
        $half = intdiv($size, 2);

        $this->assertContains(1, array_slice($order, 0, $half));
        $this->assertContains(2, array_slice($order, $half));
    }

    #[Test]
    #[DataProvider('sizes')]
    public function every_first_round_pair_sums_to_size_plus_one(int $size): void
    {
        foreach (array_chunk((new SeedOrder)->forSize($size), 2) as [$a, $b]) {
            $this->assertSame($size + 1, $a + $b);
        }
    }

    #[Test]
    public function it_rejects_sizes_that_are_not_powers_of_two(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SeedOrder)->forSize(6);
    }

    #[Test]
    public function it_rounds_bracket_size_up_to_power_of_two(): void
    {
        $seedOrder = new SeedOrder;

        $this->assertSame(2, $seedOrder->bracketSize(2));
        $this->assertSame(4, $seedOrder->bracketSize(3));
        $this->assertSame(8, $seedOrder->bracketSize(6));
        $this->assertSame(16, $seedOrder->bracketSize(9));
        $this->assertSame(32, $seedOrder->bracketSize(32));
    }

    #[Test]
    public function it_names_rounds_from_the_end(): void
    {
        $this->assertSame(
            ['Round of 32', 'Round of 16', 'Quarterfinal', 'Semifinal', 'Final'],
            array_map(fn (int $round) => SeedOrder::roundName($round, 5), range(1, 5)),
        );
    }
}
