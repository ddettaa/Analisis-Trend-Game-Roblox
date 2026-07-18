export const chartAnimationOptions = (options = {}, reduceMotion = false) => ({
    ...options,
    enabled: reduceMotion ? false : (options.enabled ?? true),
});
