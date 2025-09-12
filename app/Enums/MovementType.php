<?php

namespace App\Enums;

enum MovementType: int
{
    case IN          = 1;
    case OUT         = 2;
    case TRANSFER_IN = 3;
    case TRANSFER_OUT = 4;
    case ADJUST_IN   = 5;
    case ADJUST_OUT  = 6;

    public function label(): string
    {
        return match ($this) {
            self::IN          => 'Stock In',
            self::OUT         => 'Stock Out',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::ADJUST_IN   => 'Adjustment In',
            self::ADJUST_OUT  => 'Adjustment Out',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::IN, self::TRANSFER_IN, self::ADJUST_IN], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::OUT, self::TRANSFER_OUT, self::ADJUST_OUT], true);
    }
}
