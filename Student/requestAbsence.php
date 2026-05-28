<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/Security.php';
include '../Includes/DatabaseOperations.php';

// Ajouter les headers de sécurité
Security::setSecurityHeaders();
$db = new DatabaseOperations($conn);

// Vérifier que l'utilisateur est bien un Student
if ($_SESSION['userType'] !== 'Student') {
  header('Location: ../index.php');
  exit;
}

// Récupérer les informations de l'étudiant
$studentUser = $db->select('tblusers', ['Id' => $_SESSION['userId'], 'role' => 'Student']);
if (empty($studentUser)) {
  header('Location: ../index.php');
  exit;
}
$student = $studentUser[0];

// Récupérer le numéro d'admission
$email = $student['emailAddress'];
$admissionNo = "";
$resStudent = $db->select('tblstudents', ['emailAddress' => $email]);
if (!empty($resStudent)) {
  $admissionNo = $resStudent[0]['admissionNumber'];
}

$statusMsg = "";

// Traiter la soumission de la demande d'absence
if (isset($_POST['submit_absence'])) {
  // 1. Valider le token CSRF
  if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    Security::logSecurityEvent('CSRF_FAILED', 'Demande d\'absence invalide ou token expiré', 'WARNING');
    $statusMsg = "<div class='alert alert-danger'>Requête invalide (CSRF). Veuillez réessayer.</div>";
  } else {
    $reason = Security::validateString($_POST['reason'] ?? '', 5, 1000);
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Validation des dates
    $dStart = DateTime::createFromFormat('Y-m-d', $startDate);
    $dEnd = DateTime::createFromFormat('Y-m-d', $endDate);

    if (!$reason) {
      $statusMsg = "<div class='alert alert-danger'>Veuillez saisir un motif valide (au moins 5 caractères).</div>";
    } elseif (!$dStart || !$dEnd) {
      $statusMsg = "<div class='alert alert-danger'>Dates invalides.</div>";
    } elseif ($startDate > $endDate) {
      $statusMsg = "<div class='alert alert-danger'>La date de début doit être antérieure ou égale à la date de fin.</div>";
    } elseif (empty($admissionNo)) {
      $statusMsg = "<div class='alert alert-danger'>Vous devez être inscrit dans une classe pour demander une absence.</div>";
    } else {
      // Gérer l'upload du justificatif
      $fileName = null;
      $uploadOk = true;

      if (isset($_FILES['justification']) && $_FILES['justification']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['justification'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
          $statusMsg = "<div class='alert alert-danger'>Erreur lors du téléversement du fichier.</div>";
          $uploadOk = false;
        } else {
          // Valider l'extension
          $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
          $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

          if (!in_array($ext, $allowedExtensions)) {
            $statusMsg = "<div class='alert alert-danger'>Seuls les fichiers PDF, PNG et JPG sont autorisés.</div>";
            $uploadOk = false;
          } // Valider la taille (max 2 Mo)
          elseif ($file['size'] > 2097152) {
            $statusMsg = "<div class='alert alert-danger'>Le fichier ne doit pas dépasser 2 Mo.</div>";
            $uploadOk = false;
          } else {
            // Générer un nom de fichier unique et sécurisé
            $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $uploadDir = '../uploads/justifications/';

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
              $fileName = $newFileName;
            } else {
              $statusMsg = "<div class='alert alert-danger'>Erreur lors de l'enregistrement du justificatif sur le serveur.</div>";
              $uploadOk = false;
            }
          }
        }
      }

      if ($uploadOk) {
        // Insérer dans la base de données
        $insertData = [
          'studentAdmissionNo' => $admissionNo,
          'reason' => $reason,
          'startDate' => $startDate,
          'endDate' => $endDate,
          'justificationFile' => $fileName,
          'status' => 'En attente'
        ];

        $inserted = $db->insert('tblabsencerequests', $insertData);
        if ($inserted) {
          $statusMsg = "<div class='alert alert-success'>Votre demande d'absence a été soumise avec succès.</div>";
          Security::logSecurityEvent('ABSENCE_REQUEST', "L'étudiant $email a soumis une absence (ID: $inserted)", 'INFO');
        } else {
          $statusMsg = "<div class='alert alert-danger'>Une erreur est survenue lors de l'enregistrement en base de données.</div>";
        }
      }
    }
  }
}

