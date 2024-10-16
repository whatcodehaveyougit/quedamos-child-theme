import { animateOnScrollWithThrottle } from './common.js';

console.log('jkasfdjkdsfjkldfslkj')
function addZoomInAnimation(elementSelector) {
    const elements = document.querySelectorAll(elementSelector);
    if (elements.length === 0) {
        console.error(`No elements found with selector '${elementSelector}'`);
        return;
    }

    // Function to apply the zoom-in class
    function applyZoomIn(element) {
        console.log('sigurd')
        element.classList.add('zoom-in');
    }

    // Trigger the animation immediately for elements already in the viewport on page load
    animateOnScrollWithThrottle(Array.from(elements), applyZoomIn);
}

// Example usage: Add the zoom-in animation to elements with a specific class or selector
addZoomInAnimation('.zoom-element'); // Replace with your actual element selector



// const throttledScrollHandler1 = throttle(() => runOnScroll('.zoom-element', triggerCountAnimation), 300);

// // Throttling the function with an anonymous wrapper to pass the selector
// window.addEventListener('scroll', throttledScrollHandler1);