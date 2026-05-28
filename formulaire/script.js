document.getElementById("registrationForm").addEventListener("submit", function(event) {
    const password = document.getElementById("password").value;
    const message = document.getElementById("message");

    // Validation de mot de passe
    if (password.length < 6) {
        message.innerText = "Le mot de passe doit contenir au moins 6 caractères.";
        message.style.color = "red";
        event.preventDefault(); // Empêche l'envoi du formulaire
    } else {
        message.innerText = ""; // Réinitialise le message
    }
});