// Récupérer l'historique des demandes
$absencesList = [];
if (!empty($admissionNo)) {
  $absencesList = $db->select('tblabsencerequests', ['studentAdmissionNo' => $admissionNo], '*', 'dateCreated DESC');
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Portail Étudiant - Demander une Absence</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <link href="css/premium.css" rel="stylesheet">
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
          <img src="../images1.png" alt="logo" style="max-height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">Secel Student</div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item">
        <a class="nav-link" href="index.php">
          <i class="fas fa-fw fa-columns"></i>
          <span>Tableau de bord</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Assiduité</div>
      <li class="nav-item">
        <a class="nav-link" href="viewAttendance.php">
          <i class="fas fa-fw fa-calendar-check"></i>
          <span>Mes Présences</span></a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="requestAbsence.php">
          <i class="fas fa-fw fa-file-medical"></i>
          <span>Demander une Absence</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Récompenses & Profil</div>
      <li class="nav-item">
        <a class="nav-link" href="viewCertificates.php">
          <i class="fas fa-fw fa-award"></i>
          <span>Mes Attestations</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="profile.php">
          <i class="fas fa-fw fa-user-cog"></i>
          <span>Mon Profil</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>
    </ul>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <nav class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
          <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>
          <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="d-flex align-items-center">
                  <div class="text-right mr-3 d-none d-lg-block">
                    <span class="d-block text-gray-800 font-weight-bold"><?php echo Security::escapeHTML($student['firstName'] . ' ' . $student['lastName']); ?></span>
                    <span class="text-xs text-muted" style="text-transform: uppercase;">Étudiant</span>
                  </div>
                  <img class="img-profile rounded-circle border border-primary" src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['firstName'] . '+' . $student['lastName']); ?>&background=0d9488&color=fff" style="width: 40px; height: 40px;">
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile.php">
                  <i class="fas fa-user fa-fw mr-2 text-gray-400"></i>
                  Mon Profil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                  <i class="fas fa-sign-out-alt fa-fw mr-2 text-danger"></i>
                  Déconnexion
                </a>
              </div>
            </li>
          </ul>
        </nav>
        <!-- Topbar -->
        
        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Demander une Absence</h1>
          </div>

          <div class="row">
            <!-- Form Card -->
            <div class="col-lg-5 mb-4">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-edit text-primary mr-2"></i>Déclarer une absence</h6>
                </div>
                <div class="card-body">
                  <?php echo $statusMsg; ?>
                  <form method="POST" action="requestAbsence.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                      <label class="font-weight-bold text-gray-800">Date de début <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" name="startDate" required style="border-radius: var(--border-radius-sm);">
                    </div>

                    <div class="form-group">
                      <label class="font-weight-bold text-gray-800">Date de fin <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" name="endDate" required style="border-radius: var(--border-radius-sm);">
                    </div>

                    <div class="form-group">
                      <label class="font-weight-bold text-gray-800">Motif de l'absence <span class="text-danger">*</span></label>
                      <textarea class="form-control" name="reason" rows="4" placeholder="Ex: Raisons médicales, urgence familiale..." required style="border-radius: var(--border-radius-sm); resize: none;"></textarea>
                    </div>

                    <div class="form-group">
                      <label class="font-weight-bold text-gray-800">Pièce justificative <span class="text-xs text-muted">(Optionnel - PDF, PNG, JPG - Max 2Mo)</span></label>
                      <input type="file" class="form-control-file" name="justification">
                    </div>

                    <button type="submit" name="submit_absence" class="btn btn-premium-danger btn-block mt-4">
                      <i class="fas fa-paper-plane mr-2"></i>Soumettre ma demande
                    </button>
                  </form>
                </div>
              </div>
            </div>

            <!-- List Card -->
            <div class="col-lg-7 mb-4">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-history text-indigo mr-2"></i>Historique de mes demandes</h6>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table premium-table" width="100%">
                      <thead>
                        <tr>
                          <th>Durée</th>
                          <th>Motif</th>
                          <th>Statut</th>
                          <th>Justificatif</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($absencesList)): ?>
                          <?php foreach ($absencesList as $abs): ?>
                            <?php
                              $badgeClass = "premium-badge-warning";
                              if ($abs['status'] === 'Accepté') $badgeClass = "premium-badge-success";
                              if ($abs['status'] === 'Refusé') $badgeClass = "premium-badge-danger";
                            ?>
                            <tr>
                              <td>
                                <span class="d-block font-weight-bold text-gray-800 text-sm">
                                  Du <?php echo Security::escapeHTML(date("d/m/Y", strtotime($abs['startDate']))); ?>
                                </span>
                                <span class="text-xs text-muted">
                                  Au <?php echo Security::escapeHTML(date("d/m/Y", strtotime($abs['endDate']))); ?>
                                </span>
                              </td>
                              <td class="text-sm" style="max-width: 200px; white-space: normal;">
                                <?php echo Security::escapeHTML($abs['reason']); ?>
                              </td>
                              <td>
                                <span class="premium-badge <?php echo $badgeClass; ?>">
                                  <?php echo Security::escapeHTML($abs['status']); ?>
                                </span>
                              </td>
                              <td>
                                <?php if (!empty($abs['justificationFile'])): ?>
                                  <a href="../uploads/justifications/<?php echo Security::escapeHTML($abs['justificationFile']); ?>" target="_blank" class="btn btn-sm btn-outline-info" style="border-radius: var(--border-radius-sm);">
                                    <i class="fas fa-file-download mr-1"></i> Voir
                                  </a>
                                <?php else: ?>
                                  <span class="text-xs text-muted">Aucun</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Aucune demande d'absence enregistrée.</td>
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
        <!--- Container Fluid-->
      </div>

    </div>
  </div>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content" style="border-radius: var(--border-radius-md);">
        <div class="modal-header">
          <h5 class="modal-title font-weight-bold" id="exampleModalLabel">Prêt à partir ?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Sélectionnez "Déconnexion" ci-dessous si vous souhaitez fermer votre session actuelle.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary font-weight-bold" type="button" data-dismiss="modal" style="border-radius: var(--border-radius-sm);">Annuler</button>
          <a class="btn btn-danger font-weight-bold" href="logout.php" style="border-radius: var(--border-radius-sm);">Déconnexion</a>
        </div>
      </div>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

</body>

</html>
