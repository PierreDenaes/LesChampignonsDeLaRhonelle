import { Tab } from 'bootstrap';
import '../styles/site/recipe.scss';

document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

function initializePage() {
    const recipesList = document.getElementById('recipes-list');
    const recipeForm = document.getElementById('recipe-form');
    const notification = document.getElementById('notification');
    let list = document.getElementById('ingredient-list');
    
    // Gestion des ingrédients
    if (list) {
        let addButton = document.getElementById('add-ingredient');
        let newWidget = list.dataset.prototype;
        let index = list.children.length;

        addButton.addEventListener('click', () => {
            let newLi = document.createElement('li');
            newLi.innerHTML = newWidget.replace(/__name__/g, index++) + '<button type="button" class="remove-ingredient btn btn-danger">Supprimer</button>';
            list.appendChild(newLi);
        });

        list.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-ingredient')) {
                e.target.parentElement.remove();
            }
        });
    }
    
    // Gestion des étapes de recette
    let etapeRecettesList = document.getElementById('etapeRecettes-list');

    if (etapeRecettesList) {
        let addEtapeRecetteButton = document.getElementById('add-etapeRecette');
        let newEtapeRecetteWidget = etapeRecettesList.dataset.prototype;
        let etapeRecetteIndex = etapeRecettesList.children.length;

        addEtapeRecetteButton.addEventListener('click', () => {
            let newLi = document.createElement('li');
            newLi.innerHTML = newEtapeRecetteWidget.replace(/__name__/g, etapeRecetteIndex++) + '<button type="button" class="remove-etapeRecette btn btn-danger">Supprimer</button>';
            etapeRecettesList.appendChild(newLi);
        });

        etapeRecettesList.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-etapeRecette')) {
                e.target.parentElement.remove();
            }
        });
    }

    // Chargement des recettes
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

    loadRecipes();

    // Fonction pour afficher une recette
    window.viewRecipe = function(id) {
        fetch(`/profile/recipes/${id}`)
            .then(response => response.json())
            .then(data => {
                alert(`Titre: ${data.title}\nDescription: ${data.description}`);
            });
    };

    // Fonction pour éditer une recette
    window.editRecipe = function(id) {
        fetch(`/profile/recipes/${id}`)
            .then(response => response.json())
            .then(data => {
                recipeForm.action = `/profile/recipes/${id}/edit`; // Mise à jour de l'action du formulaire pour l'édition
                Object.keys(data).forEach(key => {
                    const input = document.querySelector(`#recipe-form [name="recipe[${key}]"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                const tabTrigger = new Tab(document.querySelector('.nav-link[href="#new-recipe"]'));
                tabTrigger.show();
            });
    };

    // Gestionnaire pour les boutons de suppression
    function attachDeleteHandlers() {
        const deleteButtons = document.querySelectorAll('.btn-danger[data-id]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const imageName = this.getAttribute('data-image-name');
                const csrfToken = recipesList.getAttribute('data-csrf-token');

                if (confirm('Êtes-vous sûr de vouloir supprimer cette recette ?')) {
                    fetch(`/profile/recipes/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ imageName: imageName })
                    }).then(response => {
                        if (response.ok) {
                            showNotification('Recette supprimée avec succès!');
                            loadRecipes();
                        } else {
                            alert('Erreur lors de la suppression de la recette.');
                        }
                    });
                }
            });
        });
    }

    // Fonction pour afficher les notifications
    function showNotification(message) {
        notification.textContent = message;
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Soumission du formulaire de recette
    recipeForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(recipeForm);
        const url = recipeForm.action;
        const method = url.includes('edit') ? 'PUT' : 'POST'; // Utilise 'PUT' pour la mise à jour et 'POST' pour la création

        fetch(url, {
            method: method,
            body: formData,
        }).then(response => {
            if (response.ok) {
                recipeForm.reset();
                showNotification('Recette mise à jour avec succès!');
                const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                tabTrigger.show();
                loadRecipes();
            } else {
                alert('Erreur lors de la mise à jour de la recette.');
            }
        });
    });
}