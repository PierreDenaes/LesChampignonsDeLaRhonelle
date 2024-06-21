document.addEventListener('DOMContentLoaded', function() {
    const recipesList = document.getElementById('recipes-list');
    const recipeForm = document.getElementById('recipe-form');

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
                Object.keys(data).forEach(key => {
                    const input = document.querySelector(`#recipe-form [name="recipe[${key}]"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                const tabTrigger = new bootstrap.Tab(document.querySelector('.nav-link[href="#new-recipe"]'));
                tabTrigger.show();
            });
    };

    window.deleteRecipe = function(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette recette ?')) {
            fetch(`/profile/recipes/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(id)
                }
            }).then(() => {
                loadRecipes();
            });
        }
    };

    // Fonction pour obtenir le jeton CSRF
    function getCsrfToken(id) {
        return '{{ csrf_token("delete") }}'.replace('delete', 'delete' + id);
    }

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
                const tabTrigger = new bootstrap.Tab(document.querySelector('.nav-link[href="#recipes"]'));
                tabTrigger.show();
                loadRecipes();
            } else {
                alert('Erreur lors de la sauvegarde de la recette.');
            }
        });
    });
});