// Custom throttle function
let lastTime = Date.now();

const throttle = (fn, delay) => {
    return () => {
        const now = Date.now();
        if (now - lastTime >= delay) {
            fn();
            lastTime = now;
        }
    };
};

export function isInViewport(element, offset = 0) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top < (window.innerHeight || document.documentElement.clientHeight) - offset &&
        rect.bottom > 0
    );
}

export function animateOnScrollWithThrottle(elements, animationFn, delay = 300) {
    let isChecking = false;
    const animationState = new Map(elements.map(element => [element, false]));

    function checkAndAnimate() {
        elements.forEach((element) => {
            if (isInViewport(element) && !animationState.get(element)) {
                animationFn(element);
                animationState.set(element, true);
            }
        });
    }

    function onScroll() {
        if (!isChecking) {
            isChecking = true;
            requestAnimationFrame(() => {
                checkAndAnimate();
                isChecking = false;
            });
        }
    }

    // Initial check on page load to handle elements already in the viewport
    checkAndAnimate();

    // Throttled scroll event listener
    window.addEventListener('scroll', throttle(onScroll, 1000));
}
