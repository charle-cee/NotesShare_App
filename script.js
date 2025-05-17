const wrapper = document.querySelector('.wrapper');
const registerLink = document.querySelector('.register-link');
const loginLink = document.querySelector('.login-link');

// When the register link is clicked
registerLink.addEventListener('click', () => {
    wrapper.classList.add('active');
    animateElements();
});

// When the login link is clicked
loginLink.addEventListener('click', () => {
    wrapper.classList.remove('active');
    animateElements();
});

// Function to add 'active' class to animation elements for triggering CSS transitions
function animateElements() {
    const animations = document.querySelectorAll('.animation');
    animations.forEach(element => {
        if (wrapper.classList.contains('active')) {
            element.classList.add('active');
        } else {
            element.classList.remove('active');
        }
    });
}

