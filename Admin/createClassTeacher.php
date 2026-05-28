<?php 
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/Security.php';
include '../Includes/DatabaseOperations.php';

Security::setSecurityHeaders();
$db = new DatabaseOperations($conn);

// Vérifier le rôle administrateur
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Administrator') {
    header('Location: ../index.php');
    exit;
}

// ==============================
// VÉRIFICATION DES TABLES
// ==============================

$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'tblteachers'");
if(mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS `tblteachers` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `firstName` varchar(100) NOT NULL,
        `lastName` varchar(100) NOT NULL,
        `emailAddress` varchar(150) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `phoneNo` varchar(20) NOT NULL,
        `dateCreated` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTable);
}

$checkLinkTable = mysqli_query($conn, "SHOW TABLES LIKE 'tblteacherclass'");
if(mysqli_num_rows($checkLinkTable) == 0) {
    $createLink = "CREATE TABLE IF NOT EXISTS `tblteacherclass` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `teacherId` int NOT NULL,
        `classId` int NOT NULL,
        `dateCreated` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createLink);
}

// ==============================
// FONCTIONS
// ==============================

function generateDefaultPassword($firstName, $lastName) {
    $base = strtolower(substr($firstName, 0, 2) . substr($lastName, 0, 2));
    return $base . "2024";
}

function getAllClasses($db) {
    $result = $db->execute(
        "SELECT tblclass.*, tblsalle.salleName 
         FROM tblclass 
         LEFT JOIN tblsalle ON tblclass.salleId = tblsalle.Id 
         ORDER BY tblsalle.salleName, tblclass.annee"
    );
    $classes = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    return $classes;
}

function getTeacherClasses($db, $teacherId) {
    $teacherId = (int)$teacherId;
    $result = $db->execute(
        "SELECT tc.*, c.Id as classId, c.className, c.specialisation, s.salleName, c.annee
         FROM tblteacherclass tc
         JOIN tblclass c ON tc.classId = c.Id
         LEFT JOIN tblsalle s ON c.salleId = s.Id
         WHERE tc.teacherId = ?",
        'i', [$teacherId]
    );
    $classes = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    return $classes;
}

function deleteTeacherClasses($db, $teacherId) {
    $teacherId = (int)$teacherId;
    $db->delete('tblteacherclass', ['teacherId' => $teacherId]);
}

// ==============================
// TRAITEMENT
// ==============================

$statusMsg = '';
$editData = null;
$teacherClassIds = [];

