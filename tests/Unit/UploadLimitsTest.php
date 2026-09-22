<?php

use App\Support\UploadLimits;

test('upload limits effective max respects php ini ceiling', function () {
    expect(UploadLimits::effectiveMaxKilobytes())->toBeLessThanOrEqual(UploadLimits::APP_MAX_KILOBYTES)
        ->and(UploadLimits::humanEffectiveLimit())->not->toBe('');
});

test('app upload limit is ten megabytes', function () {
    expect(UploadLimits::APP_MAX_KILOBYTES)->toBe(10240)
        ->and(UploadLimits::humanAppLimit())->toBe('10MB');
});

test('upload failure message for ini size mentions php limit when server limit is lower', function () {
    $message = UploadLimits::uploadFailureMessage(UPLOAD_ERR_INI_SIZE);

    expect($message)->toContain('Saiz fail melebihi');
});
