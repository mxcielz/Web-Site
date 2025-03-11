// Lazy Loading para imagens
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });

    lazyImages.forEach(img => imageObserver.observe(img));
});

// Performance Optimizations
const performanceOptimizations = {
    init() {
        this.setupCaching();
        this.setupServiceWorker();
        this.deferNonCriticalStyles();
        this.setupAccessibility();
    },

    setupCaching() {
        // Cache API implementation
        if ('caches' in window) {
            caches.open('wb-static-v1').then(cache => {
                const urlsToCache = [
                    '/',
                    '/styles.css',
                    '/nav-footer.css',
                    '/assets/js/main.js'
                ];
                cache.addAll(urlsToCache);
            });
        }
    },

    setupServiceWorker() {
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful');
                })
                .catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
        }
    },

    deferNonCriticalStyles() {
        // Defer loading of non-critical CSS
        const loadDeferredStyles = () => {
            document.querySelectorAll('link[data-defer]').forEach(link => {
                link.rel = 'stylesheet';
            });
        };
        
        if (window.requestIdleCallback) {
            requestIdleCallback(loadDeferredStyles);
        } else {
            setTimeout(loadDeferredStyles, 0);
        }
    },

    setupAccessibility() {
        // Add ARIA labels where missing
        document.querySelectorAll('button:not([aria-label])').forEach(button => {
            if (!button.textContent.trim()) {
                button.setAttribute('aria-label', 'Botão');
            }
        });

        // Ensure all images have alt text
        document.querySelectorAll('img:not([alt])').forEach(img => {
            img.setAttribute('alt', 'Imagem');
        });

        // Add keyboard navigation support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-nav');
        });
    }
};

// Initialize optimizations
performanceOptimizations.init();

// Analytics and Performance Monitoring
const analytics = {
    trackPageLoad() {
        if ('performance' in window) {
            const pageLoadTime = performance.now();
            const navigationTiming = performance.getEntriesByType('navigation')[0];
            
            // Log performance metrics
            console.log('Page Load Time:', pageLoadTime);
            console.log('DOM Content Loaded:', navigationTiming.domContentLoadedEventEnd);
            console.log('First Contentful Paint:', performance.getEntriesByType('paint')[0].startTime);
        }
    },

    trackUserInteraction(event) {
        // Track user interactions for analytics
        const interaction = {
            type: event.type,
            target: event.target.tagName,
            timestamp: new Date().getTime()
        };
        
        // Send to analytics service (implement your own analytics service)
        console.log('User Interaction:', interaction);
    }
};

// Monitor performance
window.addEventListener('load', () => analytics.trackPageLoad());

// Error handling and reporting
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error: ', msg, 'URL: ', url, 'Line: ', lineNo, 'Column: ', columnNo, 'Error object: ', error);
    return false;
};

// Export modules for use in other files
export { performanceOptimizations, analytics }; 