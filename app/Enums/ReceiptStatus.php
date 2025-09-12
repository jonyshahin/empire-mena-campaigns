<?php

namespace App\Enums;

enum ReceiptStatus: int
{
    case Draft     = 1;
    case Submitted = 2;
    case Posted    = 3;
    case Canceled  = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Submitted => 'Submitted',
            self::Posted    => 'Posted',
            self::Canceled  => 'Canceled',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Submitted], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Posted, self::Canceled], true);
    }
}
