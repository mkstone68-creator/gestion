<?php
include 'Includes/dbcon.php';
include 'Includes/Security.php';

Security::setSecurityHeaders();

$timeout = 1800;
if (isset($_SESSION['LAST']) && (time() - $_SESSION['LAST'] > $timeout)) {
  Security::destroySession();
  header('Location: index.php?session_expired=1');
  exit;
}
$_SESSION['LAST'] = time();

$loginError = '';

if (isset($_POST['login'])) {

  if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    Security::logSecurityEvent('CSRF_FAILED', 'Invalid CSRF token', 'WARNING');
    $loginError = 'Invalid request. Please try again.';
  }

  if (!$loginError && !Security::checkRateLimit('login', 20, 900)) {
    Security::logSecurityEvent('LOGIN_RATELIMIT', 'Too many login attempts from ' . $_SERVER['REMOTE_ADDR'], 'WARNING');
    $loginError = 'Too many login attempts. Please try again in 15 minutes.';
  }

  $userType = trim($_POST['userType'] ?? '');
  $username = Security::validateEmail($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if (!$loginError) {
    if (!$username) {
      $loginError = 'Invalid email format.';
    } elseif (strlen($password) < 1 || strlen($password) > 256) {
      $loginError = 'Invalid password format.';
    } elseif ($userType !== 'Administrator' && $userType !== 'ClassTeacher' && $userType !== 'Student') {
      $loginError = 'Invalid user type.';
    }
  }

  if (!$loginError) {
    $stmt = $conn->prepare('SELECT Id, firstName, lastName, emailAddress, password, role FROM tblusers WHERE emailAddress = ? AND role = ?');
    if (!$stmt) {
      Security::logSecurityEvent('DB_ERROR', 'Prepare failed: ' . $conn->error, 'ERROR');
      $loginError = 'Database error. Please try again later.';
    } else {
      $stmt->bind_param('ss', $username, $userType);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $rows = $result->fetch_assoc();

        if (password_verify($password, $rows['password'])) {
          Security::regenerateSession();
          $_SESSION['userId']       = $rows['Id'];
          $_SESSION['firstName']    = $rows['firstName'];
          $_SESSION['lastName']     = $rows['lastName'];
          $_SESSION['emailAddress'] = $rows['emailAddress'];
          $_SESSION['userType']     = $rows['role'];

          Security::logSecurityEvent('LOGIN_SUCCESS', $userType . ' user: ' . $username, 'INFO');

          if ($rows['role'] === 'Administrator') {
            header('Location: Admin/index.php');
          } elseif ($rows['role'] === 'ClassTeacher') {
            header('Location: ClassTeacher/index.php');
          } elseif ($rows['role'] === 'Student') {
            header('Location: Student/index.php');
          }
          exit;
        } else {
          Security::logSecurityEvent('LOGIN_FAILED', 'Invalid password for: ' . $username, 'WARNING');
          $loginError = 'Invalid Username/Password!';
        }
      } else {
        Security::logSecurityEvent('LOGIN_FAILED', 'User not found: ' . $username . ' as ' . $userType, 'WARNING');
        $loginError = 'Invalid Username/Password!';
      }
      $stmt->close();
    }
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
  <title>Secel Formation - Login</title>
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-login" style="background-image: url('goran-ivos-iacpoKgpBAM-unsplash.jpg');">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card shadow-sm my-5">
          <div class="card-body p-4">
            <div class="login-form">
              <h5 align="center">STUDENT FORMATION SYSTEM</h5>
              <div class="text-center">
                <img src="images1.png" style="width:100px;height:50px">
                <br><br>
                <h1 class="h4 text-gray-900 mb-4">Login Panel</h1>
              </div>
              <form class="user" method="Post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <div class="form-group">
                  <select required name="userType" class="form-control form-control-user mb-3">
                    <option value="">--Select User Roles--</option>
                    <option value="Administrator">Administrator</option>
                    <option value="ClassTeacher">ClassTeacher</option>
                    <option value="Student">Student</option>
                  </select>
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-user" required name="username" id="exampleInputEmail" placeholder="Enter Email Address">
                </div>
                <div class="form-group">
                  <input type="password" name="password" required class="form-control form-control-user" id="exampleInputPassword" placeholder="Enter Password">
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
                <?php if (!empty($loginError)): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo Security::escapeHTML($loginError); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                <?php endif; ?>
                <div class="form-group">
                  <input type="submit" class="btn btn-success btn-block btn-user" value="Login" name="login" />
                </div>
              </form>

              <!-- AJOUT : lien vers register.php -->
              <hr>
              <div class="text-center">
                <a href="register.php">Create an Admin Account</a>
              </div>
              <!-- FIN AJOUT -->

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>

</html>