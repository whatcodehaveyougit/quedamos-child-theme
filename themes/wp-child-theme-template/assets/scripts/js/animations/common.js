
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

// Modified runOnScroll to accept an element selector as a parameter
export function runOnScroll(selector, animationFn) {
    const element = document.querySelector(selector);
    if (element ) {
        if(isInViewport(element)){
            animationFn(element)
        }
    } else {
        console.log('Element not found for selector:', selector);
    }
}
