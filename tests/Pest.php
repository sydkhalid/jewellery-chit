<?php

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

expect()->extend('toBeSuccessfulApiEnvelope', function () {
    expect($this->value)
        ->toHaveKeys(['success', 'message', 'data'])
        ->and($this->value['success'])->toBeTrue();

    return $this;
});
