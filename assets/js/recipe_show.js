import '../styles/site/recipe_show.scss';
import * as bootstrap from 'bootstrap';

let loginModal = new bootstrap.Modal(document.getElementById('loginModal'));

// Fonction pour afficher la notification
function displayNotification(message, type) {
    let notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    let notificationContainer = document.getElementById('notificationModal').querySelector('.modal-body');
    notificationContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    notificationModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Affichage de la note moyenne de la recette
    let averageRatingElement = document.getElementById('average-rating');
    if (averageRatingElement) {
        let averageRating = parseFloat(averageRatingElement.getAttribute('data-average'));
        renderMushroomRating(averageRating, averageRatingElement);  // Appelle la fonction pour afficher la moyenne
    }

    // Affichage des notes dans les commentaires
    document.querySelectorAll('.mushrooms-average').forEach(container => {
        let userRating = parseFloat(container.getAttribute('data-average'));
        renderMushroomRating(userRating, container);  // Appelle la fonction pour chaque note utilisateur dans les commentaires
    });
});

// Fonction pour afficher les notes des champignons
function renderMushroomRating(rating, container) {
    const mushrooms = container.querySelectorAll('.mushroom');
    mushrooms.forEach((mushroom, index) => {
        const mushroomScore = index + 1;
        if (rating >= mushroomScore) {
            mushroom.classList.add('full');
        } else if (rating >= mushroomScore - 0.5) {
            mushroom.classList.add('half');
        } else {
            mushroom.classList.add('empty');
        }
    });
}

// Mettre à jour l'affichage des notes après soumission
function updateRatingDisplay(recipeId) {
    fetch(`/recipe/${recipeId}/current-rating`)
        .then(response => response.json())
        .then(data => {
            if (data.currentRating !== null) {
                // Mettre à jour l'affichage de la note dans l'interface
                let score = data.currentRating;
                let ratingElement = document.querySelector(`#rating-${score.toString().replace('.', '-')}`);
                if (ratingElement) {
                    ratingElement.checked = true;
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération de la note actuelle:', error);
        });
}

// Fonction pour ajouter les écouteurs d'événements sur les boutons radio
function addRatingEventListeners() {
    fetch('/api/check-login')
        .then(response => response.json())
        .then(data => {
            let isUserLoggedIn = data.isUserLoggedIn;

            document.querySelectorAll('.rating__input[type="radio"]').forEach(function(input) {
                input.addEventListener('click', function(event) {
                    if (!isUserLoggedIn) {
                        event.preventDefault();
                        loginModal.show();
                    } else {
                        let selectedRating = input.value;

                        // Récupérer l'ID de la recette et du profil
                        let recipeId = document.getElementById('ratingForm').getAttribute('data-recipe-id');
                        let profileId = document.getElementById('ratingForm').getAttribute('data-profile-id');

                        // Récupérer le token CSRF depuis le formulaire
                        let csrfToken = document.querySelector('input[name="_csrf_token"]').value;

                        // Construire l'URL de soumission
                        let submitRatingUrl = `/recipe/${recipeId}/rate`;

                        // Envoyer la note via une requête AJAX en JSON
                        fetch(submitRatingUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfToken
                            },
                            body: JSON.stringify({ score: selectedRating, profileId: profileId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.message) {
                                // Afficher le message dans la modale
                                displayNotification(data.message, 'success');

                                // Mettre à jour l'affichage de la note
                                updateRatingDisplay(recipeId);
                            } else {
                                displayNotification('Une erreur est survenue lors de la soumission.', 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la soumission de la note:', error);
                            displayNotification('Erreur technique. Réessayez plus tard.', 'danger');
                        });
                    }
                });
            });
        });
}

// Appel initial pour ajouter les écouteurs d'événements
addRatingEventListeners();