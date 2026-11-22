<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Retired = 'retired';
}
