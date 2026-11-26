<?php

namespace App\Enums;

enum PunishmentType: string
{
    case Imprisonment = 'imprisonment';
    case Fine = 'fine';
    case Other = 'other';
}
