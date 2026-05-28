<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/Security.php';
include '../Includes/DatabaseOperations.php';

Security::setSecurityHeaders();
$db = new DatabaseOperations($conn);
$statusMsg = '';
$editData = null;

// ==============================
// FONCTION : Génère le prochain numéro d'admission
// Format : STE-AAAA-XXX
// ==============================
function generateAdmissionNumber($conn) {
    $year = date('Y');
    $prefix = "STE-{$year}-";

    // Chercher le dernier numéro de l'année en cours
    $stmt = $conn->prepare(
        "SELECT admissionNumber FROM tblstudents 
         WHERE admissionNumber LIKE ? 
         ORDER BY admissionNumber DESC 
         LIMIT 1"
    );
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($row = $result->fetch_assoc()) {
        // Extraire le numéro séquentiel et incrémenter
        $lastNumber = (int) substr($row['admissionNumber'], strlen($prefix));
        $next = $lastNumber + 1;
    } else {
        // Première inscription de l'année
        $next = 1;
    }

    // Formater avec zéros initiaux sur 3 chiffres (001, 002, ... 999)
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// ==============================
// AJAX : retourne la spécialisation et classId pour un domaine+niveau
// ==============================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'getClass') {
    header('Content-Type: application/json');
    if (ob_get_length()) {
        ob_clean();
    }
    $salleId = intval($_GET['salleId']);
    $annee   = intval($_GET['annee']);
    $result  = mysqli_query($conn, "SELECT Id, specialisation FROM tblclass 
                                    WHERE salleId='$salleId' AND annee='$annee' LIMIT 1");
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['classId' => $row['Id'], 'specialisation' => $row['specialisation']]);
    } else {
        echo json_encode(['classId' => null, 'specialisation' => null]);
    }
    exit;
}

// ==============================
// CREATE
// ==============================
if (isset($_POST['save'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createStudents.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide. Veuillez réessayer.</div>";
    } else {
        $firstName       = Security::validateString($_POST['firstName'] ?? '', 1, 100);
        $lastName        = Security::validateString($_POST['lastName'] ?? '', 1, 100);
        $admissionNumber = Security::validateString($_POST['admissionNumber'] ?? '', 1, 50);
        $classId         = isset($_POST['classId']) ? (int)$_POST['classId'] : 0;
        $emailAddress    = trim($_POST['emailAddress'] ?? '');
        $phoneNo         = trim($_POST['phoneNo'] ?? '');
        $gender          = in_array($_POST['gender'] ?? '', ['M', 'F']) ? $_POST['gender'] : null;

        if (!$firstName || !$lastName || !$admissionNumber || !$classId) {
            $details = [];
            if (!$firstName) $details[] = "Prénom";
            if (!$lastName) $details[] = "Nom";
            if (!$admissionNumber) $details[] = "Numéro d'admission";
            if (!$classId) $details[] = "Classe/Spécialisation non détectée (choisissez Domaine + Niveau)";
            $statusMsg = "<div class='alert alert-danger'>Les champs obligatoires sont manquants : " . implode(', ', $details) . ".</div>";
        } else {
            // Vérification anti-doublon (sécurité côté serveur, même si auto-généré)
            $existing = $db->select('tblstudents', ['admissionNumber' => $admissionNumber]);
            if (!empty($existing)) {
                // Collision rare mais possible (ex: deux admins simultanés) → regénérer
                $admissionNumber = generateAdmissionNumber($conn);
            }

            $defaultPassword = Security::hashPassword('12345');
            $insertData = [
                'firstName'       => $firstName,
                'lastName'        => $lastName,
                'admissionNumber' => $admissionNumber,
                'password'        => $defaultPassword,
                'classId'         => $classId,
            ];
            if ($emailAddress) $insertData['emailAddress'] = $emailAddress;
            if ($phoneNo)      $insertData['phoneNo']      = $phoneNo;
            if ($gender)       $insertData['gender']       = $gender;

            $insert_id = $db->insert('tblstudents', $insertData);
            if ($insert_id) {
                Security::logSecurityEvent('STUDENT_CREATED', 'Student ID: ' . $insert_id . ' | Admission: ' . $admissionNumber, 'INFO');
                $statusMsg = "<div class='alert alert-success'>
                    <i class='fas fa-check-circle mr-2'></i>
                    Étudiant enregistré avec succès !
                    Numéro d'admission : <strong>{$admissionNumber}</strong> —
                    Mot de passe par défaut : <strong>12345</strong>
                </div>";
            } else {
                $dbError = $db->getError();
                $statusMsg = "<div class='alert alert-danger'>Erreur de base de données lors de la création : <strong>{$dbError}</strong>. Veuillez réessayer.</div>";
            }
        }
    }
}

// ==============================
// EDIT (chargement)
// ==============================
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
    $editId = (int)$_GET['Id'];
    $q = mysqli_query($conn, "SELECT s.*, c.salleId, c.annee, c.specialisation 
                               FROM tblstudents s 
                               LEFT JOIN tblclass c ON c.Id = s.classId 
                               WHERE s.Id = '$editId' LIMIT 1");
    $editData = mysqli_fetch_assoc($q);

    // UPDATE
    if (isset($_POST['update'])) {
        $firstName    = Security::validateString($_POST['firstName'] ?? '', 1, 100);
        $lastName     = Security::validateString($_POST['lastName'] ?? '', 1, 100);
        $classId      = isset($_POST['classId']) ? (int)$_POST['classId'] : 0;
        $emailAddress = trim($_POST['emailAddress'] ?? '');
        $phoneNo      = trim($_POST['phoneNo'] ?? '');
        $gender       = in_array($_POST['gender'] ?? '', ['M', 'F']) ? $_POST['gender'] : null;
        // Note : admissionNumber n'est PAS modifiable en édition

        if (!$firstName || !$lastName || !$classId) {
            $statusMsg = "<div class='alert alert-danger'>Champs obligatoires manquants.</div>";
        } else {
            $updateData = [
                'firstName'    => $firstName,
                'lastName'     => $lastName,
                'classId'      => $classId,
                'emailAddress' => $emailAddress,
                'phoneNo'      => $phoneNo,
            ];
            if ($gender) $updateData['gender'] = $gender;

            $upd = $db->update('tblstudents', $updateData, ['Id' => $editId]);
            if ($upd !== false) {
                echo "<script>window.location='createStudents.php';</script>";
                exit;
            } else {
                $statusMsg = "<div class='alert alert-danger'>Erreur lors de la mise à jour.</div>";
            }
        }
    }
}

// ==============================
// DELETE
// ==============================
if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int)$_GET['Id'];
    mysqli_query($conn, "DELETE FROM tblstudents WHERE Id='$delId'");
    echo "<script>window.location='createStudents.php';</script>";
    exit;
}

