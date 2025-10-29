document.getElementById('print-form').addEventListener('submit', function(e) {
    const submitButton = document.getElementById('print-submit-button');
    const loader = document.getElementById('print-loader');

    submitButton.disabled = true;
    loader.classList.remove('d-none');

    setTimeout(() => {
        submitButton.disabled = false;
        loader.classList.add('d-none');
    }, 2500);
});