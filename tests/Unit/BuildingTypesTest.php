<?php

use App\Support\BuildingTypes;

it('includes rumah kediaman among building types', function () {
    expect(BuildingTypes::all())
        ->toHaveKey(BuildingTypes::RUMAH_KEDIAMAN)
        ->and(BuildingTypes::all()[BuildingTypes::RUMAH_KEDIAMAN])->toBe('Rumah Kediaman')
        ->and(BuildingTypes::values())->toContain(BuildingTypes::RUMAH_KEDIAMAN);
});
