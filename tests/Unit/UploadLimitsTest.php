<?php

use App\Support\UploadLimits;

test('upload limits effective max respects php ini ceiling', function () {
    expect(UploadLimits::effectiveMaxKilobytes())->toBeLessThanOrEqual(UploadLimits::APP_MAX_KILOBYTES)
        ->and(UploadLimits::humanEffectiveLimit())->not->toBe('');
});

test('upload failure message for ini size mentions php limit when server limit is lower', function () {
    $message = UploadLimits::uploadFailureMessage(UPLOAD_ERR_INI_SIZE);

    expect($message)->toContain('Saiz fail melebihi');
});
