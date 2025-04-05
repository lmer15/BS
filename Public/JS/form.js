document.addEventListener('DOMContentLoaded', function() {
    initFormHandlers();
    initPasswordToggle();
});

function initFormHandlers() {
    // Form submission handlers
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const guestForm = document.getElementById('guestForm');

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission('register');
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission('login');
        });
    }

    if (guestForm) {
        guestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission('guest');
        });
    }
}

function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = document.getElementById(this.getAttribute('data-input'));
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash', !isPassword);
            this.classList.toggle('fa-eye', isPassword);
        });
    });
}

async function handleFormSubmission(formType) {
    const form = document.getElementById(`${formType}Form`);
    if (!form) return;

    const messageElement = document.getElementById(`${formType}Message`);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Set loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    try {
        // Build form data
        const formData = new FormData(form);
        const requestData = {};
        formData.forEach((value, key) => {
            requestData[key] = value;
        });

        // Add additional fields for register form
        if (formType === 'register') {
            requestData.confirm_password = document.getElementById('registerConfirmPassword').value;
        }

        const response = await fetch(`../Controller/AuthController.php?action=${formType}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        showFeedback(messageElement, data.message, 'success');
        
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        }
    } catch (error) {
        showFeedback(messageElement, error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = getButtonText(formType);
    }
}

function showFeedback(element, message, type) {
    if (!element) return;
    
    element.textContent = message;
    element.className = `message ${type}`;
    element.style.display = 'block';
    
    if (element.timeoutId) clearTimeout(element.timeoutId);
    element.timeoutId = setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

function getButtonText(formType) {
    const buttonTexts = {
        login: 'Log In',
        register: 'Register',
        guest: 'Continue'
    };
    return buttonTexts[formType] || 'Submit';
}