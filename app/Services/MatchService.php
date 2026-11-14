<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Carbon;

class MatchService
{
    public function scheduleMatch(GameMatch $match, ?Carbon $scheduledAt): GameMatch
    {
        $match->update(['scheduled_at' => $scheduledAt]);

        return $match->refresh();
    }
}
