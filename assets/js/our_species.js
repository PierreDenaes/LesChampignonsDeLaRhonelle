import '../styles/site/our_species.scss';
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import PhotoSwipe from 'photoswipe';
import Isotope from 'isotope-layout';

// Fonction pour changer l'image principale dans une section donnée
function changeMainImage(imageName, elementId) {
    const mainImage = document.querySelector(`#${elementId}`);
    const imgElement = mainImage.querySelector('img');
    const sources = mainImage.querySelectorAll('source');
    
    const sizes = [300, 600, 900];

    sizes.forEach((size, index) => {
        sources[index].srcset = `/images/elements/${imageName}-${size}.webp`;
    });

    imgElement.src = `/images/elements/${imageName}-900.webp`;
}

// Gestion des clics sur les thumbnails
document.querySelectorAll('.thumbnail').forEach(thumbnail => {
    thumbnail.addEventListener('click', function () {
        const imageName = this.getAttribute('data-image');
        const elementId = this.closest('.gallery-thumbnails').previousElementSibling.id;
        changeMainImage(imageName, elementId);
    });
});

// Récupération et initialisation de la galerie via API
document.addEventListener('DOMContentLoaded', function () {
    fetch('/api/gallery')
        .then(response => response.json())
        .then(data => {
            const galleryContainer = document.getElementById('gallery');
            galleryContainer.innerHTML = ''; // Vider le contenu de la galerie

            // Ajouter chaque image dans la galerie
            data.forEach(image => {
                const galleryItem = document.createElement('div');
                galleryItem.classList.add('gallery-item', image.category); // Appliquer les catégories pour le filtrage
                
                galleryItem.innerHTML = `
                    <a href="${image.imagePaths['1200']}" data-pswp-width="1200" data-pswp-height="800" target="_blank">
                        <picture>
                            <source srcset="${image.imagePaths['160']}" media="(max-width: 160px)">
                            <source srcset="${image.imagePaths['320']}" media="(max-width: 320px)">
                            <source srcset="${image.imagePaths['640']}" media="(max-width: 640px)">
                            <source srcset="${image.imagePaths['1200']}" media="(min-width: 641px)">
                            <img src="${image.imagePaths['320']}" alt="${image.title}" class="img-fluid">
                        </picture>
                    </a>
                `;

                galleryContainer.appendChild(galleryItem); // Ajouter l'élément à la galerie
            });

            // Initialisation de Isotope pour le filtrage
            const gallery = new Isotope('#gallery', {
                itemSelector: '.gallery-item',
                layoutMode: 'fitRows',
                percentPosition: true
            });

            // Sélectionner tous les boutons de filtrage
            const filterButtons = document.querySelectorAll('.filter-btn');

            // Ajout de l'événement de clic à chaque bouton de filtrage
            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const filterValue = button.getAttribute('data-filter'); // Récupère la valeur de filtrage

                    // Appliquer le filtre aux éléments de la galerie
                    gallery.arrange({ filter: filterValue });

                    // Mettre à jour l'apparence du bouton actif
                    filterButtons.forEach(btn => {
                        btn.classList.remove('btn-blue');
                        btn.classList.add('btn-secondary');
                    });
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-blue'); // Activer le bouton sélectionné
                });
            });

            // S'assurer que les images sont bien chargées avant de réarranger la galerie
            const imagesLoaded = () => {
                const images = document.querySelectorAll('#gallery img');
                let imagesToLoad = images.length;

                images.forEach(img => {
                    if (img.complete) {
                        imagesToLoad--;
                    } else {
                        img.addEventListener('load', () => {
                            imagesToLoad--;
                            if (imagesToLoad === 0) {
                                gallery.layout(); // Réarranger la grille après le chargement des images
                            }
                        });
                    }
                });

                if (imagesToLoad === 0) {
                    gallery.layout(); // Si toutes les images sont déjà chargées
                }
            };

            imagesLoaded(); // Appel de la fonction au chargement de la page

            // Initialisation de PhotoSwipe pour la lightbox
            const lightbox = new PhotoSwipeLightbox({
                gallery: '#gallery',
                children: 'a',
                pswpModule: PhotoSwipe
            });
            lightbox.init();
        })
        .catch(error => console.error('Erreur lors de la récupération des images :', error));
});