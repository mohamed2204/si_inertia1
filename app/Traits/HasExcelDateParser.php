<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasExcelDateParser
{
    protected function excelDateToCarbon($value): ?Carbon
    {
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(
                ($value - 25569) * 86400
            )->startOfDay();
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
