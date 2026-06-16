<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function perPage(Request $request): int
    {
        $value = $request->string('per_page')->value();

        if ($value === 'all') {
            return PHP_INT_MAX;
        }

        return in_array($value, ['10', '25', '50', '100', '500'], true)
            ? (int) $value
            : 10;
    }
}