// CREATE
if(isset($_POST['save'])){
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClassTeacher.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $firstName = Security::validateString($_POST['firstName'] ?? '', 1, 100);
        $lastName = Security::validateString($_POST['lastName'] ?? '', 1, 100);
        $emailAddress = Security::validateEmail($_POST['emailAddress'] ?? '');
        $phoneNo = Security::validateString($_POST['phoneNo'] ?? '', 1, 20);
        $classes = $_POST['classes'] ?? [];
        
        if (!$firstName || !$lastName || !$emailAddress || !$phoneNo) {
            $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis et doivent être valides.</div>";
        } else {
            $defaultPassword = generateDefaultPassword($firstName, $lastName);
            $hashedPassword = Security::hashPassword($defaultPassword);
            
            // Vérifier si l'email existe déjà
            $existing = $db->execute(
                "SELECT Id FROM tblteachers WHERE emailAddress = ?",
                's', [$emailAddress]
            );
            
            if ($existing && $existing->num_rows > 0) {
                $statusMsg = "<div class='alert alert-danger'>Cet email existe déjà !</div>";
            } else {
                // 1. Insérer dans tblteachers
                $teacherId = $db->insert('tblteachers', [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'password' => $hashedPassword,
                    'phoneNo' => $phoneNo
                ]);

                if ($teacherId) {
                    // 2. Insérer dans tblteacherclass (liaison)
                    if(!empty($classes)){
                        foreach($classes as $classId){
                            $classId = (int)$classId;
                            if ($classId > 0) {
                                $db->insert('tblteacherclass', ['teacherId' => $teacherId, 'classId' => $classId]);
                            }
                        }
                    }
                    
                    // 3. CRÉER LE COMPTE UTILISATEUR DANS tblusers
                    $userInsert = $db->insert('tblusers', [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'emailAddress' => $emailAddress,
                        'password' => $hashedPassword,
                        'role' => 'ClassTeacher',
                        'phoneNo' => $phoneNo
                    ]);
                    
                    if($userInsert) {
                        // Récupérer les noms des classes
                        $classesNames = [];
                        foreach($classes as $classId){
                            $classId = (int)$classId;
                            $classResult = $db->execute(
                                "SELECT s.salleName, c.annee, c.specialisation FROM tblclass c 
                                 LEFT JOIN tblsalle s ON c.salleId = s.Id WHERE c.Id = ?",
                                'i', [$classId]
                            );
                            if ($classResult && $classData = $classResult->fetch_assoc()) {
                                $anneeText = $classData['annee'] == 1 ? '1ère' : ($classData['annee'] == 2 ? '2ème' : '3ème');
                                $classesNames[] = $classData['salleName'] . ' (' . $anneeText . ' - ' . $classData['specialisation'] . ')';
                            }
                        }
                        
                        Security::logSecurityEvent('TEACHER_CREATED', "Teacher: $firstName $lastName (ID: $teacherId)", 'INFO');
                        $statusMsg = "<div class='alert alert-success'>
                                        <strong>✓ Enseignant ajouté avec succès !</strong><br>
                                        👤 Nom : " . Security::escapeHTML($firstName . ' ' . $lastName) . "<br>
                                        📧 Email : " . Security::escapeHTML($emailAddress) . "<br>
                                        🔑 Mot de passe : <code>" . Security::escapeHTML($defaultPassword) . "</code><br>
                                        📚 Classes : " . Security::escapeHTML(implode(', ', $classesNames)) . "<br>
                                        <hr>
                                        <strong>⚠️ Important :</strong> L'enseignant peut maintenant se connecter avec cet email et ce mot de passe.
                                      </div>";
                    } else {
                        $statusMsg = "<div class='alert alert-danger'>Erreur lors de la création du compte utilisateur.</div>";
                    }
                } else {
                    $statusMsg = "<div class='alert alert-danger'>Erreur lors de la création.</div>";
                }
            }
        }
    }
}

// UPDATE
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $Id = (int)$_GET['Id'];
    if ($Id > 0) {
        $editArr = $db->select('tblteachers', ['Id' => $Id]);
        $editData = !empty($editArr) ? $editArr[0] : null;
        $teacherClassesList = getTeacherClasses($db, $Id);
        foreach($teacherClassesList as $tc){
            $teacherClassIds[] = $tc['classId'];
        }
    }
    
    if(isset($_POST['update'])){
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('CSRF_FAILED', 'createClassTeacher.php update', 'WARNING');
            $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
        } else {
            $firstName = Security::validateString($_POST['firstName'] ?? '', 1, 100);
            $lastName = Security::validateString($_POST['lastName'] ?? '', 1, 100);
            $emailAddress = Security::validateEmail($_POST['emailAddress'] ?? '');
            $phoneNo = Security::validateString($_POST['phoneNo'] ?? '', 1, 20);
            $classes = $_POST['classes'] ?? [];
            
            if (!$firstName || !$lastName || !$emailAddress || !$phoneNo) {
                $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis.</div>";
            } else {
                // Récupérer l'ancien email avant mise à jour
                $oldData = $db->select('tblteachers', ['Id' => $Id], 'emailAddress');
                $oldEmail = !empty($oldData) ? $oldData[0]['emailAddress'] : '';
                
                $updated = $db->update('tblteachers', [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'emailAddress' => $emailAddress,
                    'phoneNo' => $phoneNo
                ], ['Id' => $Id]);

                if ($updated) {
                    // Mettre à jour aussi dans tblusers
                    if ($oldEmail) {
                        $db->execute(
                            "UPDATE tblusers SET firstName=?, lastName=?, emailAddress=?, phoneNo=? WHERE emailAddress=? AND role='ClassTeacher'",
                            'sssss', [$firstName, $lastName, $emailAddress, $phoneNo, $oldEmail]
                        );
                    }
                    
                    deleteTeacherClasses($db, $Id);
                    foreach($classes as $classId){
                        $classId = (int)$classId;
                        if ($classId > 0) {
                            $db->insert('tblteacherclass', ['teacherId' => $Id, 'classId' => $classId]);
                        }
                    }
                    
                    Security::logSecurityEvent('TEACHER_UPDATED', "Teacher ID: $Id", 'INFO');
                    header('Location: createClassTeacher.php');
                    exit();
                } else {
                    $statusMsg = "<div class='alert alert-danger'>Erreur lors de la mise à jour.</div>";
                }
            }
        }
    }
}

