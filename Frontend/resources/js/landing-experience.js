import Lenis from 'lenis';
import { animate, inView, stagger } from 'motion';
import { shouldEnableMotion } from './motion-preferences.js';

const noop = () => {};

const listenForMotionChanges = (motionPreference, listener) => {
    if (motionPreference?.addEventListener) {
        motionPreference.addEventListener('change', listener);
        return () => motionPreference.removeEventListener('change', listener);
    }

    if (motionPreference?.addListener) {
        motionPreference.addListener(listener);
        return () => motionPreference.removeListener(listener);
    }

    return noop;
};

export const createLandingExperience = (runtime) => () => {
    const landingRoot = runtime.document.querySelector('[data-page="landing"]');
    if (!landingRoot) return noop;

    const motionPreference = runtime.matchMedia?.('(prefers-reduced-motion: reduce)');
    if (!shouldEnableMotion(motionPreference?.matches ?? false)) return noop;

    let active = true;
    let frameId;
    let heroAnimation;
    let lenis;
    let removeMotionListener = noop;
    const revealAnimations = new Set();
    const revealObservers = [];

    const cleanup = () => {
        if (!active) return;
        active = false;

        removeMotionListener();
        landingRoot.removeAttribute('data-motion-ready');
        if (frameId !== undefined) runtime.cancelAnimationFrame(frameId);
        heroAnimation?.stop();
        revealObservers.forEach((stopObserving) => stopObserving());
        revealAnimations.forEach((reveal) => reveal.stop());
        revealAnimations.clear();
        lenis?.destroy();
    };

    try {
        lenis = new runtime.Lenis({ duration: 1.05, smoothWheel: true });
        const update = (time) => {
            if (!active) return;

            lenis.raf(time);
            frameId = runtime.requestAnimationFrame(update);
        };

        frameId = runtime.requestAnimationFrame(update);
        heroAnimation = runtime.animate(
            '[data-hero-item]',
            { opacity: [0, 1], y: [18, 0] },
            { duration: 0.55, delay: runtime.stagger(0.08), ease: 'ease-out' },
        );
        runtime.document.querySelectorAll('[data-reveal]').forEach((element) => {
            revealObservers.push(runtime.inView(element, () => {
                const reveal = runtime.animate(
                    element,
                    { opacity: [0, 1], y: [16, 0] },
                    { duration: 0.45, ease: 'ease-out' },
                );
                revealAnimations.add(reveal);

                return () => {
                    if (revealAnimations.delete(reveal)) reveal.stop();
                };
            }, { margin: '-10% 0px' }));
        });
        removeMotionListener = listenForMotionChanges(motionPreference, ({ matches }) => {
            if (matches) cleanup();
        });
        landingRoot.setAttribute('data-motion-ready', '');

        return cleanup;
    } catch {
        cleanup();
        return noop;
    }
};

export const initLandingExperience = () => createLandingExperience({
    document,
    matchMedia: window.matchMedia?.bind(window),
    requestAnimationFrame: window.requestAnimationFrame.bind(window),
    cancelAnimationFrame: window.cancelAnimationFrame.bind(window),
    Lenis,
    animate,
    inView,
    stagger,
})();
