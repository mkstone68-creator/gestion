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
        Security::logSecurityEvent('CSRF_FAILED', 'createFormation.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $name = Security::validateString($_POST['name'] ?? '', 1, 255);
        $description = Security::validateString($_POST['description'] ?? '', 0, 1000);
        $price = Security::validateString($_POST['price'] ?? '', 1, 50);
        $classArmId = (int)($_POST['classArmId'] ?? 0);
        $duration = Security::validateString($_POST['duration'] ?? '', 1, 50);
        $date_created = date("Y-m-d");
        
        if (!$name || !$price || !$duration || $classArmId <= 0) {
            $statusMsg = "<div class='alert alert-danger'>Tous les champs requis doivent être remplis.</div>";
        } else {
            // Vérifier si existe déjà
            $existing = $db->execute("SELECT Id FROM tblformation WHERE name = ?", 's', [$name]);
            
            if ($existing && $existing->num_rows > 0) {
                $statusMsg = "<div class='alert alert-danger'>Ce nom de formation existe déjà !</div>";
            } else {
                $insertId = $db->insert('tblformation', [
                    'name' => $name,
                    'description' => $description ?: '',
                    'price' => $price,
                    'duration' => $duration,
                    'dateCreated' => $date_created,
                    'classArmId' => $classArmId
                ]);

                if ($insertId) {
                    Security::logSecurityEvent('FORMATION_CREATED', "Formation: $name (ID: $insertId)", 'INFO');
                    $statusMsg = "<div class='alert alert-success'>Formation créée avec succès !</div>";
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
        $editData = $db->select('tblformation', ['Id' => $Id]);
        $row = !empty($editData) ? $editData[0] : null;
    }

    //------------UPDATE-----------------------------
    if(isset($_POST['update'])){
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('CSRF_FAILED', 'createFormation.php update', 'WARNING');
            $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
        } else {
            $name = Security::validateString($_POST['name'] ?? '', 1, 255);
            $description = Security::validateString($_POST['description'] ?? '', 0, 1000);
            $price = Security::validateString($_POST['price'] ?? '', 1, 50);
            $classArmId = (int)($_POST['classArmId'] ?? 0);
            $duration = Security::validateString($_POST['duration'] ?? '', 1, 50);
            
            if (!$name || !$price || !$duration || $classArmId <= 0) {
                $statusMsg = "<div class='alert alert-danger'>Tous les champs requis doivent être remplis.</div>";
            } else {
                $updated = $db->update('tblformation', [
                    'name' => $name,
                    'description' => $description ?: '',
                    'price' => $price,
                    'classArmId' => $classArmId,
                    'duration' => $duration
                ], ['Id' => $Id]);
                
                if ($updated) {
                    Security::logSecurityEvent('FORMATION_UPDATED', "Formation ID: $Id", 'INFO');
                    header('Location: createFormation.php');
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
        Security::logSecurityEvent('CSRF_FAILED', 'createFormation.php delete', 'WARNING');
    } else {
        $delId = (int)$_GET['Id'];
        if ($delId > 0) {
            $db->delete('tblformation', ['Id' => $delId]);
            Security::logSecurityEvent('FORMATION_DELETED', "Formation ID: $delId", 'INFO');
        }
    }
    header('Location: createFormation.php');
    exit();
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

  <script>
    function classArmDropdown(str) {
        if (str == "") {
            document.getElementById("txtHint").innerHTML = "";
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xhr.open("GET","ajaxClassArms2.php?cid="+encodeURIComponent(str),true);
        xhr.send();
    }
  </script>
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php";?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php";?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Gestion des Formations</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Accueil</a></li>
              <li class="breadcrumb-item active" aria-current="page">Formations</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($Id) ? 'Modifier la formation' : 'Créer une formation'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::escapeHTML($csrfToken); ?>">
                   <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Nom <span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo isset($row['name']) ? Security::escapeHTML($row['name']) : ''; ?>" required>
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Description</label>
                        <input type="text" class="form-control" name="description" value="<?php echo isset($row['description']) ? Security::escapeHTML($row['description']) : ''; ?>">
                        </div>
                    </div>
                     <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Prix <span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="price" value="<?php echo isset($row['price']) ? Security::escapeHTML($row['price']) : ''; ?>" required>
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Durée <span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="duration" value="<?php echo isset($row['duration']) ? Security::escapeHTML($row['duration']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Classe <span class="text-danger ml-2">*</span></label>
                         <?php
                        $classesData = $db->select('tblclass', [], '*', 'className ASC');
                        if (!empty($classesData)){
                          echo '<select required name="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                          echo '<option value="">--Sélectionner une classe--</option>';
                          foreach ($classesData as $cls){
                            echo '<option value="'.(int)$cls['Id'].'">'.Security::escapeHTML($cls['className']).'</option>';
                          }
                          echo '</select>';
                        }
                        ?>  
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Groupe <span class="text-danger ml-2">*</span></label>
                            <div id='txtHint'></div>
                        </div>
                    </div>
                      <?php if (isset($Id)): ?>
                    <button type="submit" name="update" class="btn btn-warning">Mettre à jour</button>
                    <a href="createFormation.php" class="btn btn-secondary ml-2">Annuler</a>
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
                  <h6 class="m-0 font-weight-bold text-primary">Toutes les formations</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Prix</th>
                        <th>Durée</th>
                        <th>Classe</th>
                        <th>Date</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                     <?php
                      $formResult = $db->execute(
                          "SELECT tblformation.Id, tblclass.className, tblformation.name,
                           tblformation.description, tblformation.price, tblformation.classArmId, 
                           tblformation.duration, tblformation.dateCreated
                           FROM tblformation
                           LEFT JOIN tblclass ON tblclass.Id = tblformation.classArmId"
                      );
                      $sn = 0;
                      $hasData = false;
                      if ($formResult) {
                          while ($frow = $formResult->fetch_assoc()) {
                              $hasData = true;
                              $sn++;
                              echo "<tr>
                               <td>".$sn."</td>
                                <td>".Security::escapeHTML($frow['name'])."</td>
                                <td>".Security::escapeHTML($frow['description'])."</td>
                                <td>".Security::escapeHTML($frow['price'])."</td>
                                <td>".Security::escapeHTML($frow['duration'])."</td>
                                <td>".Security::escapeHTML($frow['className'] ?? '')."</td>
                                <td>".Security::escapeHTML($frow['dateCreated'])."</td>
                                <td>
                                  <a href='?action=edit&Id=".(int)$frow['Id']."' class='btn btn-sm btn-info'><i class='fas fa-fw fa-edit'></i></a>
                                  <a href='?action=delete&Id=".(int)$frow['Id']."&csrf_token=".Security::escapeHTML($csrfToken)."' class='btn btn-sm btn-danger' onclick=\"return confirm('Supprimer cette formation ?');\"><i class='fas fa-fw fa-trash'></i></a>
                                </td>
                              </tr>";
                          }
                      }
                      if (!$hasData) {
                          echo "<tr><td colspan='8' class='text-center'>Aucune formation trouvée</td></tr>";
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