import assert from 'node:assert/strict';
import test from 'node:test';

import { chartAnimationOptions } from '../../resources/js/chart-preferences.js';

test('reduced motion forces chart animations off', () => {
    assert.deepEqual(chartAnimationOptions({ enabled: true, speed: 800 }, true), {
        enabled: false,
        speed: 800,
    });
});

test('normal motion preserves explicitly disabled animations', () => {
    assert.deepEqual(chartAnimationOptions({ enabled: false }, false), { enabled: false });
});

test('normal motion preserves explicitly enabled animations', () => {
    assert.deepEqual(chartAnimationOptions({ enabled: true }, false), { enabled: true });
});

test('normal motion enables animations by default and preserves other properties', () => {
    assert.deepEqual(chartAnimationOptions({ speed: 450, easing: 'easeinout' }, false), {
        speed: 450,
        easing: 'easeinout',
        enabled: true,
    });
});