// Pré-générer le numéro pour l'afficher dans le formulaire (mode création seulement)
$nextAdmissionNumber = (!$editData) ? generateAdmissionNumber($conn) : '';

// Charger les domaines pour le select
$sallesQuery = mysqli_query($conn, "SELECT * FROM tblsalle ORDER BY salleName ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <?php include 'includes/title.php'; ?>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .specialisation-badge {
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }
        .specialisation-badge.found     { background: #28a745; }
        .specialisation-badge.not-found { background: #dc3545; }

        /* Champ numéro d'admission auto-généré */
        .admission-field {
            background: #f8f9fc;
            border: 2px solid #4e73df;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1.05rem;
            color: #2e59d9;
            letter-spacing: 1px;
        }
        .admission-badge {
            background: #e8f0fe;
            border: 1px solid #4e73df;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 1.05rem;
            color: #2e59d9;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include "Includes/sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include "Includes/topbar.php"; ?>
            <div class="container-fluid" id="container-wrapper">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <?php echo $editData ? 'Modifier un Étudiant' : 'Enregistrer un Étudiant'; ?>
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="./">Accueil</a></li>
                        <li class="breadcrumb-item active">Étudiants</li>
                    </ol>
                </div>

                <?php echo $statusMsg; ?>

                <!-- FORMULAIRE -->
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card mb-4">
                            <div class="card-header py-3 bg-white">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-user-graduate mr-2"></i>
                                    <?php echo $editData ? 'Modifier les informations' : 'Nouvel étudiant'; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                    <input type="hidden" name="classId" id="classIdHidden" value="<?php echo htmlspecialchars($editData['classId'] ?? ''); ?>">

                                    <!-- ── Numéro d'admission ── -->
                                    <div class="form-group mb-4">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-id-badge mr-1 text-primary"></i>
                                            Numéro d'admission
                                        </label>

                                        <?php if ($editData): ?>
                                            <!-- Mode édition : numéro affiché, non modifiable -->
                                            <div class="admission-badge">
                                                <i class="fas fa-lock fa-sm"></i>
                                                <?php echo htmlspecialchars($editData['admissionNumber']); ?>
                                            </div>
                                            <input type="hidden" name="admissionNumber" value="<?php echo htmlspecialchars($editData['admissionNumber']); ?>">
                                            <small class="text-muted d-block mt-1">Le numéro d'admission ne peut pas être modifié.</small>

                                        <?php else: ?>
                                            <!-- Mode création : champ auto-généré, lecture seule -->
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-primary text-white border-0">
                                                        <i class="fas fa-magic"></i>
                                                    </span>
                                                </div>
                                                <input type="text"
                                                       class="form-control admission-field"
                                                       name="admissionNumber"
                                                       id="admissionNumberField"
                                                       value="<?php echo htmlspecialchars($nextAdmissionNumber); ?>"
                                                       readonly>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Généré automatiquement — format <strong>STE-AAAA-XXX</strong>
                                            </small>
                                        <?php endif; ?>
                                    </div>

                                    <hr class="my-3">

                                    <!-- ── Identité ── -->
                                    <h6 class="text-primary font-weight-bold mb-3">
                                        <i class="fas fa-user mr-2"></i>Identité
                                    </h6>

                                    <div class="form-group row mb-3">
                                        <div class="col-md-6">
                                            <label>Prénom <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="firstName" required
                                                   value="<?php echo htmlspecialchars($editData['firstName'] ?? ''); ?>"
                                                   placeholder="Ex : Jean">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Nom <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="lastName" required
                                                   value="<?php echo htmlspecialchars($editData['lastName'] ?? ''); ?>"
                                                   placeholder="Ex : Dupont">
                                        </div>
                                    </div>

                                    <div class="form-group row mb-3">
                                        <div class="col-md-6">
                                            <label>Genre</label>
                                            <select class="form-control" name="gender">
                                                <option value="">-- Sélectionner --</option>
                                                <option value="M" <?php echo ($editData['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                                <option value="F" <?php echo ($editData['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Féminin</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Téléphone</label>
                                            <input type="text" class="form-control" name="phoneNo"
                                                   value="<?php echo htmlspecialchars($editData['phoneNo'] ?? ''); ?>"
                                                   placeholder="6XXXXXXXX">
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="emailAddress"
                                               value="<?php echo htmlspecialchars($editData['emailAddress'] ?? ''); ?>"
                                               placeholder="exemple@email.com">
                                    </div>

                                    <hr class="my-3">

                                    <!-- ── Affectation académique ── -->
                                    <h6 class="text-primary font-weight-bold mb-3">
                                        <i class="fas fa-graduation-cap mr-2"></i>Affectation académique
                                    </h6>

                                    <div class="form-group row mb-3">
                                        <div class="col-md-6">
                                            <label>Domaine <span class="text-danger">*</span></label>
                                            <select class="form-control" name="salleId" id="salleSelect" required>
                                                <option value="">-- Sélectionner un domaine --</option>
                                                <?php while ($salle = mysqli_fetch_assoc($sallesQuery)): ?>
                                                    <option value="<?php echo $salle['Id']; ?>"
                                                        <?php echo ($editData['salleId'] ?? '') == $salle['Id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($salle['salleName']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Niveau <span class="text-danger">*</span></label>
                                            <select class="form-control" name="annee" id="anneeSelect" required>
                                                <option value="">-- Sélectionner le niveau --</option>
                                                <option value="1" <?php echo ($editData['annee'] ?? '') == 1 ? 'selected' : ''; ?>>1ère Année</option>
                                                <option value="2" <?php echo ($editData['annee'] ?? '') == 2 ? 'selected' : ''; ?>>2ème Année</option>
                                                <option value="3" <?php echo ($editData['annee'] ?? '') == 3 ? 'selected' : ''; ?>>3ème Année</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Spécialisation auto -->
                                    <div class="form-group mb-4">
                                        <label>Spécialisation <small class="text-muted">(automatique)</small></label>
                                        <div class="p-3 bg-light rounded" id="specialisationDisplay">
                                            <?php if ($editData && $editData['specialisation']): ?>
                                                <span class="specialisation-badge found">
                                                    <?php echo htmlspecialchars($editData['specialisation']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Choisissez un domaine et un niveau</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <?php if ($editData): ?>
                                            <a href="createStudents.php" class="btn btn-secondary mr-2">Annuler</a>
                                            <button type="submit" name="update" class="btn btn-warning">
                                                <i class="fas fa-save mr-1"></i>Mettre à jour
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="save" class="btn btn-primary">
                                                <i class="fas fa-user-plus mr-1"></i>Enregistrer
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LISTE DES ÉTUDIANTS -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-list mr-2"></i>Liste des étudiants
                                </h6>
                            </div>
                            <div class="table-responsive p-3">
                                <table class="table table-hover align-items-center table-flush" id="dataTableHover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>N° Admission</th>
                                            <th>Prénom</th>
                                            <th>Nom</th>
                                            <th>Domaine</th>
                                            <th>Niveau</th>
                                            <th>Spécialisation</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $listQuery = "SELECT s.Id, s.firstName, s.lastName, s.admissionNumber, s.dateCreated,
                                                             c.specialisation, c.annee, sl.salleName
                                                      FROM tblstudents s
                                                      LEFT JOIN tblclass c ON c.Id = s.classId
                                                      LEFT JOIN tblsalle sl ON sl.Id = c.salleId
                                                      ORDER BY s.admissionNumber ASC";
                                        $listResult = mysqli_query($conn, $listQuery);
                                        $sn = 0;
                                        if ($listResult && mysqli_num_rows($listResult) > 0):
                                            while ($row = mysqli_fetch_assoc($listResult)):
                                                $sn++;
                                                $anneeText = ['1' => '1ère Année', '2' => '2ème Année', '3' => '3ème Année'][$row['annee']] ?? '-';
                                        ?>
                                            <tr>
                                                <td><?php echo $sn; ?></td>
                                                <td>
                                                    <code class="text-primary font-weight-bold">
                                                        <?php echo htmlspecialchars($row['admissionNumber']); ?>
                                                    </code>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                                                <td><?php echo htmlspecialchars($row['lastName']); ?></td>
                                                <td><?php echo htmlspecialchars($row['salleName'] ?? '-'); ?></td>
                                                <td><?php echo $anneeText; ?></td>
                                                <td>
                                                    <span class="specialisation-badge found" style="font-size:12px; padding:4px 10px;">
                                                        <?php echo htmlspecialchars($row['specialisation'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($row['dateCreated'])); ?></td>
                                                <td>
                                                    <a href="?action=edit&Id=<?php echo $row['Id']; ?>"
                                                       class="btn btn-sm btn-outline-warning mr-1" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&Id=<?php echo $row['Id']; ?>"
                                                       class="btn btn-sm btn-outline-danger" title="Supprimer"
                                                       onclick="return confirm('Supprimer cet étudiant ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-2x mb-2 d-block text-light"></i>
                                                    Aucun étudiant enregistré
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

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
<script src="../vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTableHover').DataTable();

    // ── Spécialisation auto ──
    function updateSpecialisation() {
        var salleId = $('#salleSelect').val();
        var annee   = $('#anneeSelect').val();
        var display = $('#specialisationDisplay');

        if (!salleId || !annee) {
            display.html('<span class="text-muted">Choisissez un domaine et un niveau</span>');
            $('#classIdHidden').val('');
            return;
        }

        display.html('<span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Chargement...</span>');

        $.getJSON('createStudents.php', { ajax: 'getClass', salleId: salleId, annee: annee }, function(data) {
            if (data.classId && data.specialisation) {
                display.html('<span class="specialisation-badge found">' + data.specialisation + '</span>');
                $('#classIdHidden').val(data.classId);
            } else {
                display.html('<span class="specialisation-badge not-found"><i class="fas fa-exclamation-triangle mr-1"></i>Classe introuvable — créez-la d\'abord</span>');
                $('#classIdHidden').val('');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            display.html('<span class="specialisation-badge not-found"><i class="fas fa-exclamation-triangle mr-1"></i>Erreur AJAX : ' + textStatus + ' (' + errorThrown + ')</span>');
            $('#classIdHidden').val('');
        });
    }

    $('#salleSelect, #anneeSelect').on('change', updateSpecialisation);

    <?php if ($editData && $editData['salleId'] && $editData['annee']): ?>
    updateSpecialisation();
    <?php endif; ?>
});
</script>
</body>
</html>