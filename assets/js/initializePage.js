import { handleAddIngredient, handleAddStep } from './formHandlers';
import { loadRecipes, viewRecipe, attachDeleteHandlers, editRecipe } from './recipeHandlers';
import { showNotification } from './notificationHandlers';
import { Tab } from 'bootstrap';

export function initializePage() {
    const recipesList = document.getElementById('recipes-list');
    const recipeFormNew = document.getElementById('recipe-form-new');

    loadRecipes(recipesList, attachDeleteHandlers);

    window.viewRecipe = viewRecipe;
    window.editRecipe = editRecipe;

    recipeFormNew.addEventListener('submit', function(event) {
        event.preventDefault();
        // Mettre à jour les numéros d'étapes si nécessaire avant la soumission
        document.querySelectorAll('.step-list').forEach(list => {
            const steps = list.querySelectorAll('li');
            steps.forEach((step, index) => {
                const stepNumberInput = step.querySelector('input[name*="[stepNumber]"]');
                if (stepNumberInput) {
                    stepNumberInput.value = index + 1;  // Assigner correctement les numéros d'étape
                }
            });
        });

        const formData = new FormData(recipeFormNew);
        const url = recipeFormNew.action;

        fetch(url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resetForm(recipeFormNew); // Réinitialiser le formulaire
                showNotification(data.message, 'success'); // Afficher la notification de succès

                // Charger les nouvelles recettes
                loadRecipes(recipesList, attachDeleteHandlers);

                // Changer l'onglet actif vers la liste des recettes
                const tabTrigger = new Tab(document.querySelector('.nav-link[href="#recipes"]'));
                tabTrigger.show();
            } else if (data.errors) {
                // Afficher les erreurs de validation pour chaque champ
                Object.keys(data.errors).forEach(field => {
                    const inputField = recipeFormNew.querySelector(`[name*="[${field}]"]`);
                    if (inputField) {
                        const errorDiv = document.createElement('div');
                        errorDiv.classList.add('invalid-feedback');
                        errorDiv.innerText = data.errors[field];
                        inputField.classList.add('is-invalid');
                        inputField.parentElement.appendChild(errorDiv);
                    }
                });
            } else {
                showNotification('Erreur lors de la création de la recette.', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la soumission:', error);
            showNotification('Erreur technique. Réessayez plus tard.', 'danger');
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