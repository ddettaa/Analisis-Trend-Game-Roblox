import test from 'node:test';
import assert from 'node:assert/strict';
import { shouldEnableMotion } from '../../resources/js/motion-preferences.js';

test('motion is disabled when reduced motion is requested', () => assert.equal(shouldEnableMotion(true), false));
test('motion is enabled when reduced motion is not requested', () => assert.equal(shouldEnableMotion(false), true));
