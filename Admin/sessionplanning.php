<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';



//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
  $start_date=$_POST['start_date'];
  $end_date=$_POST['end_date'];
  $start_time=$_POST['start_time'];
  $end_time=$_POST['end_time'];
  $firstName = $_POST['firstName'];
  $className = $_POST['className'];
  $subject = $_POST['subject'];

 /*  echo "<pre>";
  var_dump($start_date);
  echo "</pre>";
  die; */
    $query=mysqli_query($conn,"SELECT * FROM tblplanning WHERE `start_date` ='$start_date'");
    
    if($query->num_rows > 0){ 
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This starting date Already Exists!</div>";
    }
    else{
        // Préparer la requête
        $stmt = mysqli_prepare($conn, "INSERT INTO tblplanning (start_date, end_date, start_time, end_time, firstName, className, subject) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            // Gérer l'erreur de préparation de la requête
            echo "Erreur lors de la préparation de la requête : " . mysqli_error($conn);
            exit;
        }
        
        // Lier les paramètres
        mysqli_stmt_bind_param($stmt, "sssssss", $start_date, $end_date, $start_time, $end_time, $firstName, $className, $subject);
        // "sssssss" signifie que tous les paramètres sont des chaînes de caractères (strings). Adaptez en fonction des types de données de vos colonnes.
        
        // Exécuter la requête
        $result = mysqli_stmt_execute($stmt);
        echo "<pre>";
        var_dump($result);
        echo "</pre>";
        die;
        if ($result === false) {
            // Gérer l'erreur d'exécution de la requête
            echo "Erreur lors de l'exécution de la requête : " . mysqli_error($conn);
            exit;
        }

        // Vérifier si l'insertion a réussi
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>Planification ajoutée avec succès!</div>";
        } else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>Erreur lors de l'ajout de la planification.</div>";
        }

        // Fermer la requête préparée
        mysqli_stmt_close($stmt);

/*     $query = mysqli_query(
        $conn,
        "INSERT  INTO tblplanning(start_date,end_date,start_time,end_time,firstName,className,subject)
        VALUES($start_date,$end_date,$start_time,$end_time,$firstName,$className,$subject)");
    echo "<pre>";
    var_dump($query->num_rows);
    echo "</pre>";
    die;

    if ($query) {
        
        $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;'>Created Successfully!</div>";
            
    }
    else
    {
         $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    } */
  }
}

//---------------------------------------EDIT-------------------------------------------------------------






