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
        Security::logSecurityEvent('CSRF_FAILED', 'createSessionTerm.php save', 'WARNING');
        $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
    } else {
        $sessionName = Security::validateString($_POST['sessionName'] ?? '', 1, 50);
        $termId = (int)($_POST['termId'] ?? 0);
        $dateCreated = date("Y-m-d");
        
        if (!$sessionName || $termId <= 0) {
            $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis.</div>";
        } else {
            $existing = $db->execute(
                "SELECT Id FROM tblsessionterm WHERE sessionName = ? AND termId = ?",
                'si', [$sessionName, $termId]
            );
            
            if ($existing && $existing->num_rows > 0) {
                $statusMsg = "<div class='alert alert-danger'>Cette session et ce trimestre existent déjà !</div>";
            } else {
                $insertId = $db->insert('tblsessionterm', [
                    'sessionName' => $sessionName,
                    'termId' => $termId,
                    'isActive' => '0',
                    'dateCreated' => $dateCreated
                ]);

                if ($insertId) {
                    Security::logSecurityEvent('SESSION_CREATED', "Session: $sessionName (ID: $insertId)", 'INFO');
                    $statusMsg = "<div class='alert alert-success'>Session créée avec succès !</div>";
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
        $editData = $db->select('tblsessionterm', ['Id' => $Id]);
        $row = !empty($editData) ? $editData[0] : null;
    }

    if(isset($_POST['update'])){
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Security::logSecurityEvent('CSRF_FAILED', 'createSessionTerm.php update', 'WARNING');
            $statusMsg = "<div class='alert alert-danger'>Requête invalide.</div>";
        } else {
            $sessionName = Security::validateString($_POST['sessionName'] ?? '', 1, 50);
            $termId = (int)($_POST['termId'] ?? 0);
            
            if (!$sessionName || $termId <= 0) {
                $statusMsg = "<div class='alert alert-danger'>Tous les champs sont requis.</div>";
            } else {
                $updated = $db->update('tblsessionterm', [
                    'sessionName' => $sessionName,
                    'termId' => $termId
                ], ['Id' => $Id]);
                
                if ($updated) {
                    Security::logSecurityEvent('SESSION_UPDATED', "Session ID: $Id", 'INFO');
                    header('Location: createSessionTerm.php');
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
        Security::logSecurityEvent('CSRF_FAILED', 'createSessionTerm.php delete', 'WARNING');
    } else {
        $delId = (int)$_GET['Id'];
        if ($delId > 0) {
            $db->delete('tblsessionterm', ['Id' => $delId]);
            Security::logSecurityEvent('SESSION_DELETED', "Session ID: $delId", 'INFO');
        }
    }
    header('Location: createSessionTerm.php');
    exit();
}

//--------------------------------ACTIVATE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "activate") {
    if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
        Security::logSecurityEvent('CSRF_FAILED', 'createSessionTerm.php activate', 'WARNING');
    } else {
        $actId = (int)$_GET['Id'];
        if ($actId > 0) {
            // Désactiver toutes les sessions
            $db->execute("UPDATE tblsessionterm SET isActive = '0' WHERE isActive = '1'");
            // Activer la session sélectionnée
            $db->update('tblsessionterm', ['isActive' => '1'], ['Id' => $actId]);
            Security::logSecurityEvent('SESSION_ACTIVATED', "Session ID: $actId", 'INFO');
        }
    }
    header('Location: createSessionTerm.php');
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
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php";?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php";?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Gestion des Sessions et Trimestres</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Accueil</a></li>
              <li class="breadcrumb-item active" aria-current="page">Sessions et Trimestres</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($Id) ? 'Modifier la session' : 'Créer une session'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::escapeHTML($csrfToken); ?>">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                            <label class="form-control-label">Nom de la session <span class="text-danger ml-2">*</span></label>
                            <input type="text" class="form-control" name="sessionName" value="<?php echo isset($row['sessionName']) ? Security::escapeHTML($row['sessionName']) : ''; ?>" placeholder="Ex: 2024-2025" required>
                        </div>
                        <div class="col-xl-6">
                            <label class="form-control-label">Trimestre <span class="text-danger ml-2">*</span></label>
                            <?php
                            $termsData = $db->select('tblterm', [], '*', 'termName ASC');
                            if (!empty($termsData)){
                              echo '<select required name="termId" class="form-control mb-3">';
                              echo '<option value="">--Sélectionner un trimestre--</option>';
                              foreach ($termsData as $term){
                                $selected = (isset($row['termId']) && $row['termId'] == $term['Id']) ? 'selected' : '';
                                echo '<option value="'.(int)$term['Id'].'" '.$selected.'>'.Security::escapeHTML($term['termName']).'</option>';
                              }
                              echo '</select>';
                            }
                            ?>  
                        </div>
                    </div>
                      <?php if (isset($Id)): ?>
                    <button type="submit" name="update" class="btn btn-warning">Mettre à jour</button>
                    <a href="createSessionTerm.php" class="btn btn-secondary ml-2">Annuler</a>
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
                  <h6 class="m-0 font-weight-bold text-primary">Toutes les sessions</h6>
                  <h6 class="m-0 font-weight-bold text-danger">Note: <i>Cliquez sur ✓ pour activer une session</i></h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Session</th>
                        <th>Trimestre</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Activer</th>
                        <th>Modifier</th>
                        <th>Supprimer</th>
                      </tr>
                    </thead>
                    <tbody>
                  <?php
                      $sessResult = $db->execute(
                          "SELECT tblsessionterm.Id, tblsessionterm.sessionName, tblsessionterm.isActive, 
                           tblsessionterm.dateCreated, tblterm.termName
                           FROM tblsessionterm
                           INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId"
                      );
                      $sn = 0;
                      $hasData = false;
                      if ($sessResult) {
                          while ($srow = $sessResult->fetch_assoc()) {
                              $hasData = true;
                              $status = ($srow['isActive'] == '1') ? "Actif" : "Inactif";
                              $statusClass = ($srow['isActive'] == '1') ? 'text-success font-weight-bold' : 'text-muted';
                              $sn++;
                              echo "<tr>
                                <td>".$sn."</td>
                                <td>".Security::escapeHTML($srow['sessionName'])."</td>
                                <td>".Security::escapeHTML($srow['termName'])."</td>
                                <td class='".$statusClass."'>".Security::escapeHTML($status)."</td>
                                <td>".Security::escapeHTML($srow['dateCreated'])."</td>
                                <td><a href='?action=activate&Id=".(int)$srow['Id']."&csrf_token=".Security::escapeHTML($csrfToken)."' class='btn btn-sm btn-success'><i class='fas fa-fw fa-check'></i></a></td>
                                <td><a href='?action=edit&Id=".(int)$srow['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td><a href='?action=delete&Id=".(int)$srow['Id']."&csrf_token=".Security::escapeHTML($csrfToken)."' onclick=\"return confirm('Supprimer cette session ?');\"><i class='fas fa-fw fa-trash'></i></a></td>
                              </tr>";
                          }
                      }
                      if (!$hasData) {
                          echo "<tr><td colspan='8' class='text-center'>Aucune session trouvée</td></tr>";
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