<?php

namespace App\Enums;

enum CaseStatus: string
{
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case JudgmentRecorded = 'judgment_recorded';
    case AwaitingJudicialReview = 'awaiting_judicial_review';
    case UnderJudicialReview = 'under_judicial_review';
    case JudgmentFinalized = 'judgment_finalized';
    case Closed = 'closed';
}
