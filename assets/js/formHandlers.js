// formHandlers.js
export function handleAddIngredient() {
    document.querySelectorAll('.ingredient-container').forEach(container => {
        const list = container.querySelector('.ingredient-list');
        const addButton = container.querySelector('.add-ingredient');
        const newWidget = list.dataset.prototype;
        let index = list.children.length;

        addButton.addEventListener('click', () => {
            const newLi = document.createElement('li');
            newLi.classList.add('list-group-item');
            newLi.innerHTML = newWidget.replace(/__name__/g, index) + '<button type="button" class="remove-ingredient btn btn-danger mt-1">Supprimer</button>';
            list.appendChild(newLi);
            index++;
        });

        list.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-ingredient')) {
                e.target.parentElement.remove();
            }
        });
    });
}

export function handleAddStep() {
    document.querySelectorAll('.step-container').forEach(container => {
        const list = container.querySelector('.step-list');
        const addButton = container.querySelector('.add-step');
        const newWidget = list.dataset.prototype;
        let index = list.children.length; // Prendre en compte le nombre d'étapes existantes

        addButton.addEventListener('click', () => {
            const newLi = document.createElement('li');
            newLi.classList.add('list-group-item');

            // Insérer le widget de formulaire et ajouter un champ stepNumber
            newLi.innerHTML = newWidget.replace(/__name__/g, index);
            
            const stepNumberField = newLi.querySelector('input[name$="[stepNumber]"]');
            if (stepNumberField) {
                stepNumberField.value = index + 1; // Remplir automatiquement la valeur
            }

            newLi.innerHTML += '<button type="button" class="remove-step btn btn-danger mt-1">Supprimer</button>';
            list.appendChild(newLi);
            index++;
            

            // Mettre à jour les numéros d'étapes
            updateStepNumbers(list);
        });

        // Gestion de la suppression
        list.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('remove-step')) {
                e.target.parentElement.remove();
                updateStepNumbers(list);
            }
        });
    });
}

// Fonction pour mettre à jour les numéros après suppression
function updateStepNumbers(list) {
    list.querySelectorAll('.list-group-item').forEach((item, idx) => {
        const stepNumberField = item.querySelector('input[name$="[stepNumber]"]');
        if (stepNumberField) {
            stepNumberField.value = idx + 1; // Mise à jour des numéros après suppression
        }
    });
}