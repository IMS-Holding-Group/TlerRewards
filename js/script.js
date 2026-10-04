document.addEventListener('DOMContentLoaded', () => {
    // Navigation functionality
    initNavigation();
    
    // Form handling
    initFormHandling();
    
    // Animations
    initAnimations();
    
    // Counter animations
    initCounterAnimations();
    
    // Smooth scrolling for navigation links
    initSmoothScrolling();
});

// Navigation functionality
function initNavigation() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    const navbar = document.querySelector('.navbar');
    
    // Mobile menu toggle
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        // Close menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
    }
    
    // Navbar scroll effect
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

// Form handling
function initFormHandling() {
    const registrationForm = document.getElementById('registration-form');
    const formMessage = document.getElementById('form-message');
    const submitBtn = registrationForm?.querySelector('.submit-btn');

    if (!registrationForm || !formMessage) return;

    registrationForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Show loading state
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }

        // Client-side validation
        const formData = getFormData();
        const validationResult = validateForm(formData);
        
        if (!validationResult.isValid) {
            showMessage(validationResult.message, 'error');
            resetSubmitButton();
            return;
        }

        try {
        const response = await fetch('apis/register_applicant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(formData).toString()
        });

        const result = await response.json();
        if (!result.success) throw new Error(result.message);

        showMessage(result.message || 'تم إرسال طلبك بنجاح، سيتم التواصل معك قريبا.', 'success');
        registrationForm.reset();

        // Add success animation
        registrationForm.style.transform = 'scale(0.98)';
        setTimeout(() => {
            registrationForm.style.transform = 'scale(1)';
        }, 200);

        } catch (error) {
            console.error('Error:', error);
            showMessage(error.message || 'حدث خطأ أثناء إرسال طلبك. الرجاء المحاولة مرة أخرى.', 'error');
        }
        finally { 
            registrationForm.reset();
            resetSubmitButton(); }
    });

    function getFormData() {
        return {
            name: document.getElementById('name')?.value.trim() || '',
            age: document.getElementById('age')?.value.trim() || '',
            nationality: document.getElementById('nationality')?.value.trim() || '',
            instagram: document.getElementById('instagram')?.value.trim() || '',
            email: document.getElementById('email')?.value.trim() || '',
            phone: document.getElementById('phone')?.value.trim() || ''
        };
    }

    function validateForm(data) {
        if (!data.name || !data.age || !data.nationality || !data.email || !data.phone) {
            return {
                isValid: false,
                message: 'الرجاء تعبئة جميع الحقول المطلوبة.'
            };
        }

        if (isNaN(data.age) || parseInt(data.age) < 13) {
            return {
                isValid: false,
                message: 'الرجاء إدخال عمر صحيح (أكبر من 13).'
            };
        }

        const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailRegex.test(data.email)) {
            return {
                isValid: false,
                message: 'الرجاء إدخال بريد الكتروني صحيح.'
            };
        }

        const phoneRegex = /^[0-9]{10,}$/;
        if (!phoneRegex.test(data.phone)) {
            return {
                isValid: false,
                message: 'الرجاء إدخال رقم هاتف صحيح (10 أرقام على الأقل).'
            };
        }

        return { isValid: true };
    }

    function showMessage(message, type) {
        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.style.display = 'block';
        
        // Add entrance animation
        formMessage.style.opacity = '0';
        formMessage.style.transform = 'translateY(-10px)';
        
        requestAnimationFrame(() => {
            formMessage.style.transition = 'all 0.3s ease';
            formMessage.style.opacity = '1';
            formMessage.style.transform = 'translateY(0)';
        });
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            formMessage.style.opacity = '0';
            formMessage.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                formMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }

    function resetSubmitButton() {
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    }

    // Add input focus effects
    const inputs = registrationForm.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', () => {
            if (!input.value) {
                input.parentElement.classList.remove('focused');
            }
        });
        
        // Add real-time validation feedback
        input.addEventListener('input', () => {
            clearTimeout(input.validationTimeout);
            input.validationTimeout = setTimeout(() => {
                validateInputRealTime(input);
            }, 500);
        });
    });

    function validateInputRealTime(input) {
        const value = input.value.trim();
        let isValid = true;
        let message = '';

        switch (input.type) {
            case 'email':
                const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                isValid = !value || emailRegex.test(value);
                message = isValid ? '' : 'بريد الكتروني غير صحيح';
                break;
            case 'tel':
                const phoneRegex = /^[0-9]{10,}$/;
                isValid = !value || phoneRegex.test(value);
                message = isValid ? '' : 'رقم هاتف غير صحيح';
                break;
            case 'number':
                isValid = !value || (parseInt(value) >= 13);
                message = isValid ? '' : 'العمر لازم مايقل عن 13';
                break;
        }

        // Update input styling based on validation
        if (value) {
            if (isValid) {
                input.style.borderColor = 'var(--success-color)';
                input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
            } else {
                input.style.borderColor = 'var(--error-color)';
                input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            }
        } else {
            input.style.borderColor = 'var(--border-color)';
            input.style.boxShadow = 'none';
        }
    }
}

// Animations
function initAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.stat-card, .feature-item, .benefit-item');
    animatedElements.forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });

    // Parallax effect for hero background
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const heroParticles = document.querySelector('.hero-particles');
        
        if (heroParticles) {
            heroParticles.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });

    // Floating cards animation enhancement
    const floatingCards = document.querySelectorAll('.floating-card');
    floatingCards.forEach((card, index) => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-10px) scale(1.05)';
            card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.3)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
            card.style.boxShadow = 'none';
        });
    });
}

// Counter animations
function initCounterAnimations() {
    const counters = document.querySelectorAll('.stat-number');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });

    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            // Format number with commas for thousands
            const formattedNumber = Math.floor(current).toLocaleString('en-US');
            element.textContent = formattedNumber;
        }, 16);
    }
}

// Smooth scrolling for navigation links
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.offsetTop - navbarHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Additional interactive features
document.addEventListener('DOMContentLoaded', () => {
    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn, .submit-btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', () => {
            if (!button.classList.contains('loading')) {
                button.style.transform = 'translateY(0)';
            }
        });
    });

    // Add ripple effect to buttons
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Add CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .btn, .submit-btn {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Add loading animation for page load
    window.addEventListener('load', () => {
        document.body.classList.add('loaded');
    });

    // Add scroll progress indicator
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: var(--accent-color);
        z-index: 9999;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset;
        const docHeight = document.body.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        progressBar.style.width = scrollPercent + '%';
    });
});