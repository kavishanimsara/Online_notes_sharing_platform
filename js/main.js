// Enhanced Mobile Navigation Toggle with Smooth Animations
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            
            // Enhanced hamburger icon animation
            const spans = navToggle.querySelectorAll('span');
            if (navMenu.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(6px, 6px)';
                spans[0].style.backgroundColor = '#50C878';
                spans[1].style.opacity = '0';
                spans[1].style.transform = 'translateX(-10px)';
                spans[2].style.transform = 'rotate(-45deg) translate(6px, -6px)';
                spans[2].style.backgroundColor = '#50C878';
                navToggle.style.transform = 'scale(0.95)';
            } else {
                spans[0].style.transform = 'none';
                spans[0].style.backgroundColor = 'white';
                spans[1].style.opacity = '1';
                spans[1].style.transform = 'none';
                spans[2].style.transform = 'none';
                spans[2].style.backgroundColor = 'white';
                navToggle.style.transform = 'none';
            }
        });

        // Enhanced close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[0].style.backgroundColor = 'white';
                spans[1].style.opacity = '1';
                spans[1].style.transform = 'none';
                spans[2].style.transform = 'none';
                spans[2].style.backgroundColor = 'white';
                navToggle.style.transform = 'none';
            }
        });

        // Smooth close when clicking menu links
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[0].style.backgroundColor = 'white';
                spans[1].style.opacity = '1';
                spans[1].style.transform = 'none';
                spans[2].style.transform = 'none';
                spans[2].style.backgroundColor = 'white';
                navToggle.style.transform = 'none';
            });
        });
    }

    // Enhanced File upload validation with visual feedback
    const fileInput = document.getElementById('note_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileError = document.getElementById('fileError');
            const submitBtn = document.getElementById('submitBtn');
            
            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/zip'
                ];

                const fileExtension = file.name.split('.').pop().toLowerCase();
                const allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'zip'];

                if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                    showNotification('Invalid file type! Please upload PDF, DOC, DOCX, TXT, PPT, PPTX, or ZIP files.', 'error');
                    fileInput.value = '';
                    fileInput.classList.add('shake-animation');
                    setTimeout(() => fileInput.classList.remove('shake-animation'), 500);
                    return;
                }

                if (fileSize > 10) {
                    showNotification('File size must be less than 10MB!', 'error');
                    fileInput.value = '';
                    fileInput.classList.add('shake-animation');
                    setTimeout(() => fileInput.classList.remove('shake-animation'), 500);
                    return;
                }

                // Show success feedback
                fileInput.classList.add('success-border');
                showNotification(`File "${file.name}" selected successfully! (${fileSize.toFixed(2)}MB)`, 'success');
                
                // Enable submit button if it was disabled
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    }

    // Enhanced Form validation with smooth animations
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error-animation');
                    field.style.borderColor = '#E74C3C';
                    
                    // Add pulsing effect
                    field.style.animation = 'pulse-error 0.5s ease-in-out';
                    setTimeout(() => {
                        field.style.animation = '';
                    }, 500);
                } else {
                    field.classList.remove('error-animation');
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('Please fill in all required fields marked with *', 'error');
                
                // Scroll to first error
                const firstError = form.querySelector('.error-animation');
                if (firstError) {
                    firstError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
            } else {
                // Add loading state to submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Processing...';
                }
            }
        });

        // Real-time validation on input
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('input', function() {
                if (this.hasAttribute('required') && this.value.trim()) {
                    this.classList.remove('error-animation');
                    this.style.borderColor = '#50C878';
                    this.classList.add('success-animation');
                    setTimeout(() => this.classList.remove('success-animation'), 1000);
                }
            });
            
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('error-animation');
                    this.style.borderColor = '#E74C3C';
                }
            });
        });
    });

    // Enhanced Password match validation for registration
    const confirmPassword = document.getElementById('confirm_password');
    const password = document.getElementById('password');
    
    if (confirmPassword && password) {
        const validatePasswords = () => {
            if (password.value && confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match!');
                    confirmPassword.style.borderColor = '#E74C3C';
                    confirmPassword.classList.add('shake-animation');
                    setTimeout(() => confirmPassword.classList.remove('shake-animation'), 500);
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.style.borderColor = '#50C878';
                    confirmPassword.classList.add('success-animation');
                    setTimeout(() => confirmPassword.classList.remove('success-animation'), 1000);
                }
            }
        };

        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }

    // Enhanced Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Enhanced Auto-hide alerts with smooth animations
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Add close button to alerts
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '<i class="bi bi-x"></i>';
        closeBtn.className = 'alert-close';
        closeBtn.addEventListener('click', () => {
            alert.style.transform = 'translateX(100%)';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
        alert.appendChild(closeBtn);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.transform = 'translateX(100%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    });

    // Enhanced Search form auto-focus with animation
    const searchInput = document.querySelector('.search-input');
    if (searchInput && window.innerWidth > 768) {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('search')) {
            setTimeout(() => {
                searchInput.focus();
                searchInput.classList.add('focus-glow');
                setTimeout(() => searchInput.classList.remove('focus-glow'), 2000);
            }, 1000);
        }
    }

    // Enhanced Download button with progress animation
    const downloadButtons = document.querySelectorAll('a[href*="download.php"]');
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const noteCard = this.closest('.note-card');
            if (noteCard) {
                // Add download animation
                noteCard.style.animation = 'download-pulse 0.6s ease-in-out';
                
                // Create download progress indicator
                const progress = document.createElement('div');
                progress.className = 'download-progress';
                progress.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 0%;
                    height: 3px;
                    background: linear-gradient(90deg, #4A90E2, #50C878);
                    border-radius: 3px;
                    transition: width 0.3s ease;
                    z-index: 10;
                `;
                noteCard.style.position = 'relative';
                noteCard.appendChild(progress);

                // Animate progress
                setTimeout(() => progress.style.width = '100%', 100);
                
                setTimeout(() => {
                    noteCard.style.animation = '';
                    progress.remove();
                }, 1000);
            }
        });
    });

    // Add floating action button for mobile
    if (window.innerWidth <= 768) {
        const fab = document.createElement('div');
        fab.className = 'mobile-fab';
        fab.innerHTML = '<i class="bi bi-arrow-up"></i>';
        fab.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s ease;
            opacity: 0;
            transform: scale(0);
        `;
        document.body.appendChild(fab);

        // Show FAB on scroll
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                fab.style.opacity = '1';
                fab.style.transform = 'scale(1)';
            } else {
                fab.style.opacity = '0';
                fab.style.transform = 'scale(0)';
            }
        });

        // Scroll to top functionality
        fab.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // FAB hover effects
        fab.addEventListener('mouseenter', () => {
            fab.style.transform = 'scale(1.1)';
        });
        fab.addEventListener('mouseleave', () => {
            fab.style.transform = 'scale(1)';
        });
    }

    // Add CSS animations
    addCustomStyles();
});