// DELETE
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClassTeacher.php delete', 'WARNING');
    } else {
        $Id = (int)$_GET['Id'];
        if ($Id > 0) {
            $teacherData = $db->select('tblteachers', ['Id' => $Id], 'emailAddress');
            $teacherEmail = !empty($teacherData) ? $teacherData[0]['emailAddress'] : '';
            
            deleteTeacherClasses($db, $Id);
            $db->delete('tblteachers', ['Id' => $Id]);
            if ($teacherEmail) {
                $db->execute(
                    "DELETE FROM tblusers WHERE emailAddress = ? AND role = 'ClassTeacher'",
                    's', [$teacherEmail]
                );
            }
            Security::logSecurityEvent('TEACHER_DELETED', "Teacher ID: $Id", 'INFO');
        }
    }
    header('Location: createClassTeacher.php');
    exit();
}

// RESET PASSWORD
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "reset") {
    if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClassTeacher.php reset', 'WARNING');
    } else {
        $Id = (int)$_GET['Id'];
        if ($Id > 0) {
            $teacherData = $db->select('tblteachers', ['Id' => $Id], 'firstName, lastName, emailAddress');
            if (!empty($teacherData)) {
                $teacher = $teacherData[0];
                $newPassword = generateDefaultPassword($teacher['firstName'], $teacher['lastName']);
                $hashedPassword = Security::hashPassword($newPassword);
                
                $db->update('tblteachers', ['password' => $hashedPassword], ['Id' => $Id]);
                $db->execute(
                    "UPDATE tblusers SET password = ? WHERE emailAddress = ? AND role = 'ClassTeacher'",
                    'ss', [$hashedPassword, $teacher['emailAddress']]
                );
                
                Security::logSecurityEvent('TEACHER_PASSWORD_RESET', "Teacher ID: $Id", 'INFO');
                $statusMsg = "<div class='alert alert-info'>Mot de passe réinitialisé : <code>" . Security::escapeHTML($newPassword) . "</code></div>";
            }
        }
    }
}

// Récupérer les données
$teachersResult = $db->execute("SELECT * FROM tblteachers ORDER BY dateCreated DESC");
$teachers = [];
if ($teachersResult) {
    while ($row = $teachersResult->fetch_assoc()) {
        $teachers[] = $row;
    }
}
$classesList = getAllClasses($db);

