/**
 * NEXUS FEST Splash Screen logic
 * Handles the removal of the splash screen and complex loader animations
 */
document.addEventListener('DOMContentLoaded', () => {
    const splash = document.getElementById('splash-screen');
    const body = document.body;
    
    if (!splash) return;

    // Prevent scrolling while splash is active
    body.style.overflow = 'hidden';

    // Particle Generation
    const particleContainer = document.getElementById('splash-particles');
    if (particleContainer) {
        for (let i = 0; i < 30; i++) {
            createParticle(particleContainer);
        }
    }

    // Loader Logic
    const progress = document.querySelector('.loader-progress');
    const glow = document.querySelector('.loader-glow');
    const pctText = document.getElementById('loading-pct');
    const subtitleText = document.querySelector('.typewriter');
    
    let currentPct = 0;
    const duration = 3500; // 3.5 seconds
    const interval = 30;
    const steps = duration / interval;
    const increment = 100 / steps;

    const phrases = [
        "SYSTEM INITIALIZING...",
        "DECRYPTING ASSETS...",
        "ESTABLISHING SECURE LINK...",
        "SYNCHRONIZING PROTOCOLS...",
        "WELCOME TO NEXUS FEST"
    ];

    const loaderInterval = setInterval(() => {
        currentPct += increment;
        
        // Add some random variation to the loading speed to make it feel real
        const randomJump = Math.random() > 0.8 ? Math.random() * 5 : 0;
        currentPct += randomJump;

        if (currentPct >= 100) {
            currentPct = 100;
            clearInterval(loaderInterval);
            
            // Final phase before removing
            setTimeout(() => {
                splash.classList.add('fade-out');
                body.style.overflow = '';

                setTimeout(() => {
                    splash.remove();
                }, 1000); // Wait for transition
            }, 500); // Small pause at 100%
        }

        // Update UI
        const displayPct = Math.min(100, Math.floor(currentPct));
        if (progress) progress.style.width = displayPct + '%';
        if (glow) glow.style.width = displayPct + '%';
        if (pctText) pctText.innerText = displayPct;

        // Change text based on percentage
        if (subtitleText) {
            if (displayPct < 20) subtitleText.innerText = phrases[0];
            else if (displayPct < 50) subtitleText.innerText = phrases[1];
            else if (displayPct < 75) subtitleText.innerText = phrases[2];
            else if (displayPct < 95) subtitleText.innerText = phrases[3];
            else subtitleText.innerText = phrases[4];
        }

    }, interval);

});

function createParticle(container) {
    const particle = document.createElement('div');
    particle.classList.add('splash-particle');
    
    // Randomize properties
    const size = Math.random() * 4 + 1;
    const left = Math.random() * 100;
    const top = Math.random() * 100;
    const duration = Math.random() * 3 + 2;
    const delay = Math.random() * 2;
    const colors = ['#00d4ff', '#10b981', '#7c3aed', '#f43f5e'];
    const color = colors[Math.floor(Math.random() * colors.length)];
    
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    particle.style.left = `${left}%`;
    particle.style.top = `${top}%`;
    particle.style.background = color;
    particle.style.boxShadow = `0 0 ${size * 2}px ${color}`;
    particle.style.animationDuration = `${duration}s`;
    particle.style.animationDelay = `${delay}s`;
    
    container.appendChild(particle);
}
