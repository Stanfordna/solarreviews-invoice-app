/**
 * Function to set css color variables from light to dark
 */
const root = document.documentElement;
const styles = getComputedStyle(root);

const darkButton = document.querySelector('dark-theme-toggle');
const lightButton = document.querySelector('light-theme-toggle');

const colorMap = [
    // target, lightSource, darkSource
    ['--color-app-background', '--light-app-background', '--dark-app-background'],
    ['--color-header-background', '--light-header-background', '--dark-header-background'],
    ['--color-component-background', '--light-component-background', '--dark-component-background'],
    ['--color-invoice-total-background', '--light-invoice-total-background', '--dark-invoice-total-background'],
    ['--color-element-border-regular', '--light-element-border-regular', '--dark-element-border-regular'],
    ['--color-element-border-accent', '--light-element-border-accent', '--dark-element-border-accent'],
    ['--color-save-as-draft', '--light-save-as-draft', '--dark-save-as-draft'],
    ['--color-save-as-draft-hover', '--light-save-as-draft-hover', '--dark-save-as-draft-hover'],
    ['--color-draft-status-text', '--light-draft-status-text', '--dark-draft-status-text'],
    ['--color-draft-status-background', '--light-draft-status-background', '--dark-draft-status-background'],
    ['--color-pending-status-text', '--light-pending-status-text', '--dark-pending-status-text'],
    ['--color-pending-status-background', '--light-pending-status-background', '--dark-pending-status-background'],
    ['--color-paid-status-text', '--light-paid-status-text', '--dark-paid-status-text'],
    ['--color-paid-status-background', '--light-paid-status-background', '--dark-paid-status-background'],
    ['--color-text', '--light-theme-text', '--dark-theme-text'],
    ['--color-text-alt', '--light-theme-text-alt', '--dark-theme-text-alt'],
    ['--color-accent', '--light-color-accent', '--dark-color-accent']
];

function setDarkTheme() {
    colorMap.forEach(([target, _, dark]) => {
        root.style.setProperty(target, styles.getPropertyValue(dark));
    });
    darkButton.style.display = 'none';
    lightButton.style.display = 'flex';
}

function setLightTheme() {
    colorMap.forEach(([target, light, _]) => {
        root.style.setProperty(target, styles.getPropertyValue(light));
    });
    lightButton.style.display = 'none';
    darkButton.style.display = 'flex';
}
