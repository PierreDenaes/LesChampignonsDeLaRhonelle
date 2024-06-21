// Import Bootstrap (déjà inclus dans votre projet via Webpack Encore)
import { Modal } from 'bootstrap';

// Code pour gérer les recettes
document.addEventListener('DOMContentLoaded', function() {
    const recipesList = document.getElementById('recipes-list');
    const addRecipeButton = document.getElementById('add-recipe');
    const recipeForm = document.getElementById('recipe-form');
    const recipeModalElement = document.getElementById('recipeModal');
    const recipeModal = new Modal(recipeModalElement);
    const csrfToken = recipesList.getAttribute('data-csrf-token');

    // Fonction pour charger les recettes
    function loadRecipes() {
        fetch('/profile/recipes')
            .then(response => response.json())
            .then(data => {
                recipesList.innerHTML = '';
                data.forEach(recipe => {
                    const recipeCard = document.createElement('div');
                    recipeCard.classList.add('col-md-4');
                    recipeCard.innerHTML = `
                        <div class="card mb-3">
                            <img src="/images/recipes/${recipe.imageName}" class="card-img-top" alt="${recipe.title}">
                            <div class="card-body">
                                <h5 class="card-title">${recipe.title}</h5>
                                <p class="card-text">${recipe.description}</p>
                                <button class="btn btn-primary" onclick="viewRecipe(${recipe.id})">Voir</button>
                                <button class="btn btn-secondary" onclick="editRecipe(${recipe.id})">Modifier</button>
                                <button class="btn btn-danger" onclick="deleteRecipe(${recipe.id})">Supprimer</button>
                            </div>
                        </div>
                    `;
                    recipesList.appendChild(recipeCard);
                });
            });
    }

    // Charger les recettes lors du chargement de la page
    loadRecipes();

    // Fonction pour ajouter une recette
    addRecipeButton.addEventListener('click', function() {
        recipeForm.reset();
        recipeForm.action = '/profile/recipes/new';
        document.getElementById('recipeModalLabel').textContent = 'Ajouter une recette';
        recipeModal.show();
    });

    // Fonctions pour voir, éditer et supprimer une recette
    window.viewRecipe = function(id) {
        fetch(`/profile/recipes/${id}`)
            .then(response => response.json())
            .then(data => {
                alert(`Titre: ${data.title}\nDescription: ${data.description}`);
            });
    };

    window.editRecipe = function(id) {
        fetch(`/profile/recipes/${id}`)
            .then(response => response.json())
            .then(data => {
                recipeForm.action = `/profile/recipes/${id}/edit`;
                document.getElementById('recipeModalLabel').textContent = 'Modifier la recette';
                Object.keys(data).forEach(key => {
                    const input = document.querySelector(`#recipe-form [name="recipe[${key}]"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                recipeModal.show();
            });
    };

    window.deleteRecipe = function(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette recette ?')) {
            fetch(`/profile/recipes/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            }).then(() => {
                loadRecipes();
            });
        }
    };

    // Gestion de la soumission du formulaire via AJAX
    recipeForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(recipeForm);
        const url = recipeForm.action;
        const method = url.includes('edit') ? 'PUT' : 'POST';
        fetch(url, {
            method: method,
            body: formData,
        }).then(response => {
            if (response.ok) {
                recipeModal.hide();
                loadRecipes();
            } else {
                alert('Erreur lors de la sauvegarde de la recette.');
            }
        });
    });
});