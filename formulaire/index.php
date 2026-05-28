<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Inscription - Gestion Utilisateurs</title>
</head>

<body>
    <div class="container">
        <h2>Inscription</h2>
        <form id="registrationForm" method="POST" action="submit.php">
            <div class="form-group">
                <label for="name">Nom:</label>
                <i class="fas fa-user icon"></i>
                <input type="text" id="name" name="firstName" placeholder="Votre prénom" required>
            </div>

            <div class="form-group">
                <label for="lastName">Nom de famille:</label>
                <i class="fas fa-user icon"></i>
                <input type="text" id="lastName" name="lastName" placeholder="Votre nom de famille" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <i class="fas fa-envelope icon"></i>
                <input type="email" id="email" name="emailAddress" placeholder="Votre email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <i class="fas fa-lock icon"></i>
                <input type="password" id="password" name="password" placeholder="Votre mot de passe" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe:</label>
                <i class="fas fa-lock icon"></i>
                <input type="password" id="confirm" name="confirm" placeholder="Confirmer le mot de passe" required minlength="6">
            </div>

            <div class="form-group">
                <label for="role">Rôle:</label>
                <i class="fas fa-user-tag icon"></i>
                <select id="role" name="role" required>
                    <option value="">-- Sélectionnez votre rôle --</option>
                    <option value="Administrator">Administrateur</option>
                    <option value="ClassTeacher">Enseignant</option>
                    <option value="Student">Étudiant</option>
                </select>
            </div>

            <button type="submit">Register</button>
            <div id="message"></div>
            <button class="btn btn-theme btn-block" onclick="window.location.href='index1.php'" type="reset"><i class="fa fa-back"></i> return</button>

        </form>
    </div>

    <script src="script.js"></script>
</body>

</html>