<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class StudentBillingStart
{
    private const AUGUST_START_UNITS = ['MTS', 'MA', 'PONPES'];

    public static function unitCodes(): array
    {
        return self::AUGUST_START_UNITS;
    }

    public static function defaultFor(?string $unitCode, mixed $entryDate): ?string
    {
        if (! in_array(strtoupper(trim((string) $unitCode)), self::unitCodes(), true)) {
            return null;
        }

        if (blank($entryDate)) {
            return null;
        }

        try {
            $year = CarbonImmutable::parse($entryDate)->year;
        } catch (Throwable) {
            return null;
        }

        return sprintf('%04d-08-01', $year);
    }
}
