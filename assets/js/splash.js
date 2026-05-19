/**
 * NEXUS FEST Splash Screen logic
 * Handles the removal of the splash screen after the animation completes
 */
document.addEventListener('DOMContentLoaded', () => {
    const splash = document.getElementById('splash-screen');
    const body = document.body;

    // Prevent scrolling while splash is active
    body.style.overflow = 'hidden';

    // Wait for the animation (2.5s for loader + some buffer)
    setTimeout(() => {
        if (splash) {
            splash.classList.add('fade-out');
            
            // Allow scrolling again
            body.style.overflow = '';

            // Remove from DOM after transition
            setTimeout(() => {
                splash.remove();
            }, 800);
        }
    }, 3000); // 3 seconds total splash time
});
