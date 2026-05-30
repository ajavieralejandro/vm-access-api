<?php

namespace App\Enums;

enum AccessDecisionReason: string
{
    case Ok             = 'ok';
    case InvalidQr      = 'invalid_qr';
    case ExpiredPass    = 'expired_pass';
    case RevokedPass    = 'revoked_pass';
    case InactiveZone   = 'inactive_zone';
    case InvalidZone    = 'invalid_zone';
    case NotYetValid    = 'not_yet_valid';
    case UsedPass       = 'used_pass';
    case MissingZone    = 'missing_zone';
    case ValidationError = 'validation_error';
}
