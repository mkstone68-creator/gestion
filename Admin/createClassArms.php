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

$statusMsg = '';
$row = null;
$Id = null;

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClassArms.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $classId = (int)$_POST['classId'];
        $classArmName = Security::validateString($_POST['classArmName'] ?? '', 1, 255);
        
        if (!$classArmName || $classId <= 0) {
            $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis.</div>";
        } else {
            // Vérifier si existe déjà
            $existing = $db->execute(
                "SELECT Id FROM tblclassarms WHERE classArmName = ? AND classId = ?",
                'si', [$classArmName, $classId]
            );
            
            if ($existing && $existing->num_rows > 0) {
                $statusMsg = "<div class='alert alert-danger'>Ce groupe existe déjà !</div>";
            } else {
                $insertId = $db->insert('tblclassarms', [
                    'classId' => $classId,
                    'classArmName' => $classArmName,
                    'isAssigned' => '0'
                ]);
                
                if ($insertId) {
                    Security::logSecurityEvent('CLASSARM_CREATED', "ClassArm: $classArmName (ID: $insertId)", 'INFO');
                    $statusMsg = "<div class='alert alert-success'>Groupe créé avec succès !</div>";
                } else {
                    $statusMsg = "<div class='alert alert-danger'>Une erreur est survenue.</div>";
                }
            }
        }
    }
}

//--------------------EDIT------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $Id = (int)$_GET['Id'];
    if ($Id > 0) {
        $editData = $db->select('tblclassarms', ['Id' => $Id]);
        $row = !empty($editData) ? $editData[0] : null;
    }

    //------------UPDATE-----------------------------
    if(isset($_POST['update'])){
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('CSRF_FAILED', 'createClassArms.php update', 'WARNING');
            $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
        } else {
            $classId = (int)$_POST['classId'];
            $classArmName = Security::validateString($_POST['classArmName'] ?? '', 1, 255);
            
            if (!$classArmName || $classId <= 0) {
                $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis.</div>";
            } else {
                $updated = $db->update('tblclassarms', 
                    ['classId' => $classId, 'classArmName' => $classArmName], 
                    ['Id' => $Id]
                );
                
                if ($updated) {
                    Security::logSecurityEvent('CLASSARM_UPDATED', "ClassArm ID: $Id", 'INFO');
                    header('Location: createClassArms.php');
                    exit();
                } else {
                    $statusMsg = "<div class='alert alert-danger'>Une erreur est survenue.</div>";
                }
            }
        }
    }
}

//--------------------------------DELETE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createClassArms.php delete', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $delId = (int)$_GET['Id'];
        if ($delId > 0) {
            $db->delete('tblclassarms', ['Id' => $delId]);
            Security::logSecurityEvent('CLASSARM_DELETED', "ClassArm ID: $delId", 'INFO');
        }
        header('Location: createClassArms.php');
        exit();
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
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
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
            <h1 class="h3 mb-0 text-gray-800">Gestion des Groupes</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Accueil</a></li>
              <li class="breadcrumb-item active" aria-current="page">Groupes</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($Id) ? 'Modifier le groupe' : 'Créer un groupe'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::escapeHTML($csrfToken); ?>">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Classe <span class="text-danger ml-2">*</span></label>
                         <?php
                        $classesData = $db->select('tblclass', [], '*', 'className ASC');
                        if (!empty($classesData)){
                          echo '<select required name="classId" class="form-control mb-3">';
                          echo '<option value="">--Sélectionner une classe--</option>';
                          foreach ($classesData as $cls){
                            $selected = (isset($row['classId']) && $row['classId'] == $cls['Id']) ? 'selected' : '';
                            echo '<option value="'.(int)$cls['Id'].'" '.$selected.'>'.Security::escapeHTML($cls['className']).'</option>';
                          }
                          echo '</select>';
                        }
                        ?>  
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Nom du groupe <span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" name="classArmName" value="<?php echo isset($row['classArmName']) ? Security::escapeHTML($row['classArmName']) : ''; ?>" placeholder="Nom du groupe" required>
                        </div>
                    </div>
                      <?php if (isset($Id)): ?>
                    <button type="submit" name="update" class="btn btn-warning">Mettre à jour</button>
                    <a href="createClassArms.php" class="btn btn-secondary ml-2">Annuler</a>
                      <?php else: ?>
                    <button type="submit" name="save" class="btn btn-primary">Enregistrer</button>
                      <?php endif; ?>
                  </form>
                </div>
              </div>

              <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Tous les groupes</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Classe</th>
                        <th>Nom du groupe</th>
                        <th>Statut</th>
                        <th>Modifier</th>
                        <th>Supprimer</th>
                      </tr>
                    </thead>
                    <tbody>
                  <?php
                      $armsResult = $db->execute(
                          "SELECT tblclassarms.Id, tblclassarms.isAssigned, tblclass.className, tblclassarms.classArmName 
                           FROM tblclassarms
                           INNER JOIN tblclass ON tblclass.Id = tblclassarms.classId"
                      );
                      $sn = 0;
                      $hasData = false;
                      if ($armsResult) {
                          while ($arms = $armsResult->fetch_assoc()) {
                              $hasData = true;
                              $status = ($arms['isAssigned'] == '1') ? "Assigné" : "Non assigné";
                              $sn++;
                              echo "<tr>
                                <td>".$sn."</td>
                                <td>".Security::escapeHTML($arms['className'])."</td>
                                <td>".Security::escapeHTML($arms['classArmName'])."</td>
                                <td>".Security::escapeHTML($status)."</td>
                                <td><a href='?action=edit&Id=".(int)$arms['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td><a href='?action=delete&Id=".(int)$arms['Id']."&csrf_token=".Security::escapeHTML($csrfToken)."' onclick=\"return confirm('Supprimer ce groupe ?');\"><i class='fas fa-fw fa-trash'></i></a></td>
                              </tr>";
                          }
                      }
                      if (!$hasData) {
                          echo "<tr><td colspan='6' class='text-center'>Aucun groupe trouvé</td></tr>";
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>
</body>

</html>