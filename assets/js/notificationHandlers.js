import * as bootstrap from 'bootstrap';

export function showNotification(message, type, footerButtons = []) {
    let notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
    let notificationBody = document.getElementById('notificationModal').querySelector('.modal-body');
    let notificationFooter = document.getElementById('notificationModalFooter');

    // Injecter le message dans le corps de la modale
    notificationBody.innerHTML = `<div class="alert alert-${type}">${message}</div>`;

    // Vider le footer et ajouter les boutons
    notificationFooter.innerHTML = '';
    footerButtons.forEach(button => {
        const buttonElement = document.createElement('button');
        buttonElement.classList.add('btn', ...button.classes);
        buttonElement.textContent = button.label;
        if (button.dismiss) {
            buttonElement.setAttribute('data-bs-dismiss', 'modal');
        }
        buttonElement.addEventListener('click', button.onClick);
        notificationFooter.appendChild(buttonElement);
    });

    // Afficher la modale
    notificationModal.show();
}