// Enhanced Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.innerHTML = `
        <i class="bi ${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="bi bi-x"></i></button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.style.transform = 'translateX(400px)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(400px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
        warning: 'bi-exclamation-circle-fill'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        success: 'linear-gradient(135deg, #50C878, #40B868)',
        error: 'linear-gradient(135deg, #E74C3C, #C0392B)',
        info: 'linear-gradient(135deg, #4A90E2, #3A7BC8)',
        warning: 'linear-gradient(135deg, #F39C12, #E67E22)'
    };
    return colors[type] || colors.info;
}

// Add custom CSS styles
function addCustomStyles() {
    const styles = `
        <style>
            /* Enhanced Animations */
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.02); }
            }
            
            @keyframes download-pulse {
                0%, 100% { transform: scale(1); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                50% { transform: scale(1.03); box-shadow: 0 5px 20px rgba(74, 144, 226, 0.3); }
            }
            
            @keyframes pulse-error {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            @keyframes shake-animation {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            @keyframes success-glow {
                0%, 100% { box-shadow: 0 0 5px rgba(80, 200, 120, 0); }
                50% { box-shadow: 0 0 15px rgba(80, 200, 120, 0.5); }
            }
            
            /* Animation Classes */
            .shake-animation {
                animation: shake-animation 0.5s ease-in-out;
            }
            
            .success-animation {
                animation: success-glow 1s ease-in-out;
            }
            
            .error-animation {
                border-color: #E74C3C !important;
            }
            
            .success-border {
                border-color: #50C878 !important;
            }
            
            .focus-glow {
                animation: success-glow 2s ease-in-out;
            }
            
            /* Loading spinner */
            .spinner {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Alert close button */
            .alert-close {
                background: none;
                border: none;
                color: inherit;
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.3s;
                margin-left: auto;
            }
            
            .alert-close:hover {
                opacity: 1;
            }
            
            /* Mobile FAB hover effects */
            .mobile-fab:hover {
                transform: scale(1.1) !important;
                box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            }
            
            /* Enhanced navigation transitions */
            #navMenu {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            #navToggle span {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            /* Button loading state */
            button.loading {
                pointer-events: none;
                opacity: 0.8;
            }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', styles);
}

// Enhanced window resize handler
window.addEventListener('resize', function() {
    const navMenu = document.getElementById('navMenu');
    const navToggle = document.getElementById('navToggle');
    
    if (window.innerWidth > 768 && navMenu && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
        if (navToggle) {
            const spans = navToggle.querySelectorAll('span');
            spans[0].style.transform = 'none';
            spans[0].style.backgroundColor = 'white';
            spans[1].style.opacity = '1';
            spans[1].style.transform = 'none';
            spans[2].style.transform = 'none';
            spans[2].style.backgroundColor = 'white';
            navToggle.style.transform = 'none';
        }
    }
});

// Enhanced page load animations
window.addEventListener('load', function() {
    document.body.classList.add('page-loaded');
    
    // Animate elements with delay
    const animatedElements = document.querySelectorAll('.card, .btn, .form-control');
    animatedElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
    });
});