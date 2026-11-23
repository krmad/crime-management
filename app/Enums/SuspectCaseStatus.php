<?php

namespace App\Enums;

enum SuspectCaseStatus: string
{
    case Identified = 'identified';
    case UnderInvestigation = 'under_investigation';
    case Cleared = 'cleared';
    case Charged = 'charged';
}
