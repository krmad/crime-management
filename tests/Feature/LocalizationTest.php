<?php

test('incident location map strings are localized', function () {
    expect(__('Incident location map', [], 'en'))->toBe('Incident location map')
        ->and(__('Incident location map', [], 'am'))->toBe('የክስተት ቦታ ካርታ')
        ->and(__('Incident location map', [], 'om'))->toBe("Kaartaa bakka tasa'a");
});