//--------------------EDIT------------------------------------------------------------

 if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit")
	{
        $Id= $_GET['Id'];

        $query=mysqli_query($conn,"select * from tblplanning where Id ='$Id'");
        $row=mysqli_fetch_array($query);

        //------------UPDATE-----------------------------

        if(isset($_POST['update'])){
    
    
            $start_date=$_POST['start_date'];
            $end_date=$_POST['end_date'];
            $start_time=$_POST['start_time'];
           
            
            $end_time=$_POST['end_time'];
            $firstName = $_POST['firstName'];
            $className = $_POST['className'];
            $subject = $_POST['subject'];

 $query=mysqli_query($conn,"update tblplanning set start_date='$start_date', end_date='$end_date',
   start_time='$start_time',end_time='$end_time', firstName='$firstName', className='$className'
    where Id='$Id'");
            if ($query) {
                
                echo "<script type = \"text/javascript\">
                window.location = (\"sessionplanning.php\")
                </script>"; 
            }
            else
            {
                $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
            }
        }
    }


//--------------------------------DELETE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete")
	{
        $Id= $_GET['Id'];
        $start_date=$_POST['start_date'];
        $end_date=$_POST['end_date'];
        $start_time=$_POST['start_time'];
           
        $end_time=$_POST['end_time'];
        $firstName = $_POST['firstName'];
        $className = $_POST['className'];
        $subject = $_POST['subject'];



        $query = mysqli_query($conn,"DELETE FROM tblplanning WHERE Id='$Id'");

        if ($query == TRUE) {

            echo "<script type = \"text/javascript\">
            window.location = (\"sessionplanning.php\")
            </script>";
        }
        else{

            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>"; 
         }
      
  }


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
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
    } else { 
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
        xmlhttp.send();
    }
}
</script>
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Create Planning</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Planning</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Planning</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                   <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">start_date<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="start_date" value="<?php echo $row['start_date'];?>" id="exampleInputstart_date" >
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">end_date<span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" name="end_date" value="<?php echo $row['end_date'];?>" id="exampleInputend_date" >
                        </div>
                    </div>
                     <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">start_time<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="start_time" value="<?php echo $row['start_time'];?>" id="exampleInputstart_time" >
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">end_time<span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" required name="end_time" value="<?php echo $row['end_time'];?>" id="exampleInputend_time" >
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">first_name<span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" required name="firstName" value="<?php echo $row['firstName'];?>" id="exampleInputfirst_name" >
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">subject<span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" required name="subject" value="<?php echo $row['subject'];?>" id="exampleInputsubject" >
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                         <?php
                        $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;		
                        if ($num > 0){
                          echo ' <select required name="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                          echo'<option value="">--Select Class--</option>';
                          while ($rows = $result->fetch_assoc()){
                          echo'<option value="'.$rows['Id'].'" >'.$rows['className'].'</option>';
                              }
                                  echo '</select>';
                              }
                            ?>  
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Class Arm<span class="text-danger ml-2">*</span></label>
                            <?php
                                echo"<div id='txtHint'></div>";
                            ?>
                        </div>
                    </div>
                      <?php
                    if (isset($Id))
                    {
                    ?>
                    <button type="submit" name="update" class="btn btn-warning">Update</button>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php
                    } else {           
                    ?>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php
                    }         
                    ?>
                  </form>
                </div>
              </div>

              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Planning</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>start_date</th>
                        <th>end_date</th>
                        <th>start_time</th>
                        <th>end_time</th>
                        <th>firstName</th>
                        <th>className</th>
                        <th>class Arm</th>
                        <th>subject</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                
                    <tbody>

                     <?php
                      $query = "SELECT tblplanning.Id,tblclass.className,tblplanning.start_date,
                      tblplanning.end_date,tblplanning.start_time,tblplanning.end_time,tblplanning.firstName,tblplanning.subject
                      FROM tblplanning
                      INNER JOIN tblclass ON tblclass.Id = tblplanning.classId
                      INNER JOIN tblclassarms ON tblclassarms.Id = tblplanning.classArmId";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn=0;
                      $status="";
                      /* echo '<pre>';
                      var_dump($rs->fetch_assoc());
                      echo '</pre>';
                      die; */
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                             $sn = $sn + 1;
                            echo"
                              <tr>
                               <td>".$sn."</td>
                                <td>".$rows['start_date']."</td>
                                <td>".$rows['end_date']."</td>
                                <td>".$rows['start_time']."</td>
                                <td>".$rows['end_time']."</td>
                                <td>".$rows['firstName']."</td>
                                 <td>".$rows['className']."</td>
                                <td>".$rows['classArmId']."</td>
                                 <td>".$rows['subject']."</td>
                                <td><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td><a href='?action=delete&Id=".$rows['Id']."'><i class='fas fa-fw fa-trash'></i></a></td>
                              </tr>";
                          }
                      }
                      else
                      {
                           echo   
                           "<div class='alert alert-danger' role='alert'>
                            No Record Found!
                            </div>";
                      }
                      
                     ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            </div>
          </div>
          <!--Row-->

          <!-- Documentation Link -->
          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>For more documentations you can visit<a href="https://getbootstrap.com/docs/4.3/components/forms/"
                  target="_blank">
                  bootstrap forms documentations.</a> and <a
                  href="https://getbootstrap.com/docs/4.3/components/input-group/" target="_blank">bootstrap input
                  groups documentations</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
   <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>