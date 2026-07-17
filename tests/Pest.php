<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function hqJrpChecklistFor(\App\Models\RentalContract $contract, bool $withMofOrEpu = false): array
{
    $checklist = [
        \App\Support\HqJrpChecklist::AADK_RECEIVE_EPU_COMMENTS => '1',
        \App\Support\HqJrpChecklist::AADK_RECEIVE_MOF_COMMENTS => '1',
        \App\Support\HqJrpChecklist::AADK_SUBMIT_BPH => '1',
        \App\Support\HqJrpChecklist::AADK_RECEIVE_BPH_APPROVAL => '1',
        'dates' => collect(\App\Support\HqJrpChecklist::keysRequiringDate())
            ->mapWithKeys(fn (string $key): array => [$key => '2026-07-16'])
            ->all(),
    ];

    if ($withMofOrEpu) {
        $checklist[\App\Support\HqJrpChecklist::MOF] = '1';
        $checklist[\App\Support\HqJrpChecklist::KP] = '0';
    } else {
        $checklist[\App\Support\HqJrpChecklist::KP] = '1';
    }

    return ['jrp_checklist' => $checklist];
}
