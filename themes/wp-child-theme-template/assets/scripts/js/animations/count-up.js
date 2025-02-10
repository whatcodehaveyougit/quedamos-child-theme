import { runOnScroll, throttle } from './common.js';

function doCountAnimation(element, targetNumber, duration = 1000) {
    if (!element) {
        console.error(`Element with selector '${elementSelector}' not found.`);
        return;
    }
    const startTime = performance.now();
    function updateCount(currentTime) {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);
        const currentNumber = Math.floor(progress * targetNumber);
        element.textContent = currentNumber;
        if (progress < 1) {
            requestAnimationFrame(updateCount); // requestAnimationFrame() is build in browser function
        }
    }
    requestAnimationFrame(updateCount);
}


function triggerCountAnimation(element){
    doCountAnimation(element.querySelector('div.wp-block-group:nth-child(1) p:nth-child(1)'), 31);
    doCountAnimation(element.querySelector('div.wp-block-group:nth-child(2) p:nth-child(1)'), 7);
    doCountAnimation(element.querySelector('div.wp-block-group:nth-child(3) p:nth-child(1)'), 14);
    doCountAnimation(element.querySelector('.wp-block-group:nth-child(4) p:nth-child(1)'), 1000);
    window.removeEventListener('scroll', throttledScrollHandler);
}

const throttledScrollHandler = throttle(() => runOnScroll('.big-numbers-container-home .wp-block-group', triggerCountAnimation), 300);

// Throttling the function with an anonymous wrapper to pass the selector
window.addEventListener('scroll', throttledScrollHandler);

