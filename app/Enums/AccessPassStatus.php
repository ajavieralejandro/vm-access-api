<?php

namespace App\Enums;

enum AccessPassStatus: string
{
    case Active  = 'active';
    case Used    = 'used';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
