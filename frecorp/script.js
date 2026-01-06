// FRECORP ERP - Dark Mode Only JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Set current year in footer
    const yearElement = document.getElementById('current-year');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }
    
    // Force dark theme
    const body = document.body;
    body.setAttribute('data-theme', 'dark');
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuToggle.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            } else {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        });
        
        // Close mobile menu when clicking on a link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    }
    
    // Smooth scroll behavior for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Navbar glass effect on scroll
    const navbar = document.querySelector('.glass-nav');
    let lastScrollY = window.scrollY;
    
    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > 100) {
            navbar.style.background = 'rgba(15, 23, 42, 0.95)';
            navbar.style.backdropFilter = 'blur(25px)';
        } else {
            navbar.style.background = 'rgba(15, 23, 42, 0.7)';
            navbar.style.backdropFilter = 'blur(20px)';
        }
        
        // Hide/show navbar on scroll (only on desktop)
        if (window.innerWidth > 768) {
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }
        }
        
        lastScrollY = currentScrollY;
    });
    
    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe all glass cards
    document.querySelectorAll('.glass-card').forEach(card => {
        observer.observe(card);
    });
    
    // Parallax effect for floating orbs
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.3;
        
        document.querySelectorAll('.floating-orb').forEach((orb, index) => {
            const speed = (index + 1) * 0.2;
            orb.style.transform = `translateY(${rate * speed}px)`;
        });
    });
    
    // Mouse tracking for glass effects
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        document.querySelectorAll('.glass-card').forEach(card => {
            const rect = card.getBoundingClientRect();
            const cardX = rect.left + rect.width / 2;
            const cardY = rect.top + rect.height / 2;
            
            const distanceX = (e.clientX - cardX) / 10;
            const distanceY = (e.clientY - cardY) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${distanceY * 0.1}deg) rotateY(${distanceX * 0.1}deg)`;
        });
        
        // Update floating orbs position based on mouse
        document.querySelectorAll('.floating-orb').forEach((orb, index) => {
            const speed = (index + 1) * 20;
            const x = (mouseX - 0.5) * speed;
            const y = (mouseY - 0.5) * speed;
            
            orb.style.transform += ` translate(${x}px, ${y}px)`;
        });
    });
    
    // Reset card transform when mouse leaves
    document.addEventListener('mouseleave', () => {
        document.querySelectorAll('.glass-card').forEach(card => {
            card.style.transform = '';
        });
    });
    
    // Form submission with animation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Loading animation
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi en cours...';
            submitBtn.disabled = true;
            
            // Simulate form submission
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Message envoyé !';
                submitBtn.classList.remove('glass-button-primary');
                submitBtn.classList.add('bg-green-500');
                
                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.add('glass-button-primary');
                    submitBtn.classList.remove('bg-green-500');
                    form.reset();
                }, 2000);
            }, 2000);
        });
    }
    
    // Counter animation for statistics
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (element.textContent.includes('%')) {
                element.textContent = Math.floor(current) + '%';
            } else if (element.textContent.includes('+')) {
                element.textContent = Math.floor(current) + '+';
            } else if (element.textContent.includes('★')) {
                element.textContent = current.toFixed(1) + '★';
            } else {
                element.textContent = current.toFixed(1);
            }
        }, 50);
    }
    
    // Trigger counter animation when statistics section is visible
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counters = entry.target.querySelectorAll('[class*="text-4xl"]');
                counters.forEach(counter => {
                    const text = counter.textContent;
                    if (text.includes('500+')) {
                        animateCounter(counter, 500);
                    } else if (text.includes('99.9%')) {
                        animateCounter(counter, 99.9);
                    } else if (text.includes('24/7')) {
                        counter.textContent = '24/7';
                    } else if (text.includes('5★')) {
                        animateCounter(counter, 5);
                    }
                });
                statsObserver.unobserve(entry.target);
            }
        });
    });
    
    // Observe the statistics card container (grid inside the stats section)
    const statsContainer = document.querySelector('section .grid.grid-cols-2.md\\:grid-cols-4');
    if (statsContainer) {
        statsObserver.observe(statsContainer.parentElement);
    }
    
    // Mobile menu toggle functionality is already handled above
    
    // Advanced glass morphism effects
    function createGlassMorphism() {
        const elements = document.querySelectorAll('.glass-card, .glass-button, .glass-nav');
        
        elements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.style.background = element.style.background.replace('0.4', '0.6');
                element.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.background = element.style.background.replace('0.6', '0.4');
                element.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            });
        });
    }
    
    createGlassMorphism();
    
    // Dynamic background color based on scroll position
    let ticking = false;
    
    function updateBackgroundColor() {
        const scrollPercent = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight);
        
        const hue = 240 + (scrollPercent * 60); // From blue to purple
        body.style.background = `linear-gradient(135deg, 
            hsl(${hue}, 70%, 15%) 0%, 
            hsl(${hue + 30}, 80%, 20%) 50%, 
            hsl(${hue}, 70%, 15%) 100%)`;
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateBackgroundColor);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick);
    
    // Initialize background
    updateBackgroundColor();
});

// CSS Animation classes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes animate-in {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-in {
        animation: animate-in 0.8s ease-out forwards;
    }
    
    .glass-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .glass-button, .glass-button-primary {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
`;

document.head.appendChild(style);
