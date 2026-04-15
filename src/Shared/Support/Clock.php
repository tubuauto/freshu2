<?php

declare(strict_types=1);

namespace App\Shared\Support;

use DateTimeImmutable;
use DateTimeZone;

final class Clock
{
    public static function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function nowIsoUtc(): string
    {
        return self::nowUtc()->format('Y-m-d\\TH:i:s\\Z');
    }
}
