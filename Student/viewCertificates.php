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

// Récupérer son ID d'étudiant académique (tblstudents.Id)
$email = $student['emailAddress'];
$studentId = null;
$studentDetails = $db->select('tblstudents', ['emailAddress' => $email]);
if (!empty($studentDetails)) {
  $studentId = $studentDetails[0]['Id'];
}

$statusMsg = "";
$selectedAttestation = null;

// Vérifier si une attestation est sélectionnée pour affichage/impression
if (isset($_GET['id'])) {
  $attId = intval($_GET['id']);
  
  if ($studentId) {
    // Requête jointe pour obtenir toutes les informations de l'attestation
    $query = "SELECT att.*, s.firstName as sFirst, s.lastName as sLast, s.otherName as sOther,
                     f.name as formationName, c.className, ca.classArmName,
                     t.firstName as tFirst, t.lastName as tLast,
                     sess.sessionName
              FROM tblattestation att
              INNER JOIN tblstudents s ON s.Id = att.studentId
              INNER JOIN tblformation f ON f.id = att.formationId
              INNER JOIN tblclass c ON c.Id = att.classId
              INNER JOIN tblclassarms ca ON ca.Id = att.classArmId
              INNER JOIN tblteachers t ON t.Id = att.teacherId
              INNER JOIN tblsessionterm sess ON sess.Id = att.sessionId
              WHERE att.Id = ? AND att.studentId = ?";
              
    $stmt = $conn->prepare($query);
    if ($stmt) {
      $stmt->bind_param('ii', $attId, $studentId);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res->num_rows > 0) {
        $selectedAttestation = $res->fetch_assoc();
      } else {
        $statusMsg = "<div class='alert alert-danger'>Attestation introuvable ou non autorisée.</div>";
      }
      $stmt->close();
    }
  }
}

