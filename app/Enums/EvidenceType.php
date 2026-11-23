<?php

namespace App\Enums;

enum EvidenceType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Biological = 'biological';
    case Documentary = 'documentary';
    case Photographic = 'photographic';
    case Other = 'other';
}
