// Main JavaScript for Data Bundle Site
// Handles mobile menu, forms, notifications, and UI interactions

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenu = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenu) {
        mobileMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking a link
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Parallax effect on floating cards
    const cards = document.querySelectorAll('.floating-card');
    if (cards.length > 0) {
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX / window.innerWidth - 0.5;
            const mouseY = e.clientY / window.innerHeight - 0.5;
            
            cards.forEach((card, index) => {
                const depth = (index + 1) * 20;
                card.style.transform = `translate(${mouseX * depth}px, ${mouseY * depth}px) rotateY(${mouseX * 10}deg) rotateX(${mouseY * 10}deg)`;
            });
        });
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Phone number formatting and validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);
            e.target.value = value;
            
            // Validate Ghana phone number
            const isValid = /^0[0-9]{9}$/.test(value);
            if (value.length === 10) {
                if (isValid) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                } else {
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                }
            } else {
                e.target.classList.remove('is-valid', 'is-invalid');
            }
        });
    });

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength-bar');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function(e) {
            const password = e.target.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
    }

    // Toggle password visibility
    window.togglePassword = function(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    };

    // Show toast notification
    window.showToast = function(title, message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-text">${message}</div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    };

    // Copy to clipboard
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied!', 'Text copied to clipboard', 'success');
        }).catch(() => {
            alert('Failed to copy text');
        });
    };

    // Format currency
    window.formatCurrency = function(amount) {
        return 'GH₵ ' + parseFloat(amount).toFixed(2);
    };

    // Format date
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Time ago function
    window.timeAgo = function(dateString) {
        const date = new Date(dateString);
        const seconds = Math.floor((new Date() - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + ' year' + (interval === 1 ? '' : 's') + ' ago';
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + ' month' + (interval === 1 ? '' : 's') + ' ago';
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + ' day' + (interval === 1 ? '' : 's') + ' ago';
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + ' hour' + (interval === 1 ? '' : 's') + ' ago';
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + ' minute' + (interval === 1 ? '' : 's') + ' ago';
        
        return 'just now';
    };

    // Calculate fee
    window.calculateFee = function(amount, feePercent) {
        const fee = (amount * feePercent) / 100;
        const total = amount + fee;
        return { fee, total };
    };
});

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