// Récupérer toutes les attestations de cet étudiant
$attestationsList = [];
if ($studentId) {
  $queryList = "SELECT att.Id, att.mention, att.dateGenerated, f.name as formationName, sess.sessionName
                FROM tblattestation att
                INNER JOIN tblformation f ON f.id = att.formationId
                INNER JOIN tblsessionterm sess ON sess.Id = att.sessionId
                WHERE att.studentId = ?
                ORDER BY att.dateGenerated DESC";
  
  $stmtList = $conn->prepare($queryList);
  if ($stmtList) {
    $stmtList->bind_param('i', $studentId);
    $stmtList->execute();
    $resList = $stmtList->get_result();
    while ($row = $resList->fetch_assoc()) {
      $attestationsList[] = $row;
    }
    $stmtList->close();
  }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Portail Étudiant - Mes Attestations</title>
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
      <li class="nav-item active">
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
          
          <?php if (!$selectedAttestation): ?>
            <!-- Main List View -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Mes Attestations Scolaires</h1>
            </div>

            <div class="row">
              <div class="col-lg-12">
                <?php echo $statusMsg; ?>
                <div class="premium-card">
                  <div class="premium-card-header">
                    <h6 class="premium-card-title"><i class="fas fa-award text-success mr-2"></i>Mon Coffre-fort Numérique</h6>
                  </div>
                  <div class="card-body">
                    <p class="text-muted mb-4">Retrouvez ci-dessous toutes les attestations officielles de formation qui vous ont été délivrées par la direction de l'établissement.</p>
                    
                    <div class="table-responsive">
                      <table class="table premium-table" width="100%">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Formation</th>
                            <th>Session</th>
                            <th>Mention</th>
                            <th>Date d'émission</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (!empty($attestationsList)): ?>
                            <?php $sn = 0; foreach ($attestationsList as $att): $sn++; ?>
                              <tr>
                                <td><?php echo $sn; ?></td>
                                <td class="font-weight-bold text-gray-800"><?php echo Security::escapeHTML($att['formationName']); ?></td>
                                <td><?php echo Security::escapeHTML($att['sessionName']); ?></td>
                                <td>
                                  <span class="premium-badge premium-badge-success">
                                    <?php echo Security::escapeHTML($att['mention']); ?>
                                  </span>
                                </td>
                                <td><?php echo Security::escapeHTML(date("d/m/Y", strtotime($att['dateGenerated']))); ?></td>
                                <td>
                                  <a href="?id=<?php echo $att['Id']; ?>" class="btn btn-premium-primary btn-sm">
                                    <i class="fas fa-eye mr-1"></i> Visualiser & Imprimer
                                  </a>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr>
                              <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-certificate fa-3x d-block mb-3 text-gray-300"></i>
                                Aucune attestation n'a encore été délivrée par la direction pour votre compte.
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

          <?php else: ?>
            <!-- Single Certificate View & Print Layout -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4 d-print-none">
              <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Attestation de Formation</h1>
              <div>
                <a href="viewCertificates.php" class="btn btn-outline-secondary mr-2 font-weight-bold" style="border-radius: var(--border-radius-sm);">
                  <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
                <button onclick="window.print();" class="btn btn-premium-success">
                  <i class="fas fa-print mr-1"></i> Imprimer / Enregistrer en PDF
                </button>
              </div>
            </div>

            <!-- Print Certificate Card -->
            <div class="attestation-container">
              <div class="attestation-header">
                <img src="../images1.png" alt="Logo de l'entreprise">
              </div>
              
              <h2 class="attestation-title">Attestation de Formation</h2>
              
              <div class="attestation-body">
                <p>La direction des études certifie par la présente que l'étudiant(e)</p>
                
                <h3 class="font-weight-extrabold my-4 text-gray-900" style="font-size: 1.8rem; letter-spacing: 1px;">
                  <?php echo Security::escapeHTML(strtoupper($selectedAttestation['sLast']) . ' ' . $selectedAttestation['sFirst'] . ' ' . ($selectedAttestation['sOther'] ?? '')); ?>
                </h3>
                
                <p>a suivi avec assiduité et succès la formation professionnelle intitulée :</p>
                <p><span class="attestation-highlight" style="font-size: 1.4rem; padding: 5px 10px; display: inline-block; margin: 10px 0;"><?php echo Security::escapeHTML($selectedAttestation['formationName']); ?></span></p>
                
                <p>dans la classe de <span class="font-weight-bold text-gray-800"><?php echo Security::escapeHTML($selectedAttestation['className']); ?></span> (Lieu d'enseignement : <?php echo Security::escapeHTML($selectedAttestation['classArmName']); ?>),</p>
                <p>sous la supervision pédagogique de l'enseignant(e) <span class="font-weight-bold text-gray-800"><?php echo Security::escapeHTML($selectedAttestation['tFirst'] . ' ' . $selectedAttestation['tLast']); ?></span>,</p>
                <p>durant la session d'apprentissage <span class="font-weight-bold text-gray-800"><?php echo Security::escapeHTML($selectedAttestation['sessionName']); ?></span>.</p>
                
                <p class="mt-4">En foi de quoi, la présente attestation lui est délivrée avec la mention : 
                  <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 1.1rem; text-transform: uppercase; background-color: var(--color-success);">
                    <?php echo Security::escapeHTML($selectedAttestation['mention']); ?>
                  </span>
                </p>
              </div>
              
              <div class="attestation-footer">
                <div class="attestation-date text-left">
                  Fait le <?php echo Security::escapeHTML(date("d/m/Y", strtotime($selectedAttestation['dateGenerated']))); ?>
                </div>
                <div class="attestation-signature">
                  <p class="font-weight-bold text-gray-800 mb-4">La Direction des Études</p>
                  <!-- Mockup signature -->
                  <div style="border-top: 1px dashed #cbd5e1; width: 180px; margin-top: 50px;">
                    <span class="text-xs text-muted">Signature électronique</span>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

        </div>
        <!--- Container Fluid-->
      </div>

    </div>
  </div>

  <!-- Logout Modal-->
  <div class="modal fade d-print-none" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
