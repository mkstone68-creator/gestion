<?php
// Connexion à la base de données et inclusion de la sécurité
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/Security.php';
include '../Includes/DatabaseOperations.php';

// Appliquer les headers de sécurité
Security::setSecurityHeaders();
$db = new DatabaseOperations($conn);

// Récupérer les données nécessaires des différentes tables via DatabaseOperations
$formationsList = $db->select('tblformation');
$studentsList = $db->select('tblstudents');
$classesList = $db->select('tblclass');
$classarmsList = $db->select('tblclassarms');
$teachersList = $db->select('tblteachers');
$sessionsList = $db->select('tblsessionterm');

$statusMsg = "";

if (isset($_POST['generate'])) {
    // Récupérer et valider les données soumises par le formulaire
    $studentId = intval($_POST['studentId'] ?? 0);
    $formationId = intval($_POST['formationId'] ?? 0);
    $classId = intval($_POST['classId'] ?? 0);
    $classArmId = intval($_POST['classArmId'] ?? 0);
    $teacherId = intval($_POST['teacherId'] ?? 0);
    $sessionId = intval($_POST['sessionId'] ?? 0);
    $mention = Security::validateString($_POST['mention'] ?? '', 1, 50);

    // Validation des ID
    if ($studentId <= 0 || $formationId <= 0 || $classId <= 0 || $classArmId <= 0 || $teacherId <= 0 || $sessionId <= 0 || !$mention) {
        $statusMsg = "<div class='alert alert-danger'>Veuillez sélectionner des champs valides.</div>";
    } else {
        // Récupérer les détails individuels de manière sécurisée (Prepared Statements)
        $studentArr = $db->select('tblstudents', ['Id' => $studentId]);
        $formationArr = $db->select('tblformation', ['id' => $formationId]);
        $classArr = $db->select('tblclass', ['Id' => $classId]);
        $classArmArr = $db->select('tblclassarms', ['Id' => $classArmId]);
        $teacherArr = $db->select('tblteachers', ['Id' => $teacherId]);
        $sessionArr = $db->select('tblsessionterm', ['Id' => $sessionId]);

        if (!empty($studentArr) && !empty($formationArr) && !empty($classArr) && !empty($classArmArr) && !empty($teacherArr) && !empty($sessionArr)) {
            $student = $studentArr[0];
            $formation = $formationArr[0];
            $class = $classArr[0];
            $classArm = $classArmArr[0];
            $teacher = $teacherArr[0];
            $session = $sessionArr[0];

            // 1. Sauvegarder dans la table tblattestation
            $insertData = [
                'studentId' => $studentId,
                'formationId' => $formationId,
                'classId' => $classId,
                'classArmId' => $classArmId,
                'teacherId' => $teacherId,
                'sessionId' => $sessionId,
                'mention' => $mention
            ];
            
            $inserted = $db->insert('tblattestation', $insertData);
            
            if ($inserted) {
                Security::logSecurityEvent('CERTIFICATE_GENERATED', "Certificate generated for student ID $studentId, mention: $mention", 'INFO');
                
                // 2. Afficher et lancer l'impression (Style premium et responsive)
                echo "<!DOCTYPE html>
                <html lang='fr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Impression - Attestation de Formation</title>
                    <link href='../Student/css/premium.css' rel='stylesheet'>
                </head>
                <body style='background-color: #fff;'>
                    <div class='attestation-container'>
                        <div class='attestation-header'>
                            <img src='images1.png' alt='Logo de l\'entreprise'>
                        </div>
                        <h2 class='attestation-title'>Attestation de Formation</h2>
                        <div class='attestation-body'>
                            <p>La direction des études certifie par la présente que l'étudiant(e)</p>
                            <h3 style='font-size: 1.8rem; font-weight: 800; margin: 20px 0; text-transform: uppercase;'>" . Security::escapeHTML($student['firstName'] . ' ' . $student['lastName']) . "</h3>
                            <p>a suivi avec assiduité et succès la formation professionnelle intitulée :</p>
                            <p><span class='attestation-highlight' style='font-size: 1.4rem; padding: 5px 10px; display: inline-block; margin: 10px 0;'>" . Security::escapeHTML($formation['name']) . "</span></p>
                            <p>dans la classe de <strong>" . Security::escapeHTML($class['className']) . "</strong> (Lieu d'enseignement : " . Security::escapeHTML($classArm['classArmName']) . "),</p>
                            <p>sous la supervision pédagogique de l'enseignant(e) <strong>" . Security::escapeHTML($teacher['firstName'] . ' ' . $teacher['lastName']) . "</strong>,</p>
                            <p>durant la session d'apprentissage <strong>" . Security::escapeHTML($session['sessionName']) . "</strong>.</p>
                            <p style='margin-top: 25px;'>En foi de quoi, la présente attestation lui est délivrée avec la mention : <strong>" . Security::escapeHTML($mention) . "</strong></p>
                        </div>
                        <div class='attestation-footer'>
                            <div class='attestation-date text-left'>
                                Fait le " . date('d/m/Y') . "
                            </div>
                            <div class='attestation-signature'>
                                <p style='font-weight: bold; margin-bottom: 50px;'>La Direction des Études</p>
                                <div style='border-top: 1px dashed #cbd5e1; width: 180px;'>
                                    <span style='font-size: 0.75rem; color: #64748b;'>Signature électronique</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            // Retourner au formulaire après l'impression
                            setTimeout(function() {
                                window.location = 'attestation.php';
                            }, 1000);
                        }
                    </script>
                </body>
                </html>";
                exit;
            } else {
                $statusMsg = "<div class='alert alert-danger'>Une erreur est survenue lors de l'enregistrement de l'attestation.</div>";
            }
        } else {
            $statusMsg = "<div class='alert alert-danger'>Erreur : Références de données invalides.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération d'Attestation - Secel Formation</title>
    <!-- Utiliser le style Bootstrap standard et notre style premium -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../Student/css/premium.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h6 class="premium-card-title"><i class="fas fa-award text-success mr-2"></i>Générer une Attestation de Formation</h6>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary font-weight-bold" style="border-radius: var(--border-radius-sm);"><i class="fas fa-arrow-left mr-1"></i>Retour</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($statusMsg)) echo $statusMsg; ?>
                        
                        <form method="POST" action="attestation.php">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Étudiant :</label>
                                <select name="studentId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner l'Étudiant --</option>
                                    <?php if (!empty($studentsList)): ?>
                                        <?php foreach ($studentsList as $row): ?>
                                            <option value="<?php echo $row['Id']; ?>"><?php echo Security::escapeHTML($row['lastName'] . ' ' . $row['firstName']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Formation :</label>
                                <select name="formationId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner la Formation --</option>
                                    <?php if (!empty($formationsList)): ?>
                                        <?php foreach ($formationsList as $row): ?>
                                            <option value="<?php echo $row['id']; ?>"><?php echo Security::escapeHTML($row['name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Classe :</label>
                                <select name="classId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner la Classe --</option>
                                    <?php if (!empty($classesList)): ?>
                                        <?php foreach ($classesList as $row): ?>
                                            <option value="<?php echo $row['Id']; ?>"><?php echo Security::escapeHTML($row['className']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Lieu d'Enseignement :</label>
                                <select name="classArmId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner la Cohorte/Lieu --</option>
                                    <?php if (!empty($classarmsList)): ?>
                                        <?php foreach ($classarmsList as $row): ?>
                                            <option value="<?php echo $row['Id']; ?>"><?php echo Security::escapeHTML($row['classArmName']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Enseignant :</label>
                                <select name="teacherId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner l'Enseignant --</option>
                                    <?php if (!empty($teachersList)): ?>
                                        <?php foreach ($teachersList as $row): ?>
                                            <option value="<?php echo $row['Id']; ?>"><?php echo Security::escapeHTML($row['lastName'] . ' ' . $row['firstName']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-800">Session :</label>
                                <select name="sessionId" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="">-- Sélectionner la Session --</option>
                                    <?php if (!empty($sessionsList)): ?>
                                        <?php foreach ($sessionsList as $row): ?>
                                            <option value="<?php echo $row['Id']; ?>"><?php echo Security::escapeHTML($row['sessionName']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-gray-800">Mention obtenue :</label>
                                <select name="mention" class="form-control" required style="border-radius: var(--border-radius-sm);">
                                    <option value="Très Bien">Très Bien</option>
                                    <option value="Bien">Bien</option>
                                    <option value="Assez Bien">Assez Bien</option>
                                    <option value="Passable">Passable</option>
                                </select>
                            </div>
                           
                            <button type="submit" name="generate" class="btn btn-premium-success btn-block py-3">
                                <i class="fas fa-certificate mr-2"></i>Générer et Imprimer l'Attestation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>