export const throttle = (fn, delay) => {
    // Set up the throttlerconst throttle = (fn, delay) => {
    let time = Date.now();
    return () => {
        if ((time + delay - Date.now()) <= 0) {
            fn();
            time = Date.now();
        }
    };
}
export function isInViewport(element, offset = 0) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top < (window.innerHeight || document.documentElement.clientHeight) - offset &&
        rect.bottom > 0
    );
}
export function runOnScroll(selector, animationFn) {
    const element = document.querySelector(selector);
    if (element) {
        if(isInViewport(element)){
            animationFn(element)
        }
    } else {
        console.log('Element not found for selector:', selector);
    }
}
export function animateOnScrollWithThrottle(elements, animationFn, delay = 300) {
    // Convert NodeList or other iterable to an array if it's not already an array
    const elementsArray = Array.isArray(elements) ? elements : Array.from(elements);

    let isChecking = false;
    const animationState = new Map(elements.map(element => [element, false]));

    // Function to check each element and apply animation if it's in the viewport
    function checkAndAnimate() {
        elementsArray.forEach((element) => {
            if (isInViewport(element)) {
                animationFn(element);
                animationState.set(element, true); // Mark as animated to prevent reanimation
                return true;
            }
        });
    }

    // Throttle function to limit how often checkAndAnimate is triggered
    function onScroll() {
        if (!isChecking) {
            isChecking = true;
            requestAnimationFrame(() => {
                checkAndAnimate(); // Check elements on scroll
                isChecking = false; // Reset the flag after the check
            });
        }
    }

    // Initial check on page load for any elements already in the viewport
    checkAndAnimate();

    // Throttled scroll event listener with reduced delay
    window.addEventListener('scroll', throttle(onScroll, delay));
}
