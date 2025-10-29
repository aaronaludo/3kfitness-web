document.querySelectorAll('.reveal-button').forEach(button => {
    button.addEventListener('click', function() {
        const passwordField = this.previousElementSibling;
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            this.textContent = 'Hide';
        } else {
            passwordField.type = 'password';
            this.textContent = 'Show';
        }
    });
});