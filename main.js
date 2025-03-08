document.addEventListener("DOMContentLoaded", function() {
    const images = document.querySelectorAll('.image-title img');
    const modal = document.createElement('div');
    const modalContent = document.createElement('img');
    const closeBtn = document.createElement('span');

    modal.classList.add('modal');
    closeBtn.classList.add('close');
    closeBtn.innerHTML = '&times;';

    modal.appendChild(closeBtn);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    images.forEach(image => {
        image.addEventListener('click', function() {
            const fullsizeUrl = this.getAttribute('data-fullsize');
            modalContent.src = fullsizeUrl;
            modal.style.display = "flex";
            setTimeout(() => {
                modal.classList.add('show');
                modalContent.classList.add('show');
            }, 10);
        });
    });

    closeBtn.addEventListener('click', function() {
        modal.classList.remove('show');
        modalContent.classList.remove('show');
        setTimeout(() => {
            modal.style.display = "none";
        }, 300); // Espera o fade-out antes de ocultar
    });

    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.classList.remove('show');
            modalContent.classList.remove('show');
            setTimeout(() => {
                modal.style.display = "none";
            }, 300); // Espera o fade-out antes de ocultar
        }
    });
});