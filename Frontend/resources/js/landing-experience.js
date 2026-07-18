import Lenis from 'lenis';
import { animate, inView, stagger } from 'motion';
import { shouldEnableMotion } from './motion-preferences.js';

const noop = () => {};

export const initLandingExperience = () => {
    if (!document.querySelector('[data-page="landing"]')) return noop;

    const motionPreference = window.matchMedia?.('(prefers-reduced-motion: reduce)');
    const reducedMotion = motionPreference?.matches ?? false;
    if (!shouldEnableMotion(reducedMotion)) return noop;

    const lenis = new Lenis({ duration: 1.05, smoothWheel: true });
    let frameId;

    const update = (time) => {
        lenis.raf(time);
        frameId = requestAnimationFrame(update);
    };

    frameId = requestAnimationFrame(update);

    const heroAnimation = animate(
        '[data-hero-item]',
        { opacity: [0, 1], y: [18, 0] },
        { duration: 0.55, delay: stagger(0.08), ease: 'ease-out' },
    );
    const revealObservers = [...document.querySelectorAll('[data-reveal]')].map((element) => inView(element, () => {
        const reveal = animate(
            element,
            { opacity: [0, 1], y: [16, 0] },
            { duration: 0.45, ease: 'ease-out' },
        );

        return () => reveal.stop();
    }, { margin: '-10% 0px' }));

    return () => {
        cancelAnimationFrame(frameId);
        heroAnimation.stop();
        revealObservers.forEach((stopObserving) => stopObserving());
        lenis.destroy();
    };
};
