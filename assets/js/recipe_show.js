import '../styles/site/recipe_show.scss';
import * as bootstrap from 'bootstrap';
let loginModal = new bootstrap.Modal(document.getElementById('loginModal'));

fetch('/api/check-login')
    .then(response => response.json())
    .then(data => {
        let isUserLoggedIn = data.isUserLoggedIn;
        
        document.querySelectorAll('.rating__input[type="radio"]').forEach(function(input) {
            input.addEventListener('click', function(event) {
                if (!isUserLoggedIn) {
                    event.preventDefault();
                    loginModal.show();
                }
            });
        });
    });