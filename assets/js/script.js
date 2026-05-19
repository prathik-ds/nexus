// Nexus Fest BCA IT Fest — Interactive Engine

document.addEventListener('DOMContentLoaded', () => {
    console.log('%c⬡ Nexus Fest ENGINE ONLINE', 'color: #7c3aed; font-weight: bold; font-size: 14px;');

    // ── Mouse Glow Tracker ──
    const glow = document.createElement('div');
    glow.style.cssText = `
        position: fixed;
        width: 500px; height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(124, 58, 237, 0.025) 0%, rgba(0, 212, 255, 0.015) 30%, transparent 70%);
        pointer-events: none;
        z-index: -1;
        transform: translate(-50%, -50%);
        transition: top 0.15s ease-out, left 0.15s ease-out;
    `;
    document.body.appendChild(glow);

    document.addEventListener('mousemove', (e) => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });

    // ── Smooth Reveal Animation for glass & card elements ──
    const revealElements = document.querySelectorAll('.glass, .glass-panel, .glass-panel-dash, .event-card-dash');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                entry.target.style.transition = 'opacity 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94), transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    revealElements.forEach((el, i) => {
        // Only apply initial hidden state if not already animated via CSS
        if (!el.style.animationName && !el.classList.contains('event-card-dash')) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(24px)';
            el.style.transitionDelay = `${i * 0.05}s`;
        }
        revealObserver.observe(el);
    });

    // ── Input Focus Micro-interactions ──
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            if (input.parentElement) {
                input.parentElement.style.transition = '0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                input.parentElement.style.transform = 'translateX(3px)';
            }
        });
        input.addEventListener('blur', () => {
            if (input.parentElement) {
                input.parentElement.style.transform = 'translateX(0)';
            }
        });
    });

    // ── Navbar Scroll Enhancement ──
    const nav = document.querySelector('nav');
    if (nav) {
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 100) {
                nav.style.background = 'rgba(4, 6, 14, 0.92)';
                nav.style.borderBottomColor = 'rgba(100, 130, 200, 0.12)';
            } else {
                nav.style.background = 'rgba(4, 6, 14, 0.75)';
                nav.style.borderBottomColor = 'rgba(100, 130, 200, 0.08)';
            }
            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ── Counter Animation for Stats ──
    const statNumbers = document.querySelectorAll('[data-count]');
    statNumbers.forEach(el => {
        const target = parseInt(el.getAttribute('data-count'));
        let count = 0;
        const increment = target / 60;
        const timer = setInterval(() => {
            count += increment;
            if (count >= target) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(count);
            }
        }, 16);
    });

    // ── Close mobile nav on link click ──
    const navLinks = document.querySelectorAll('.nav-links a');
    const navMenu = document.querySelector('.nav-links');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navMenu && navMenu.classList.contains('open')) {
                navMenu.classList.remove('open');
            }
        });
    });

    // ── Ripple Effect on Buttons ──
    const buttons = document.querySelectorAll('.btn-neon, .btn-coord, .btn-start-dash');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            ripple.style.cssText = `
                position: absolute;
                width: 10px; height: 10px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
                left: ${e.clientX - rect.left}px;
                top: ${e.clientY - rect.top}px;
            `;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 700);
        });
    });

    // Add ripple keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes rippleEffect {
            to { transform: scale(30); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});
