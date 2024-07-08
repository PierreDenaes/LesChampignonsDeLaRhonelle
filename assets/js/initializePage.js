import { handleAddIngredient, handleAddStep } from './formHandlers';
import { loadRecipes, viewRecipe, attachDeleteHandlers, editRecipe } from './recipeHandlers';
import { showNotification } from './notificationHandlers';
import { Tab } from 'bootstrap';

export function initializePage() {
    const recipesList = document.getElementById('recipes-list');
    const recipeFormNew = document.getElementById('recipe-form-new');
    const notification = document.getElementById('notification');

    loadRecipes(recipesList, attachDeleteHandlers);

    window.viewRecipe = viewRecipe;
    window.editRecipe = editRecipe;

    recipeFormNew.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(recipeFormNew);
        const url = recipeFormNew.action;

        fetch(url, {
            method: 'POST',
            body: formData,
        }).then(response => {
            if (response.ok) {
                resetForm(recipeFormNew); // Appeler la fonction pour réinitialiser le formulaire
                showNotification(notification, 'Votre recette a été ajoutée et est en attente de vérification par l\'administrateur.');
                loadRecipes(recipesList, attachDeleteHandlers);
                const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                tabTrigger.show();
            } else {
                alert('Erreur lors de la création de la recette.');
            }
        });
    });

    handleAddIngredient(); // Initialiser les gestionnaires d'événements pour les ingrédients
    handleAddStep(); // Initialiser les gestionnaires d'événements pour les étapes
}

// Fonction pour réinitialiser le formulaire et ses éléments dynamiques
function resetForm(form) {
    form.reset();

    // Réinitialiser les listes d'ingrédients et d'étapes
    document.querySelectorAll('.ingredient-list').forEach(list => {
        list.innerHTML = ''; // Vider la liste des ingrédients
    });
    document.querySelectorAll('.step-list').forEach(list => {
        list.innerHTML = ''; // Vider la liste des étapes
    });

    // Réinitialiser les gestionnaires d'événements
    handleAddIngredient();
    handleAddStep();
}