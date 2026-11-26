<?php

namespace App\Enums;

enum JudgmentVerdict: string
{
    case Guilty = 'guilty';
    case NotGuilty = 'not_guilty';
}
