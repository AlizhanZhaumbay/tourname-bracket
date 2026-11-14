<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Completed = 'completed';
    case Bye = 'bye';
}
