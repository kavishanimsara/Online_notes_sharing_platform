// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger icon
            const spans = navToggle.querySelectorAll('span');
            if (navMenu.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }

    // File upload validation and preview
    const fileInput = document.getElementById('note_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                ];

                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type! Please upload PDF, DOC, DOCX, TXT, PPT, or PPTX files.');
                    fileInput.value = '';
                    return;
                }

                if (fileSize > 10) {
                    alert('File size must be less than 10MB!');
                    fileInput.value = '';
                    return;
                }

                // Show file info
                console.log('File selected:', file.name, 'Size:', fileSize.toFixed(2) + 'MB');
            }
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#E74C3C';
                } else {
                    field.style.borderColor = '#BDC3C7';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields!');
            }
        });
    });

    // Password match validation for registration
    const confirmPassword = document.getElementById('confirm_password');
    const password = document.getElementById('password');
    
    if (confirmPassword && password) {
        confirmPassword.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match!');
                confirmPassword.style.borderColor = '#E74C3C';
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.style.borderColor = '#50C878';
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });

    // Search form auto-focus
    const searchInput = document.querySelector('.search-input');
    if (searchInput && window.innerWidth > 768) {
        // Only auto-focus on desktop
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('search')) {
            setTimeout(() => {
                searchInput.focus();
            }, 500);
        }
    }

    // Download button confirmation
    const downloadButtons = document.querySelectorAll('a[href*="download.php"]');
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Optional: Add download animation or notification
            const noteCard = this.closest('.note-card');
            if (noteCard) {
                noteCard.style.animation = 'pulse 0.5s';
                setTimeout(() => {
                    noteCard.style.animation = '';
                }, 500);
            }
        });
    });
});

// Add CSS animation for pulse effect
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
`;
document.head.appendChild(style);