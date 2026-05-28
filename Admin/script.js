// Ajout d'une animation d'apparition lors du chargement de la page
document.addEventListener('DOMContentLoaded', function () {
    const formElements = document.querySelectorAll('.form-group');
    formElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.2}s`;
    });
});

// Fonction d'impression pour l'attestation
function printAttestation() {
    window.print();
}