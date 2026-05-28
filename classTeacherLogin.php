<?php
include 'Includes/dbcon.php';
include 'Includes/Security.php';

// Ajouter les headers de sécurité
Security::setSecurityHeaders();

// Vérifier la session actuelle pour timeout (30 minutes)
$timeout = 1800;
if (isset($_SESSION['LAST']) && (time() - $_SESSION['LAST'] > $timeout)) {
  Security::destroySession();
  header('Location: classTeacherLogin.php?session_expired=1');
  exit;
}
$_SESSION['LAST'] = time();
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
  <title>AMS - Login</title>
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-login">
  <!-- Login Content -->
  <div class="container-login">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-lg-12 col-md-9">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <div class="text-center">
                    <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                    <br><br>
                    <h1 class="h4 text-gray-900 mb-4">Login</h1>
                  </div>
                  <form class="user" method="Post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <div class="form-group">
                      <input type="text" class="form-control" required name="username" id="exampleInputEmail" placeholder="Enter Email Address">
                    </div>
                    <div class="form-group">
                      <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="Enter Password">
                    </div>
                    <div class="form-group">
                      <div class="custom-control custom-checkbox small" style="line-height: 1.5rem;">
                        <input type="checkbox" class="custom-control-input" id="customCheck">
                      </div>
                    </div>
                    <?php if (isset($_GET['session_expired'])): ?>
                      <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        Your session has expired. Please login again.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    <?php if (isset($loginError) && !empty($loginError)): ?>
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo Security::escapeHTML($loginError); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    <div class="form-group">
                      <input type="submit" class="btn btn-primary btn-block" value="Login" name="login" />
                    </div>
                  </form>

                  <?php

                  $loginError = '';

                  if (isset($_POST['login'])) {

                    // 1. Vérifier CSRF token
                    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                      Security::logSecurityEvent('CSRF_FAILED', 'Invalid CSRF token on ClassTeacher login', 'WARNING');
                      $loginError = 'Invalid request. Please try again.';
                    }

                    // 2. Rate limiting (5 tentatives / 15 minutes)
                    if (!$loginError && !Security::checkRateLimit('login_teacher', 5, 900)) {
                      Security::logSecurityEvent('LOGIN_RATELIMIT', 'Too many login attempts from ' . $_SERVER['REMOTE_ADDR'], 'WARNING');
                      $loginError = 'Too many login attempts. Please try again in 15 minutes.';
                    }

                    // 3. Validation des entrées
                    if (!$loginError) {
                      $username = Security::validateEmail($_POST['username'] ?? '');
                      $password = trim($_POST['password'] ?? '');

                      if (!$username) {
                        $loginError = 'Invalid email format.';
                      } elseif (strlen($password) < 1 || strlen($password) > 256) {
                        $loginError = 'Invalid password format.';
                      }
                    }

                    if (!$loginError) {

                      // Requête préparée pour éviter SQL injection sur la table tblusers
                      $stmt = $conn->prepare('SELECT Id, firstName, lastName, emailAddress, password, role FROM tblusers WHERE emailAddress = ? AND role = "ClassTeacher"');
                      if (!$stmt) {
                        Security::logSecurityEvent('DB_ERROR', 'Prepare failed: ' . $conn->error, 'ERROR');
                        $loginError = 'Database error. Please try again later.';
                      } else {
                        $stmt->bind_param('s', $username);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                          $rows = $result->fetch_assoc();

                          // Vérifier le mot de passe avec password_verify
                          if (password_verify($password, $rows['password'])) {
                            // Login réussi
                            Security::regenerateSession();
                            
                            // Récupérer l'Id de l'enseignant dans tblteachers
                            $teacherQuery = mysqli_query($conn, "SELECT Id FROM tblteachers WHERE emailAddress = '{$rows['emailAddress']}'");
                            $teacherData = mysqli_fetch_assoc($teacherQuery);
                            $teacherId = $teacherData ? $teacherData['Id'] : 0;
                            
                            $_SESSION['userId'] = $rows['Id'];
                            $_SESSION['teacherId'] = $teacherId;
                            $_SESSION['firstName'] = $rows['firstName'];
                            $_SESSION['lastName'] = $rows['lastName'];
                            $_SESSION['emailAddress'] = $rows['emailAddress'];
                            $_SESSION['userType'] = 'ClassTeacher';
                            
                            // Sélectionner la première classe disponible par défaut
                            $_SESSION['classId'] = 0;
                            $_SESSION['classArmId'] = 0;
                            if ($teacherId > 0) {
                                $firstClassQuery = mysqli_query($conn, "
                                    SELECT tc.classId, ca.Id as classArmId 
                                    FROM tblteacherclass tc 
                                    LEFT JOIN tblclassarms ca ON ca.classId = tc.classId 
                                    WHERE tc.teacherId = '$teacherId' 
                                    LIMIT 1
                                ");
                                if ($firstClass = mysqli_fetch_assoc($firstClassQuery)) {
                                    $_SESSION['classId'] = $firstClass['classId'];
                                    $_SESSION['classArmId'] = $firstClass['classArmId'];
                                }
                            }

                            Security::logSecurityEvent('LOGIN_SUCCESS', 'Teacher user: ' . $username, 'INFO');

                            header('Location: ClassTeacher/index.php');
                            exit;
                          } else {
                            Security::logSecurityEvent('LOGIN_FAILED', 'Invalid password for: ' . $username, 'WARNING');
                            $loginError = 'Invalid Username/Password!';
                          }
                        } else {
                          Security::logSecurityEvent('LOGIN_FAILED', 'User not found: ' . $username, 'WARNING');
                          $loginError = 'Invalid Username/Password!';
                        }
                        $stmt->close();
                      }
                    }
                  }
                  ?>

                  <!-- <hr>
                    <a href="index.html" class="btn btn-google btn-block">
                      <i class="fab fa-google fa-fw"></i> Login with Google
                    </a>
                    <a href="index.html" class="btn btn-facebook btn-block">
                      <i class="fab fa-facebook-f fa-fw"></i> Login with Facebook
                    </a> -->
                  <hr>
                  <div class="text-center">
                    <a class="font-weight-bold small" href="classTeacherLogin.php">Class Teacher Login!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <!-- <a class="font-weight-bold small" href=".php">Cooperative Account!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="font-weight-bold small" href="forgotPassword.php">Forgot Password?</a> -->

                  </div>
                  <div class="text-center">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Login Content -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>

</html>