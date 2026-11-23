<?php

namespace App\Enums;

enum EvidenceStatus: string
{
    case Collected = 'collected';
    case Stored = 'stored';
    case Transferred = 'transferred';
    case Released = 'released';
    case Disposed = 'disposed';
}
