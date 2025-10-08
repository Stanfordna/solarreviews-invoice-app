/**
 * Function to darken or lighten background during invoice edit
 */
// const root = document.documentElement;
// const styles = getComputedStyle(root);

const backgroundOverlay = document.querySelector('background-overlay');

function darkenBackground() {
    backgroundOverlay.style.opacity = '0.3';
}
function lightenBackground() {
    backgroundOverlay.style.opacity = '0';
}