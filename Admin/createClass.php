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
// VÉRIFICATION DES COLONNES
// ==============================

$checkAnnee = mysqli_query($conn, "SHOW COLUMNS FROM tblclass LIKE 'annee'");
if(mysqli_num_rows($checkAnnee) == 0) {
    mysqli_query($conn, "ALTER TABLE tblclass ADD COLUMN annee INT DEFAULT 1");
}

$checkSpecialisation = mysqli_query($conn, "SHOW COLUMNS FROM tblclass LIKE 'specialisation'");
if(mysqli_num_rows($checkSpecialisation) == 0) {
    mysqli_query($conn, "ALTER TABLE tblclass ADD COLUMN specialisation VARCHAR(100)");
}

$checkClassName = mysqli_query($conn, "SHOW COLUMNS FROM tblclass LIKE 'className'");
if(mysqli_num_rows($checkClassName) == 0) {
    mysqli_query($conn, "ALTER TABLE tblclass ADD COLUMN className VARCHAR(255) AFTER Id");
    mysqli_query($conn, "UPDATE tblclass c JOIN tblsalle s ON c.salleId = s.Id SET c.className = s.salleName");
}


// ==============================
// FONCTION SPÉCIALISATION AVEC LES BONS IDs
// ==============================

function getSpecialisation($salleId, $annee) {
    $salleId = (int)$salleId;
    $annee = (int)$annee;
    // Récupérer dynamiquement depuis tblbranch si disponible
    global $db;
    $branch = $db->select('tblbranch', ['salleId' => $salleId, 'annee' => $annee], 'branchName');
    if (!empty($branch)) {
        return $branch[0]['branchName'];
    }
    return "Spécialisation non définie";
}

// ==============================
// TRAITEMENT
// ==============================

$statusMsg = '';

// CREATE
if(isset($_POST['save'])){
    // Vérification CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClass.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide. Veuillez réessayer.</div>";
    } else {
        $salleId = (int)$_POST['salleId'];
        $annee = (int)$_POST['annee'];
        
        if ($salleId <= 0 || $annee <= 0 || $annee > 3) {
            $statusMsg = "<div class='alert alert-danger'>Données invalides.</div>";
        } else {
            $specialisation = getSpecialisation($salleId, $annee);
            
            // Récupérer le nom du domaine
            $domaineData = $db->select('tblsalle', ['Id' => $salleId], 'salleName');
            $className = !empty($domaineData) ? $domaineData[0]['salleName'] : '';
            
            if (empty($className)) {
                $statusMsg = "<div class='alert alert-danger'>Domaine introuvable.</div>";
            } else {
                // Vérifier si existe déjà
                $existing = $db->execute(
                    "SELECT Id FROM tblclass WHERE salleId = ? AND annee = ?",
                    'ii', [$salleId, $annee]
                );
                
                if ($existing && $existing->num_rows > 0) {
                    $statusMsg = "<div class='alert alert-danger'>Cette classe existe déjà !</div>";
                } else {
                    $insertId = $db->insert('tblclass', [
                        'className' => $className,
                        'salleId' => $salleId,
                        'annee' => $annee,
                        'specialisation' => $specialisation
                    ]);

                    if ($insertId) {
                        Security::logSecurityEvent('CLASS_CREATED', "Class: $className - $specialisation (ID: $insertId)", 'INFO');
                        $statusMsg = "<div class='alert alert-success'>Classe créée : " . Security::escapeHTML($className) . " - " . Security::escapeHTML($specialisation) . "</div>";
                    } else {
                        $statusMsg = "<div class='alert alert-danger'>Erreur lors de la création.</div>";
                    }
                }
            }
        }
    }
}

// DELETE
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    // Vérification CSRF via token GET
    if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClass.php delete', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $Id = (int)$_GET['Id'];
        if ($Id > 0) {
            $db->delete('tblclass', ['Id' => $Id]);
            Security::logSecurityEvent('CLASS_DELETED', "Class ID: $Id", 'INFO');
        }
        header('Location: createClass.php');
        exit();
    }
}

$sallesResult = $db->execute("SELECT * FROM tblsalle ORDER BY salleName ASC");
$salles = [];
if ($sallesResult) {
    while ($row = $sallesResult->fetch_assoc()) {
        $salles[] = $row;
    }
}

