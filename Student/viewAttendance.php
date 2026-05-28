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

// Récupérer les statistiques réelles d'assiduité
$email = $student['emailAddress'];
$admissionNo = "";
$resStudent = $db->select('tblstudents', ['emailAddress' => $email]);
if (!empty($resStudent)) {
  $admissionNo = $resStudent[0]['admissionNumber'];
}

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

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Portail Étudiant - Mes Présences</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <link href="css/premium.css" rel="stylesheet">
  <!-- Load Chart.js CDN for premium interactive pie chart -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <li class="nav-item active">
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Mon Assiduité</h1>
          </div>

          <!-- Pie Chart & Stats Row -->
          <div class="row mb-4">
            <!-- Pie Chart Card -->
            <div class="col-lg-5 mb-4">
              <div class="premium-card h-100">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-chart-pie text-teal mr-2"></i>Répartition (Camembert)</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                  <?php if ($totalClasses > 0): ?>
                    <div style="position: relative; width: 100%; max-width: 250px; height: 250px;">
                      <canvas id="attendancePieChart"></canvas>
                    </div>
                  <?php else: ?>
                    <div class="text-center py-5 text-muted">
                      <i class="fas fa-chart-pie fa-3x d-block mb-3 text-gray-300"></i>
                      Aucune donnée de présence disponible pour générer le graphique.
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Stats & Info Card -->
            <div class="col-lg-7 mb-4">
              <div class="premium-card h-100">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-info-circle text-info mr-2"></i>Résumé de mes présences</h6>
                </div>
                <div class="card-body">
                  <div class="row mb-4">
                    <div class="col-md-6 border-right">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Taux Global d'Assiduité</span>
                      <span class="h3 font-weight-bold text-gray-800"><?php echo $attendanceRate; ?> %</span>
                      <div class="premium-progress-container mt-2">
                        <div class="premium-progress-bar" style="width: <?php echo $attendanceRate; ?>%; background-color: var(--color-success);"></div>
                      </div>
                    </div>
                    <div class="col-md-6 pl-md-4">
                      <span class="text-xs text-muted text-uppercase d-block mb-1">Nombre Total de Séances</span>
                      <span class="h3 font-weight-bold text-gray-800"><?php echo $totalClasses; ?></span>
                      <span class="text-xs text-muted d-block mt-2">Enregistrements effectués par les enseignants.</span>
                    </div>
                  </div>

                  <div class="border-top pt-3">
                    <h6 class="font-weight-bold text-indigo mb-2">📌 Note importante sur l'assiduité :</h6>
                    <p class="text-sm text-muted">Les données affichées sur cette page proviennent **exclusivement des feuilles d'émargement numériques** remplies par vos enseignants à chaque début de cours. Le total s'actualise automatiquement dès que l'enseignant enregistre sa séance.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- History Table -->
          <div class="row">
            <div class="col-lg-12 mb-4">
              <div class="premium-card">
                <div class="premium-card-header">
                  <h6 class="premium-card-title"><i class="fas fa-list text-primary mr-2"></i>Historique des émargements</h6>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table premium-table" id="attendanceTable" width="100%">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Session / Cours</th>
                          <th>Statut</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if (!empty($admissionNo)) {
                          $query = "SELECT tblattendance.dateTimeTaken, tblattendance.status, tblsessionterm.sessionName 
                                    FROM tblattendance 
                                    INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId 
                                    WHERE tblattendance.admissionNo = ?
                                    ORDER BY tblattendance.dateTimeTaken DESC";

                          $stmt = $conn->prepare($query);
                          if ($stmt) {
                            $stmt->bind_param('s', $admissionNo);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                              while ($row = $result->fetch_assoc()) {
                                $statusText = $row['status'] == 1 ? 'Présent' : 'Absent';
                                $statusBadge = $row['status'] == 1 ? 'premium-badge-success' : 'premium-badge-danger';
                                
                                echo "<tr>";
                                echo "<td><span class='font-weight-bold text-gray-800'>" . Security::escapeHTML(date("d/m/Y", strtotime($row['dateTimeTaken']))) . "</span></td>";
                                echo "<td>" . Security::escapeHTML($row['sessionName']) . "</td>";
                                echo "<td><span class='premium-badge " . $statusBadge . "'>" . $statusText . "</span></td>";
                                echo "</tr>";
                              }
                            } else {
                              echo "<tr><td colspan='3' class='text-center py-4 text-muted'>Aucun émargement enregistré pour votre compte.</td></tr>";
                            }
                            $stmt->close();
                          }
                        } else {
                          echo "<tr><td colspan='3' class='text-center py-4 text-muted'>Veuillez patienter qu'un administrateur vous affecte à une classe.</td></tr>";
                        }
                        ?>
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  
  <script>
    $(document).ready(function() {
      $('#attendanceTable').DataTable({
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/French.json"
        }
      });
      
      <?php if ($totalClasses > 0): ?>
      // Render the beautiful premium interactive pie chart (camembert)
      const ctx = document.getElementById('attendancePieChart').getContext('2d');
      new Chart(ctx, {
        type: 'pie',
        data: {
          labels: ['Présent', 'Absent'],
          datasets: [{
            data: [<?php echo $presentClasses; ?>, <?php echo $absentClasses; ?>],
            backgroundColor: ['#0d9488', '#e11d48'],
            borderColor: ['#ffffff', '#ffffff'],
            borderWidth: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                font: {
                  family: "'Outfit', sans-serif",
                  size: 13,
                  weight: '600'
                },
                color: '#1e293b',
                usePointStyle: true,
                padding: 15
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.label || '';
                  if (label) {
                    label += ': ';
                  }
                  const value = context.raw;
                  const total = <?php echo $totalClasses; ?>;
                  const percentage = Math.round((value / total) * 100);
                  label += value + ' cours (' + percentage + '%)';
                  return label;
                }
              }
            }
          }
        }
      });
      <?php endif; ?>
    });
  </script>

</body>

</html>