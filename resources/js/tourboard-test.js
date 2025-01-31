const tourButton = document.getElementById('ativation-mai');
const overlay = document.getElementById('tour-overlay');
const closeButton = document.getElementById('tour-close');

    // Abrindo o overlay
    tourButton.addEventListener('click', () => {
        console.log('clicado')
        overlay.classList.remove('hidden');
    });

    // Fechando o overlay
    closeButton.addEventListener('click', () => {
        overlay.classList.add('hidden');
    });
