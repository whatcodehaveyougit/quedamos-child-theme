import { isInViewport, animateOnScrollWithThrottle } from './common.js';

document.addEventListener('DOMContentLoaded', () => {
    function animateCountUp(elementSelector, targetNumber, duration = 1000) {
        const element = document.querySelector(elementSelector);

        if (!element) {
            console.error(`Element with selector '${elementSelector}' not found.`);
            return;
        }

        // Function to perform the count animation
        function doCountAnimation() {
            const startTime = performance.now();

            function updateCount(currentTime) {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(elapsedTime / duration, 1);
                const currentNumber = Math.floor(progress * targetNumber);
                element.textContent = currentNumber;
                if (progress < 1) {
                    requestAnimationFrame(updateCount);
                }
            }
            requestAnimationFrame(updateCount);
        }

        function checkAndAnimate() {
            if (isInViewport(element)) {
                console.log('Element is in viewport, triggering count-up animation.'); // Debugging log
                doCountAnimation();
                // Optionally remove the element from the list if you don't want to animate it again
                // elements = elements.filter(e => e !== element);
            }
        }

        // Use animateOnScrollWithThrottle with the updated checkAndAnimate function
        animateOnScrollWithThrottle([element], checkAndAnimate);
    }

    // Example usage for multiple elements
    animateCountUp('.big-numbers-container-home .wp-block-group > .wp-block-group p:nth-child(1)', 21); // First element
    animateCountUp('.big-numbers-container-home .wp-block-group > .wp-block-group:nth-child(2) p:nth-child(1)', 8); // Second element
    animateCountUp('.big-numbers-container-home .wp-block-group > .wp-block-group:nth-child(3) p:nth-child(1)', 6); // Third element
    animateCountUp('.big-numbers-container-home .wp-block-group > .wp-block-group:nth-child(4) p:nth-child(1)', 1000); // Fourth element
});
