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

// Récupérer les informations de l'étudiant depuis tblusers
$studentUser = $db->select('tblusers', ['Id' => $_SESSION['userId'], 'role' => 'Student']);
if (empty($studentUser)) {
  header('Location: ../index.php');
  exit;
}
$student = $studentUser[0];

// Récupérer les informations académiques (tblstudents)
$email = $student['emailAddress'];
$className = "Non assigné";
$classArmName = "Non assigné";
$formationName = "Aucune formation";
$admissionNo = "Non assigné";
$otherName = "";

$queryStudent = "SELECT s.*, c.className, ca.classArmName 
                 FROM tblstudents s
                 LEFT JOIN tblclass c ON c.Id = s.classId
                 LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
                 WHERE s.emailAddress = ?";
$resStudent = $db->execute($queryStudent, 's', [$email]);

if ($resStudent && $resStudent->num_rows > 0) {
  $studentDetails = $resStudent->fetch_assoc();
  $className = $studentDetails['className'] ?? "Non assigné";
  $classArmName = $studentDetails['classArmName'] ?? "Non assigné";
  $formationName = $studentDetails['formationName'] ?? "Aucune formation";
  $admissionNo = $studentDetails['admissionNumber'] ?? "Non assigné";
  $otherName = $studentDetails['otherName'] ?? "";
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Portail Étudiant - Mon Profil</title>
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
      <li class="nav-item">
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
      <li class="nav-item active">
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Mon Profil</h1>
          </div>

          <div class="row">
            <!-- Profile Info Card -->
            <div class="col-lg-8 mb-4">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-user-circle text-primary mr-2"></i>Informations Personnelles</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label class="font-weight-bold text-gray-800">Prénom</label>
                      <input type="text" class="form-control bg-light" value="<?php echo Security::escapeHTML($student['firstName']); ?>" readonly style="border-radius: var(--border-radius-sm);">
                    </div>
                    <div class="col-md-6 form-group">
                      <label class="font-weight-bold text-gray-800">Nom</label>
                      <input type="text" class="form-control bg-light" value="<?php echo Security::escapeHTML($student['lastName']); ?>" readonly style="border-radius: var(--border-radius-sm);">
                    </div>
                  </div>

                  <?php if (!empty($otherName)): ?>
                    <div class="form-group">
                      <label class="font-weight-bold text-gray-800">Deuxième prénom / Autre nom</label>
                      <input type="text" class="form-control bg-light" value="<?php echo Security::escapeHTML($otherName); ?>" readonly style="border-radius: var(--border-radius-sm);">
                    </div>
                  <?php endif; ?>

                  <div class="form-group">
                    <label class="font-weight-bold text-gray-800">Adresse Email</label>
                    <input type="email" class="form-control bg-light" value="<?php echo Security::escapeHTML($student['emailAddress']); ?>" readonly style="border-radius: var(--border-radius-sm);">
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label class="font-weight-bold text-gray-800">Téléphone</label>
                      <input type="text" class="form-control bg-light" value="<?php echo Security::escapeHTML($student['phoneNo'] ?? 'Non renseigné'); ?>" readonly style="border-radius: var(--border-radius-sm);">
                    </div>
                    <div class="col-md-6 form-group">
                      <label class="font-weight-bold text-gray-800">Date de création du compte</label>
                      <input type="text" class="form-control bg-light" value="<?php echo Security::escapeHTML(date("d/m/Y H:i", strtotime($student['dateCreated']))); ?>" readonly style="border-radius: var(--border-radius-sm);">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Inscription Details -->
            <div class="col-lg-4 mb-4">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-id-card text-indigo mr-2"></i>Statut Académique</h6>
                </div>
                <div class="card-body text-center">
                  <img class="rounded-circle border border-primary p-1 mb-3" src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['firstName'] . '+' . $student['lastName']); ?>&background=0d9488&color=fff&size=100" style="width: 100px; height: 100px;">
                  
                  <h5 class="font-weight-bold text-gray-900 mb-1"><?php echo Security::escapeHTML($student['firstName'] . ' ' . $student['lastName']); ?></h5>
                  <span class="premium-badge premium-badge-success mb-4">Étudiant Actif</span>

                  <div class="text-left mt-3 border-top pt-3">
                    <div class="mb-2">
                      <span class="text-xs text-muted d-block">Numéro d'Admission</span>
                      <span class="font-weight-bold text-gray-800 text-sm"><?php echo Security::escapeHTML($admissionNo); ?></span>
                    </div>
                    <div class="mb-2">
                      <span class="text-xs text-muted d-block">Formation</span>
                      <span class="font-weight-bold text-gray-800 text-sm"><?php echo Security::escapeHTML($formationName); ?></span>
                    </div>
                    <div class="mb-2">
                      <span class="text-xs text-muted d-block">Classe & Cohorte</span>
                      <span class="font-weight-bold text-gray-800 text-sm"><?php echo Security::escapeHTML($className . ' - ' . $classArmName); ?></span>
                    </div>
                  </div>

                  <a href="logout.php" class="btn btn-premium-danger btn-block mt-4">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                  </a>
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