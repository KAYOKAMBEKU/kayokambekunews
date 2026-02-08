document.documentElement.classList.add('has-js');

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('process_register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                this.reset();
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de l\'inscription.');
        });
    });
}

const headerEl = document.querySelector('header');
const toggleBtn = document.querySelector('.menu-toggle');
if (headerEl && toggleBtn) {
    const closeMenu = () => {
        document.body.classList.remove('nav-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
    };
    const openMenu = () => {
        document.body.classList.add('nav-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    };
    toggleBtn.addEventListener('click', () => {
        if (document.body.classList.contains('nav-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });
    document.addEventListener('click', (e) => {
        if (!headerEl.contains(e.target)) closeMenu();
    });
}
