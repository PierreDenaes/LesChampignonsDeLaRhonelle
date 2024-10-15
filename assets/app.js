/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
require('bootstrap');
require('@fortawesome/fontawesome-free/css/all.min.css');
// any CSS you import will output into a single css file (app.css in this case)
import * as bootstrap from 'bootstrap';
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
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les éléments avec la classe "page-link"
    let pageLinks = document.querySelectorAll('.page-link');

    // Parcourir les liens et modifier leur texte si nécessaire
    pageLinks.forEach(function(link) {
        if (link.textContent.includes('Previous')) {
            link.textContent = 'Précédent';
        } else if (link.textContent.includes('Next')) {
            link.textContent = 'Suivant';
        }
    });
    let notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    let notificationContainer = document.getElementById('notificationModal').querySelector('.modal-body');
    
    // Récupérer les messages de notification présents dans la page
    let notificationMessages = document.querySelectorAll('.alert');

    // Si des notifications sont présentes, on les affiche dans la modale
    if (notificationMessages.length > 0) {
        notificationContainer.innerHTML = ''; // Nettoyer la modale

        notificationMessages.forEach(function(message) {
            notificationContainer.append(message.cloneNode(true)); // Ajouter chaque message à la modale
        });

        notificationModal.show(); // Afficher la modale
    }

    // S'assurer que la modale se ferme complètement
    document.getElementById('notificationModal').addEventListener('hidden.bs.modal', function () {
        document.body.classList.remove('modal-open');
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove(); // Supprimer le fond de la modale
        }
    });
    document.querySelectorAll('.mushrooms-average').forEach(function(container) {
        let average = parseFloat(container.getAttribute('data-average'));
        let mushrooms = container.querySelectorAll('.mushroom');
        
        mushrooms.forEach((mushroom, index) => {
            let score = index + 1;
            if (average >= score) {
                mushroom.classList.add('mushroom-full');
            } else if (average >= score - 0.5) {
                mushroom.classList.add('mushroom-half');
            } else {
                mushroom.classList.add('mushroom-empty');
            }
        });
    });
});
