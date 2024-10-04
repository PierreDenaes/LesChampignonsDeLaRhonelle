// recipeHandlers.js
import { showNotification } from './notificationHandlers';
import { Modal, Tab } from 'bootstrap';
import { handleAddIngredient, handleAddStep } from './formHandlers'; // Importer les fonctions nécessaires
import * as bootstrap from 'bootstrap';

export function loadRecipes(recipesList, attachDeleteHandlers) {
    fetch('/profile/recipes')
        .then(response => response.json())
        .then(data => {
            recipesList.innerHTML = '';
            data.forEach(recipe => {
                // Vérifie si la recette est active avant de l'afficher
                if (recipe.isActive) {
                    const recipeCard = document.createElement('div');
                    recipeCard.classList.add('col-md-4');
                    recipeCard.innerHTML = `
                        <div class="card mb-3">
                            <img src="/images/recipes/${recipe.imageName || 'default/default-recipe.webp'}" class="card-img-top" alt="${recipe.title}">
                            <div class="card-body">
                                <h5 class="card-title">${recipe.title}</h5>
                                <p class="card-text">${recipe.description}</p>
                                <button class="btn btn-primary" onclick="viewRecipe(${recipe.id})">Voir</button>
                                <button class="btn btn-secondary" onclick="editRecipe(${recipe.id})">Modifier</button>
                                <button class="btn btn-danger" data-id="${recipe.id}" data-image-name="${recipe.imageName}">Supprimer</button>
                            </div>
                        </div>
                    `;
                    recipesList.appendChild(recipeCard);
                }
            });
            attachDeleteHandlers();
        });
}

// Fonction pour voir une recette
export function viewRecipe(id) {
    fetch(`/profile/recipes/${id}/show`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json(); // Assurez-vous que la réponse est bien du JSON
        })
        .then(data => {
            alert(`Titre: ${data.title}\nDescription: ${data.description}`);
        })
        .catch(error => {
            console.error('Error fetching recipe:', error);
        });
}

export function editRecipe(id) {
    fetch(`/profile/recipes/${id}/edit-form`)
        .then(response => response.text())
        .then(html => {
            const editContainer = document.getElementById('recipe-edit-container');
            if (editContainer) {
                editContainer.innerHTML = html;
                initializeEditForm();
                // Afficher la modale
                const editRecipeModalElement = document.getElementById('editRecipeModal');
                const editRecipeModal = new Modal(editRecipeModalElement);
                editRecipeModal.show();
            } else {
                console.error('Element with ID "recipe-edit-container" not found.');
            }
        }).catch(error => {
            console.error('Error fetching edit form:', error);
        });
}

function initializeEditForm() {
    const recipeFormEdit = document.getElementById('recipe-form-edit');
    if (recipeFormEdit) {
        recipeFormEdit.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(recipeFormEdit);
            const url = recipeFormEdit.action;

            // Supprimer les messages d'erreur existants avant la nouvelle soumission
            const errorFields = recipeFormEdit.querySelectorAll('.is-invalid');
            errorFields.forEach(field => {
                field.classList.remove('is-invalid');
                const errorDiv = field.parentElement.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            });

            fetch(url, {
                method: 'POST',
                body: formData,
            }).then(response => {
                if (response.ok) {
                    response.json().then(data => {
                        showNotification(data.message, 'success'); // Afficher la notification de succès
                        recipeFormEdit.reset();
                        loadRecipes(document.getElementById('recipes-list'), attachDeleteHandlers);
                        const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                        tabTrigger.show();
                        const editRecipeModalElement = document.getElementById('editRecipeModal');
                        const editRecipeModal = Modal.getInstance(editRecipeModalElement);
                        if (editRecipeModal) {
                            editRecipeModal.hide(); // Fermer la modale
                        }
                    });
                } else {
                    response.json().then(data => {
                        // Affichage des erreurs de validation
                        Object.keys(data.errors).forEach(field => {
                            const inputField = recipeFormEdit.querySelector(`[name*="[${field}]"]`);
                            if (inputField) {
                                const errorDiv = document.createElement('div');
                                errorDiv.classList.add('invalid-feedback');
                                errorDiv.innerText = data.errors[field].join(', ');
                                inputField.classList.add('is-invalid');
                                inputField.parentElement.appendChild(errorDiv);
                            }
                        });
                    });
                }
            }).catch(error => {
                console.error('Error updating recipe:', error);
            });
        });

        // Initialiser les gestionnaires pour ajouter/supprimer des ingrédients et des étapes
        const ingredientListEdit = document.querySelector('.ingredient-list');
        if (ingredientListEdit) {
            handleAddIngredient(ingredientListEdit);
        }

        const stepListEdit = document.querySelector('.step-list');
        if (stepListEdit) {
            handleAddStep(stepListEdit);
        }
    } else {
        console.error('Element with ID "recipe-form-edit" not found.');
    }
}

export function attachDeleteHandlers() {
    const deleteButtons = document.querySelectorAll('.btn-danger[data-id]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const imageName = this.getAttribute('data-image-name');
            const csrfToken = document.getElementById('recipes-list').getAttribute('data-csrf-token');

            showNotification('Êtes-vous sûr de vouloir supprimer cette recette ?', 'warning', [
                { 
                    label: 'Annuler', 
                    classes: ['btn-secondary'], 
                    dismiss: true 
                },
                { 
                    label: 'Supprimer', 
                    classes: ['btn-danger'], 
                    onClick: function() {
                        fetch(`/profile/recipes/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ imageName: imageName })
                        }).then(response => {
                            if (response.ok) {
                                // Afficher le message de succès
                                showNotification('Recette supprimée avec succès!', 'success');
                                loadRecipes(document.getElementById('recipes-list'), attachDeleteHandlers); 
                                
                                // S'assurer que la modal est fermée complètement après l'affichage du message
                                const notificationModalElement = document.getElementById('notificationModal');
                                const modalInstance = bootstrap.Modal.getInstance(notificationModalElement);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            } else {
                                showNotification('Erreur lors de la suppression de la recette.', 'danger');
                            }
                        });
                    }
                }
            ]);
        });
    });
}