// Récupérer les spécialisations depuis tblbranch pour le JavaScript
$branchesResult = $db->execute("SELECT salleId, annee, branchName FROM tblbranch ORDER BY salleId, annee");
$branchesMap = [];
if ($branchesResult) {
    while ($row = $branchesResult->fetch_assoc()) {
        $branchesMap[(int)$row['salleId']][(int)$row['annee']] = $row['branchName'];
    }
}

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
        .specialisation-badge {
            background: #1a73e8;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }
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
                        <h1 class="h3 mb-0 text-gray-800">Gestion des Classes</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Accueil</a></li>
                            <li class="breadcrumb-item active">Classes</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mx-auto">
                            <div class="card">
                                <div class="card-header py-3 bg-white">
                                    <h6 class="m-0 font-weight-bold text-primary">Nouvelle Classe</h6>
                                </div>
                                <div class="card-body">
                                    <?php echo $statusMsg; ?>
                                    
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::escapeHTML($csrfToken); ?>">
                                        <div class="form-group mb-4">
                                            <label>Domaine</label>
                                            <select class="form-control" name="salleId" id="salleSelect" required>
                                                <option value="">Sélectionner un domaine</option>
                                                <?php foreach($salles as $salle): ?>
                                                    <option value="<?php echo (int)$salle['Id']; ?>">
                                                        <?php echo Security::escapeHTML($salle['salleName']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label>Année / Niveau</label>
                                            <select class="form-control" name="annee" id="anneeSelect" required>
                                                <option value="">Sélectionner le niveau</option>
                                                <option value="1">1ère Année</option>
                                                <option value="2">2ème Année</option>
                                                <option value="3">3ème Année</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label>Spécialisation</label>
                                            <div class="p-3 bg-light rounded" id="specialisationDisplay">
                                                <span class="text-muted">Choisissez un domaine et un niveau</span>
                                            </div>
                                            <input type="hidden" name="specialisation" id="specialisation">
                                        </div>
                                        
                                        <button type="submit" name="save" class="btn btn-primary btn-block">
                                            Enregistrer la classe
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des classes -->
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Classes existantes</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Domaine</th>
                                                <th>Niveau</th>
                                                <th>Spécialisation</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $classesResult = $db->execute(
                                                "SELECT tblclass.*, tblsalle.salleName FROM tblclass 
                                                 LEFT JOIN tblsalle ON tblclass.salleId = tblsalle.Id 
                                                 ORDER BY tblclass.salleId, tblclass.annee"
                                            );
                                            $hasClasses = false;
                                            if($classesResult):
                                                while($row = $classesResult->fetch_assoc()):
                                                    $hasClasses = true;
                                                    $anneeText = $row['annee'] . 'ère Année';
                                                    if($row['annee'] == 2) $anneeText = '2ème Année';
                                                    if($row['annee'] == 3) $anneeText = '3ème Année';
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo Security::escapeHTML($row['salleName']); ?></strong></td>
                                                    <td><?php echo Security::escapeHTML($anneeText); ?></td>
                                                    <td>
                                                        <span class="specialisation-badge">
                                                            <?php echo Security::escapeHTML($row['specialisation']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="?action=delete&Id=<?php echo (int)$row['Id']; ?>&csrf_token=<?php echo Security::escapeHTML($csrfToken); ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Supprimer cette classe ?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php 
                                                endwhile;
                                            endif;
                                            if(!$hasClasses):
                                            ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">Aucune classe</td>
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
        $('#dataTableHover').DataTable();
        
        // Spécialisations chargées depuis la base de données
        const specialisations = <?php echo json_encode($branchesMap); ?>;
        
        function updateSpecialisation() {
            var salleId = $('#salleSelect').val();
            var annee = $('#anneeSelect').val();
            var display = $('#specialisationDisplay');
            
            if(salleId && annee && specialisations[salleId] && specialisations[salleId][annee]) {
                var specialisation = specialisations[salleId][annee];
                $('#specialisation').val(specialisation);
                display.html('<span class="specialisation-badge" style="background:#28a745;">' + $('<span>').text(specialisation).html() + '</span>');
            } else if(salleId && annee) {
                display.html('<span class="text-danger">Spécialisation non définie</span>');
            } else {
                display.html('<span class="text-muted">Choisissez un domaine et un niveau</span>');
            }
        }
        
        $('#salleSelect, #anneeSelect').on('change', updateSpecialisation);
    });
    </script>
</body>
</html>