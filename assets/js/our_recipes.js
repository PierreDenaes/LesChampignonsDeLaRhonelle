import '../styles/site/our_recipes.scss';
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

// Pour gérer les clics sur les thumbnails
document.querySelectorAll('.thumbnail').forEach(thumbnail => {
    thumbnail.addEventListener('click', function () {
        const imageName = this.getAttribute('data-image');
        const elementId = this.closest('.gallery-thumbnails').previousElementSibling.id;
        changeMainImage(imageName, elementId);
    });
});