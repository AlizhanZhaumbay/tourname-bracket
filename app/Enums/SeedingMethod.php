<?php

namespace App\Enums;

enum SeedingMethod: string
{
    case Random = 'random';
    case Manual = 'manual';
    case RegistrationOrder = 'registration_order';
}
