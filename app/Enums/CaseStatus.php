<?php

namespace App\Enums;

enum CaseStatus: string
{
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case JudgmentRecorded = 'judgment_recorded';
    case Closed = 'closed';
}
