// Animation utility functions
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards with staggered delay
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card, index) {
        setTimeout(function() {
            card.classList.add('fade-in');
        }, index * 100);
    });
    
    // Add slide-in animation to headings and buttons
    const slideElements = document.querySelectorAll('.navbar, h1, h2, h3, h4, h5, .btn-primary');
    slideElements.forEach(function(element) {
        element.classList.add('slide-in');
    });
    
    // Make favorite buttons interactive
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('animate__animated', 'animate__heartBeat');
            setTimeout(() => {
                this.classList.remove('animate__animated', 'animate__heartBeat');
            }, 1000);
        });
    });
    
    // Add hover effects to navigation
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Implement smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 70,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Optional: Dark mode toggle functionality
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            // Save preference to localStorage
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
        });
        
        // Check for saved preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    }
});

// Intersection Observer for scroll animations
const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1
};

const observer = new IntersectionObserver(function(entries, observer) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-on-scroll');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe elements with .animate-on-scroll-trigger class
document.querySelectorAll('.animate-on-scroll-trigger').forEach(el => {
    observer.observe(el);
}); 