$csrfToken = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <?php include 'includes/title.php';?>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .teacher-card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .badge-class { background: #1a73e8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; margin: 2px; display: inline-block; }
        .badge-specialisation { background: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .classes-list { max-height: 250px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; }
        .class-checkbox { margin-right: 8px; }
        .class-item { padding: 8px 5px; border-bottom: 1px solid #f0f0f0; }
        .class-item:last-child { border-bottom: none; }
        .class-item:hover { background-color: #f8f9fa; cursor: pointer; }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include "Includes/sidebar.php";?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include "Includes/topbar.php";?>

                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chalkboard-teacher"></i> Gestion des Enseignants
                        </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Accueil</a></li>
                            <li class="breadcrumb-item active">Enseignants</li>
                        </ol>
                    </div>

                    <?php if($statusMsg != ''): ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <?php echo $statusMsg; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Formulaire -->
                        <div class="col-lg-5">
                            <div class="card teacher-card mb-4">
                                <div class="card-header py-3 bg-white">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <?php echo isset($editData) ? '✏️ Modifier un enseignant' : '➕ Ajouter un enseignant'; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::escapeHTML($csrfToken); ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Prénom <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="firstName" 
                                                       value="<?php echo isset($editData) ? Security::escapeHTML($editData['firstName']) : ''; ?>" 
                                                       required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Nom <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="lastName" 
                                                       value="<?php echo isset($editData) ? Security::escapeHTML($editData['lastName']) : ''; ?>" 
                                                       required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="emailAddress" 
                                                   value="<?php echo isset($editData) ? Security::escapeHTML($editData['emailAddress']) : ''; ?>" 
                                                   required>
                                        </div>

                                        <div class="mb-3">
                                            <label>Téléphone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" name="phoneNo" 
                                                   value="<?php echo isset($editData) ? Security::escapeHTML($editData['phoneNo']) : ''; ?>" 
                                                   placeholder="+237 6XX XXX XXX" required>
                                        </div>

                                        <div class="mb-3">
                                            <label>📚 Classes enseignées <span class="text-danger">*</span></label>
                                            <div class="classes-list">
                                                <?php if(!empty($classesList)): ?>
                                                    <?php foreach($classesList as $class): 
                                                        $anneeText = $class['annee'] == 1 ? '1ère Année' : ($class['annee'] == 2 ? '2ème Année' : '3ème Année');
                                                        $classDisplay = $class['salleName'] . ' - ' . $anneeText;
                                                        $checked = (in_array($class['Id'], $teacherClassIds)) ? 'checked' : '';
                                                    ?>
                                                        <div class="class-item">
                                                            <label style="cursor: pointer; width: 100%;">
                                                                <input type="checkbox" name="classes[]" value="<?php echo (int)$class['Id']; ?>" <?php echo $checked; ?> class="class-checkbox">
                                                                <strong><?php echo Security::escapeHTML($classDisplay); ?></strong>
                                                                <span class="badge-specialisation"><?php echo Security::escapeHTML($class['specialisation']); ?></span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="alert alert-warning mb-0">
                                                        ⚠️ Aucune classe disponible. Veuillez d'abord <a href="createClass.php">créer des classes</a>.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                Un enseignant peut enseigner dans plusieurs classes
                                            </small>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <?php if (isset($editData)): ?>
                                                <button type="submit" name="update" class="btn btn-warning btn-block">
                                                    <i class="fas fa-save"></i> Mettre à jour
                                                </button>
                                                <a href="createClassTeacher.php" class="btn btn-secondary btn-block mt-2">
                                                    <i class="fas fa-times"></i> Annuler
                                                </a>
                                            <?php else: ?>
                                                <button type="submit" name="save" class="btn btn-primary btn-block">
                                                    <i class="fas fa-user-plus"></i> Enregistrer l'enseignant
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des enseignants -->
                        <div class="col-lg-7">
                            <div class="card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-list"></i> Liste des enseignants
                                    </h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Enseignant</th>
                                                <th>Contact</th>
                                                <th>Classes enseignées</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(!empty($teachers)): ?>
                                                <?php foreach($teachers as $row): 
                                                    $teacherClassesList = getTeacherClasses($db, $row['Id']);
                                                    $classesArray = [];
                                                    foreach($teacherClassesList as $tc){
                                                        $anneeText = $tc['annee'] == 1 ? '1ère' : ($tc['annee'] == 2 ? '2ème' : '3ème');
                                                        $display = $tc['salleName'] . ' - ' . $anneeText . ' (' . $tc['specialisation'] . ')';
                                                        $classesArray[] = $display;
                                                    }
                                                    $classesText = !empty($classesArray) ? implode('<br>', array_map([Security::class, 'escapeHTML'], $classesArray)) : 'Aucune classe';
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo Security::escapeHTML($row['firstName'] . ' ' . $row['lastName']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php echo Security::escapeHTML($row['emailAddress']); ?><br>
                                                            <small class="text-muted"><?php echo Security::escapeHTML($row['phoneNo']); ?></small>
                                                        </td>
                                                        <td>
                                                            <small><?php echo $classesText; ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="?action=edit&Id=<?php echo (int)$row['Id']; ?>" class="btn btn-sm btn-info" title="Modifier">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="?action=reset&Id=<?php echo (int)$row['Id']; ?>&csrf_token=<?php echo Security::escapeHTML($csrfToken); ?>" class="btn btn-sm btn-warning" title="Réinitialiser mot de passe"
                                                                   onclick="return confirm('Réinitialiser le mot de passe ?');">
                                                                    <i class="fas fa-key"></i>
                                                                </a>
                                                                <a href="?action=delete&Id=<?php echo (int)$row['Id']; ?>&csrf_token=<?php echo Security::escapeHTML($csrfToken); ?>" class="btn btn-sm btn-danger" title="Supprimer"
                                                                   onclick="return confirm('Supprimer cet enseignant ?');">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">
                                                        <div class="alert alert-info mb-0">Aucun enseignant enregistré</div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                     </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#dataTableHover').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' }
        });
    });
    </script>
</body>
</html>