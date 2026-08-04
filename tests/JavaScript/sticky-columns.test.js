import assert from 'node:assert/strict';
import test from 'node:test';

import { shorterColumnIndex } from '../../resources/js/frontend/sticky-columns.js';

test('shorter column is selected for sticky positioning', () => {
    assert.equal(shorterColumnIndex([600, 1200]), 0);
    assert.equal(shorterColumnIndex([1500, 700]), 1);
});

test('equal or incomplete columns do not select a sticky column', () => {
    assert.equal(shorterColumnIndex([900, 900]), null);
    assert.equal(shorterColumnIndex([900]), null);
});
