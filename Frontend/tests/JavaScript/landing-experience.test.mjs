import assert from 'node:assert/strict';
import test from 'node:test';

import { createLandingExperience } from '../../resources/js/landing-experience.js';

const createRuntime = ({ reducedMotion = false } = {}) => {
    const listeners = new Map();
    const root = {
        attributes: new Map(),
        setAttribute(name, value) {
            this.attributes.set(name, value);
        },
        removeAttribute(name) {
            this.attributes.delete(name);
        },
        hasAttribute(name) {
            return this.attributes.has(name);
        },
    };
    const mediaQuery = {
        matches: reducedMotion,
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
        removeEventListener(type, listener) {
            if (listeners.get(type) === listener) listeners.delete(type);
        },
        change(matches) {
            this.matches = matches;
            listeners.get('change')?.({ matches });
        },
        hasChangeListener() {
            return listeners.has('change');
        },
    };
    const stats = {
        cancelledFrames: [],
        heroStops: 0,
        lenisDestroys: 0,
        observerDisconnects: 0,
        revealStops: 0,
    };
    let enterReveal = () => {};
    let exitReveal = () => {};

    class FakeLenis {
        raf() {}
        destroy() {
            stats.lenisDestroys += 1;
        }
    }

    const runtime = {
        document: {
            querySelector: () => root,
            querySelectorAll: () => [{}],
        },
        matchMedia: () => mediaQuery,
        requestAnimationFrame: () => 41,
        cancelAnimationFrame: (frame) => stats.cancelledFrames.push(frame),
        Lenis: FakeLenis,
        animate: (target) => {
            const isHero = typeof target === 'string';
            return {
                stop: () => {
                    if (isHero) stats.heroStops += 1;
                    else stats.revealStops += 1;
                },
            };
        },
        inView: (_element, onEnter) => {
            let onExit;
            enterReveal = () => {
                onExit = onEnter();
            };
            exitReveal = () => onExit?.();

            return () => {
                stats.observerDisconnects += 1;
            };
        },
        stagger: () => 0,
    };

    return {
        enterReveal: () => enterReveal(),
        exitReveal: () => exitReveal(),
        mediaQuery,
        root,
        runtime,
        stats,
    };
};

test('a reduced-motion preference change fully tears down active landing motion', () => {
    const fixture = createRuntime();
    const cleanup = createLandingExperience(fixture.runtime)();

    assert.equal(fixture.root.hasAttribute('data-motion-ready'), true);
    fixture.enterReveal();
    fixture.mediaQuery.change(true);

    assert.deepEqual(fixture.stats.cancelledFrames, [41]);
    assert.equal(fixture.stats.lenisDestroys, 1);
    assert.equal(fixture.stats.observerDisconnects, 1);
    assert.equal(fixture.stats.heroStops, 1);
    assert.equal(fixture.mediaQuery.hasChangeListener(), false);
    assert.equal(fixture.stats.revealStops, 1);
    assert.equal(fixture.root.hasAttribute('data-motion-ready'), false);
    assert.equal(fixture.mediaQuery.hasChangeListener(), false);
    assert.equal(fixture.stats.lenisDestroys, 1);
    cleanup();
    assert.equal(fixture.stats.lenisDestroys, 1);
});

test('landing motion cleanup is idempotent', () => {
    const fixture = createRuntime();
    const cleanup = createLandingExperience(fixture.runtime)();

    cleanup();
    cleanup();

    assert.deepEqual(fixture.stats.cancelledFrames, [41]);
    assert.equal(fixture.stats.lenisDestroys, 1);
    assert.equal(fixture.stats.observerDisconnects, 1);
    assert.equal(fixture.stats.heroStops, 1);
});

test('leaving a reveal stops and removes its animation control', () => {
    const fixture = createRuntime();
    const cleanup = createLandingExperience(fixture.runtime)();

    fixture.enterReveal();
    fixture.exitReveal();
    cleanup();

    assert.equal(fixture.stats.revealStops, 1);
});

test('landing readiness only marks an active motion runtime', () => {
    const reducedFixture = createRuntime({ reducedMotion: true });
    const reducedCleanup = createLandingExperience(reducedFixture.runtime)();

    assert.equal(reducedFixture.root.hasAttribute('data-motion-ready'), false);
    assert.equal(reducedFixture.stats.lenisDestroys, 0);
    reducedCleanup();

    const activeFixture = createRuntime();
    const activeCleanup = createLandingExperience(activeFixture.runtime)();
    assert.equal(activeFixture.root.hasAttribute('data-motion-ready'), true);
    activeCleanup();
    assert.equal(activeFixture.root.hasAttribute('data-motion-ready'), false);
});
