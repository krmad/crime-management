<?php

namespace App\Enums;

enum CustodyEvent: string
{
    case Collection = 'collection';
    case Transfer = 'transfer';
    case LocationChange = 'location_change';
    case Release = 'release';
    case Disposal = 'disposal';
}
