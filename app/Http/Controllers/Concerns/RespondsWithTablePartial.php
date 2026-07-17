<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RespondsWithTablePartial
{
    protected function wantsTablePartial(Request $request): bool
    {
        return $request->ajax() || $request->boolean('partial');
    }
}
