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

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'Administrator') {
    header('Location: ../index.php');
    exit;
}

// Récupérer les statistiques via DatabaseOperations
$totalTeachers = $db->count('tblteachers');
$totalClasses = $db->count('tblclass');
$totalStudents = $db->count('tblstudents');

// Récupérer le nom de l'admin connecté
$adminId = (int) $_SESSION['userId'];
$adminData = $db->select('tblusers', ['Id' => $adminId], 'firstName, lastName');
$admin = !empty($adminData) ? $adminData[0] : ['firstName' => '', 'lastName' => ''];

// Récupérer les 5 dernières activités (enseignants ajoutés récemment)
$recentTeachersResult = $db->execute(
    "SELECT firstName, lastName, emailAddress, dateCreated FROM tblteachers ORDER BY dateCreated DESC LIMIT 5"
);
$recentTeachers = [];
if ($recentTeachersResult) {
    while ($row = $recentTeachersResult->fetch_assoc()) {
        $recentTeachers[] = $row;
    }
}

// Récupérer les 5 dernières classes ajoutées
$recentClassesResult = $db->execute(
    "SELECT c.specialisation, s.salleName, c.annee, c.dateCreated 
     FROM tblclass c
     LEFT JOIN tblsalle s ON c.salleId = s.Id 
     ORDER BY c.dateCreated DESC LIMIT 5"
);
$recentClasses = [];
if ($recentClassesResult) {
    while ($row = $recentClassesResult->fetch_assoc()) {
        $recentClasses[] = $row;
    }
}
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
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php";?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php";?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Accueil</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>

          <div class="row mb-3">
            <!-- Total Enseignants -->
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1 text-primary">
                        Enseignants</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$totalTeachers; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total Classes -->
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1 text-success">
                        Classes</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$totalClasses; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-door-open fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total Étudiants -->
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1 text-warning">
                        Étudiants</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$totalStudents; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activities -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Derniers enseignants ajoutés</h6>
                  <a href="createClassTeacher.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="table-responsive p-3">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if(!empty($recentTeachers)): ?>
                        <?php foreach($recentTeachers as $teacher): ?>
                        <tr>
                          <td><?php echo Security::escapeHTML($teacher['firstName'] . ' ' . $teacher['lastName']); ?></td>
                          <td><?php echo Security::escapeHTML($teacher['emailAddress']); ?></td>
                          <td><?php echo date('d/m/Y', strtotime($teacher['dateCreated'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                      <tr>
                        <td colspan="3" class="text-center">Aucun enseignant</td>
                      </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Dernières classes ajoutées</h6>
                  <a href="createClass.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="table-responsive p-3">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Domaine</th>
                        <th>Spécialisation</th>
                        <th>Année</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if(!empty($recentClasses)): ?>
                        <?php foreach($recentClasses as $class): ?>
                        <tr>
                          <td><?php echo Security::escapeHTML($class['salleName']); ?></td>
                          <td><?php echo Security::escapeHTML($class['specialisation']); ?></td>
                          <td><?php echo (int)$class['annee']; ?>ère</td>
                        </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                      <tr>
                        <td colspan="3" class="text-center">Aucune classe</td>
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
      <?php include "Includes/footer.php";?>
    </div>
  </div>

  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>

</html>