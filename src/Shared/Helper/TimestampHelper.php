<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use App\Infrastructure\Clock\SystemClock;

final class TimestampHelper
{
    private static ?SystemClock $clock = null;

    public static function setClock(SystemClock $clock): void
    {
        self::$clock = $clock;
    }

    public static function now(): string
    {
        if (self::$clock === null) {
            self::$clock = new SystemClock();
        }

        return self::$clock->now()->format('Y-m-d H:i:s');
    }

    public static function utcNow(): string
    {
        if (self::$clock === null) {
            self::$clock = new SystemClock();
        }

        return self::$clock->now()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }

    public static function format(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    public static function formatUtc(\DateTimeInterface $dateTime): string
    {
        $dt = new \DateTime($dateTime->format('Y-m-d H:i:s'), $dateTime->getTimezone());

        return $dt
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }
}
