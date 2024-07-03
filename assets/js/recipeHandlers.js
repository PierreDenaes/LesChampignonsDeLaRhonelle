// recipeHandlers.js
import { showNotification } from './notificationHandlers';
import { Modal, Tab } from 'bootstrap';
import { handleAddIngredient, handleAddStep } from './formHandlers'; // Importer les fonctions nécessaires

export function loadRecipes(recipesList, attachDeleteHandlers) {
    fetch('/profile/recipes')
        .then(response => response.json())
        .then(data => {
            recipesList.innerHTML = '';
            data.forEach(recipe => {
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
            });
            attachDeleteHandlers();
        });
}

export function viewRecipe(id) {
    fetch(`/profile/recipes/${id}`)
        .then(response => response.json())
        .then(data => {
            alert(`Titre: ${data.title}\nDescription: ${data.description}`);
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

            fetch(url, {
                method: 'POST', // Utiliser POST pour supporter multipart/form-data
                body: formData,
            }).then(response => {
                if (response.ok) {
                    recipeFormEdit.reset();
                    showNotification(document.getElementById('notification'), 'Recette mise à jour avec succès!');
                    loadRecipes(document.getElementById('recipes-list'), attachDeleteHandlers);
                    const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                    tabTrigger.show();
                    const editRecipeModalElement = document.getElementById('editRecipeModal');
                    const editRecipeModal = Modal.getInstance(editRecipeModalElement);
                    if (editRecipeModal) {
                        editRecipeModal.hide(); // Fermer la modale
                    }
                } else {
                    response.json().then(data => {
                        alert('Erreur lors de la mise à jour de la recette: ' + data.details);
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

            if (confirm('Êtes-vous sûr de vouloir supprimer cette recette ?')) {
                fetch(`/profile/recipes/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ imageName: imageName })
                }).then(response => {
                    if (response.ok) {
                        showNotification(document.getElementById('notification'), 'Recette supprimée avec succès!');
                        loadRecipes(document.getElementById('recipes-list'), attachDeleteHandlers); // Rafraîchit la liste des recettes
                    } else {
                        alert('Erreur lors de la suppression de la recette.');
                    }
                });
            }
        });
    });
}