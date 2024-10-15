import { isInViewport, animateOnScrollWithThrottle } from './common.js';

function addSlideUpAnimation(elementSelector, offset = 100) {
    const elements = document.querySelectorAll(elementSelector);

    if (elements.length === 0) {
        console.error(`No elements found with selector '${elementSelector}'`);
        return;
    }

    // Function to apply the slide-up class
    function applySlideUp(element) {
        element.classList.add('slide-up-active');
    }

    // Function to check if element is in the viewport with an offset
    function checkIfInViewport(element) {
        return isInViewport(element, offset);
    }

    // Use animateOnScrollWithThrottle with a function that handles animation
    animateOnScrollWithThrottle(
        Array.from(elements),
        (element) => {
            if (checkIfInViewport(element)) {
                applySlideUp(element);
            }
        },
        300 // Adjust throttle delay if needed
    );
}

// Example usage: Add the slide-up animation to elements with a specific class or selector
addSlideUpAnimation('.slide-up');
