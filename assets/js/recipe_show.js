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
    // Gestion de l'ouverture de la modale pour définir l'URL actuelle
    let loginModalElement = document.getElementById('loginModal');
    loginModalElement.addEventListener('shown.bs.modal', function() {
        let targetPathInput = document.getElementById('targetPath');
        if (targetPathInput) {
            targetPathInput.value = window.location.href;  // Définit l'URL actuelle
        }
    });
    
    // Vérifier si l'utilisateur doit se connecter pour commenter
    let commentLoginButton = document.getElementById('commentLoginButton');
    if (commentLoginButton) {
        commentLoginButton.addEventListener('click', function(event) {
            event.preventDefault();
            loginModal.show();  // Ouvre la modale de connexion déjà définie pour les ratings
        });
    }

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

    // Logique AJAX pour modifier un commentaire en direct
    document.querySelectorAll('.edit-comment-button').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            // Récupérer l'ID du commentaire à modifier
            const commentId = this.getAttribute('data-comment-id');

            // Faire une requête AJAX pour obtenir le formulaire d'édition
            fetch(`/comment/${commentId}/edit-inline`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Vérifier que le conteneur du commentaire existe
                const commentContainer = document.querySelector(`#comment-${commentId}`);
                if (!commentContainer) {
                    console.error(`Comment container #comment-${commentId} introuvable dans le DOM`);
                    return;
                }

                // Remplacer le contenu du commentaire par le formulaire de modification
                commentContainer.innerHTML = data.formHtml;
            })
            .catch(error => console.error('Erreur lors de la récupération du formulaire de modification:', error));
        });
    });
    // Soumission du formulaire de modification en AJAX
    document.addEventListener('submit', function (event) {
        if (event.target.classList.contains('edit-comment-form')) {
            event.preventDefault(); // Empêcher la soumission classique du formulaire

            // Récupérer les données du formulaire
            const form = event.target;
            const formData = new FormData(form);
            const commentId = form.getAttribute('data-comment-id');
            
            // Envoyer la requête AJAX
            fetch(`/comment/${commentId}/edit-ajax`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.updatedCommentHtml) {
                    // Mettre à jour l'intégralité du conteneur du commentaire avec le nouveau contenu
                    const commentContainer = document.querySelector(`#comment-${commentId}`);
                    commentContainer.outerHTML = data.updatedCommentHtml;

                    // Optionnel : Afficher un message de succès
                    displayNotification('Commentaire mis à jour avec succès.', 'success');
                } else if (data.error) {
                    displayNotification('Erreur : ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la soumission AJAX :', error);
                displayNotification('Erreur lors de la mise à jour du commentaire.', 'danger');
            });
        }
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