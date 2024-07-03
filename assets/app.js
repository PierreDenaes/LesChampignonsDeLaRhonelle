/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
require('bootstrap');
require('@fortawesome/fontawesome-free/css/all.min.css');
// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
const navbarBrand = document.querySelector('.navbar-brand');
window.addEventListener('scroll', () => {
    if (window.scrollY > 0) {
        navbarBrand.classList.add('hidden');
        navbarBrand.classList.remove('visible');
    } else {
        navbarBrand.classList.add('visible');
        navbarBrand.classList.remove('hidden');
    }
});
