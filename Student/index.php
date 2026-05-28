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

// Récupérer les informations académiques (tblstudents)
$email = $student['emailAddress'];
$studentDetails = null;
$className = "Non assigné";
$classArmName = "Non assigné";
$formationName = "Aucune formation";
$admissionNo = "";

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
  $admissionNo = $studentDetails['admissionNumber'];
}

// Récupérer les statistiques de présence si l'étudiant est assigné
$totalClasses = 0;
$presentClasses = 0;
$absentClasses = 0;
$attendanceRate = 100;

if (!empty($admissionNo)) {
  $totalClasses = $db->count('tblattendance', ['admissionNo' => $admissionNo]);
  $presentClasses = $db->count('tblattendance', ['admissionNo' => $admissionNo, 'status' => '1']);
  $absentClasses = $db->count('tblattendance', ['admissionNo' => $admissionNo, 'status' => '0']);
  
  if ($totalClasses > 0) {
    $attendanceRate = round(($presentClasses / $totalClasses) * 100);
  }
}

// Session active
$activeSession = "Aucune session active";
$sessionQuery = $db->select('tblsessionterm', ['isActive' => '1']);
if (!empty($sessionQuery)) {
  $activeSession = $sessionQuery[0]['sessionName'];
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Portail Étudiant - Dashboard</title>
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
      <li class="nav-item active">
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Espace Étudiant</h1>
          </div>

          <!-- Welcome Banner -->
          <div class="row mb-4">
            <div class="col-lg-12">
              <div class="premium-card bg-gradient-light" style="background: linear-gradient(135deg, #ffffff, #f0fdfa); border-left: 5px solid var(--color-success);">
                <div class="card-body p-4">
                  <h4 class="font-weight-bold text-success">Ravi de vous revoir, <?php echo Security::escapeHTML($student['firstName']); ?> ! 👋</h4>
                  <p class="text-muted mb-0">Bienvenue sur votre portail académique. Suivez vos présences, effectuez vos demandes d'absence et accédez à vos attestations de formation en temps réel.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Academic & Inscription Info -->
          <div class="row mb-4">
            <div class="col-lg-12">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-university text-primary mr-2"></i>Détails de mon Inscription</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3 mb-3 mb-md-0 border-right">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Formation suivie</span>
                      <span class="h6 font-weight-bold text-gray-800"><?php echo Security::escapeHTML($formationName); ?></span>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0 border-right">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Classe académique</span>
                      <span class="h6 font-weight-bold text-gray-800"><?php echo Security::escapeHTML($className); ?></span>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0 border-right">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Cohorte (Class Arm)</span>
                      <span class="h6 font-weight-bold text-gray-800"><?php echo Security::escapeHTML($classArmName); ?></span>
                    </div>
                    <div class="col-md-3">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Session active</span>
                      <span class="h6 font-weight-bold text-success"><i class="fas fa-check-circle mr-1"></i><?php echo Security::escapeHTML($activeSession); ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Row -->
          <div class="row mb-4">
            <!-- Rate Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="premium-card stat-card-green h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Taux d'assiduité</div>
                      <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $attendanceRate; ?> %</div>
                      <div class="premium-progress-container">
                        <div class="premium-progress-bar bg-teal" style="width: <?php echo $attendanceRate; ?>%; background-color: var(--color-success);"></div>
                      </div>
                    </div>
                    <div class="col-auto">
                      <div class="stat-icon stat-icon-green">
                        <i class="fas fa-chart-line"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Present Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="premium-card stat-card-blue h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Cours Présent</div>
                      <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $presentClasses; ?></div>
                      <span class="text-xs text-success font-weight-bold">Présence validée</span>
                    </div>
                    <div class="col-auto">
                      <div class="stat-icon stat-icon-blue">
                        <i class="fas fa-calendar-check"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Absent Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="premium-card stat-card-warning h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Absences signalées</div>
                      <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $absentClasses; ?></div>
                      <span class="text-xs text-danger font-weight-bold">À justifier</span>
                    </div>
                    <div class="col-auto">
                      <div class="stat-icon stat-icon-warning">
                        <i class="fas fa-calendar-times"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="premium-card stat-card-indigo h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total de Sessions</div>
                      <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $totalClasses; ?></div>
                      <span class="text-xs text-indigo font-weight-bold">Cours enregistrés</span>
                    </div>
                    <div class="col-auto">
                      <div class="stat-icon stat-icon-indigo">
                        <i class="fas fa-clock"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions & Tools -->
          <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-6 mb-4">
              <div class="premium-card h-100">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-bolt text-warning mr-2"></i>Actions Rapides</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-sm-6 mb-3">
                      <a href="viewAttendance.php" class="btn btn-block btn-premium-primary text-center py-3">
                        <i class="fas fa-eye fa-2x mb-2 d-block"></i>
                        Mes Présences
                      </a>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <a href="requestAbsence.php" class="btn btn-block btn-premium-danger text-center py-3">
                        <i class="fas fa-file-medical fa-2x mb-2 d-block"></i>
                        Déclarer une Absence
                      </a>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <a href="viewCertificates.php" class="btn btn-block btn-premium-success text-center py-3">
                        <i class="fas fa-award fa-2x mb-2 d-block"></i>
                        Mes Attestations
                      </a>
                    </div>
                    <div class="col-sm-6 mb-3">
                      <a href="profile.php" class="btn btn-block btn-outline-secondary text-center py-3 font-weight-bold" style="border-radius: var(--border-radius-sm);">
                        <i class="fas fa-user-circle fa-2x mb-2 d-block text-muted"></i>
                        Mon Profil
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- School Rules / Info Card -->
            <div class="col-lg-6 mb-4">
              <div class="premium-card h-100">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-info-circle text-info mr-2"></i>Notes d'information</h6>
                </div>
                <div class="card-body">
                  <h6 class="font-weight-bold text-indigo">📝 Rappel de l'Assiduité scolaire</h6>
                  <p class="text-sm text-muted">Afin de valider votre cursus et d'obtenir votre attestation de formation finale, un taux de présence minimal de **80 %** est fortement conseillé. Veillez à émarger à chaque début de séance.</p>
                  
                  <h6 class="font-weight-bold text-teal mt-4">📁 Justification des Absences</h6>
                  <p class="text-sm text-muted mb-0">Toute absence doit être justifiée dans les **48 heures** suivant la date de l'absence. Vous pouvez désormais téléverser votre justificatif au format PDF ou Image directement dans l'onglet **Demander une Absence**.</p>
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