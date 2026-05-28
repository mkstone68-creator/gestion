<?php
include 'Includes/dbcon.php';
include 'Includes/Security.php';

Security::setSecurityHeaders();

$registerError = '';
$registerSuccess = '';

if (isset($_POST['register'])) {

  // 1. CSRF
  if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    Security::logSecurityEvent('CSRF_FAILED', 'Invalid CSRF token on register', 'WARNING');
    $registerError = 'Invalid request. Please try again.';
  }

  // 2. Rate limiting
  if (!$registerError && !Security::checkRateLimit('register', 5, 900)) {
    $registerError = 'Too many attempts. Please try again in 15 minutes.';
  }

  // 3. Validation des champs
  if (!$registerError) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName']  ?? '');
    $email     = Security::validateEmail($_POST['email'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');

    if (empty($firstName) || empty($lastName)) {
      $registerError = 'First name and last name are required.';
    } elseif (!$email) {
      $registerError = 'Invalid email format.';
    } elseif (empty($phone)) {
      $registerError = 'Phone number is required.';
    } elseif (strlen($password) < 8) {
      $registerError = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
      $registerError = 'Passwords do not match.';
    }
  }

  // 4. Vérifier si l'email existe déjà
  if (!$registerError) {
    $stmt = $conn->prepare('SELECT Id FROM tblusers WHERE emailAddress = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $registerError = 'This email is already registered.';
    }
    $stmt->close();
  }

  // 5. Insertion
  if (!$registerError) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $role   = 'Administrator';
    $stmt   = $conn->prepare('INSERT INTO tblusers (firstName, lastName, emailAddress, password, role, phoneNo) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $firstName, $lastName, $email, $hashed, $role, $phone);

    if ($stmt->execute()) {
      Security::logSecurityEvent('REGISTER_SUCCESS', 'New admin: ' . $email, 'INFO');
      $registerSuccess = 'Account created successfully! You can now <a href="index.php">login here</a>.';
    } else {
      $registerError = 'Database error. Please try again.';
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Secel Formation - Register</title>
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
                <h1 class="h4 text-gray-900 mb-4">Admin Registration</h1>
              </div>

              <?php if (!empty($registerSuccess)): ?>
                <div class="alert alert-success"><?php echo $registerSuccess; ?></div>
              <?php else: ?>

              <?php if (!empty($registerError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php echo Security::escapeHTML($registerError); ?>
                  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
              <?php endif; ?>

              <form class="user" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">

                <div class="form-group row">
                  <div class="col-sm-6 mb-3">
                    <input type="text" class="form-control form-control-user" name="firstName" placeholder="First Name" value="<?php echo isset($firstName) ? Security::escapeHTML($firstName) : ''; ?>" required>
                  </div>
                  <div class="col-sm-6 mb-3">
                    <input type="text" class="form-control form-control-user" name="lastName" placeholder="Last Name" value="<?php echo isset($lastName) ? Security::escapeHTML($lastName) : ''; ?>" required>
                  </div>
                </div>

                <div class="form-group">
                  <input type="email" class="form-control form-control-user" name="email" placeholder="Email Address" value="<?php echo isset($email) ? Security::escapeHTML($email) : ''; ?>" required>
                </div>

                <div class="form-group">
                  <input type="tel" class="form-control form-control-user" name="phone" placeholder="Phone Number" value="<?php echo isset($phone) ? Security::escapeHTML($phone) : ''; ?>" required>
                </div>

                <div class="form-group">
                  <input type="password" class="form-control form-control-user" name="password" placeholder="Password (min. 8 characters)" required>
                </div>

                <div class="form-group">
                  <input type="password" class="form-control form-control-user" name="confirm" placeholder="Confirm Password" required>
                </div>

                <div class="form-group">
                  <input type="submit" name="register" class="btn btn-success btn-block btn-user" value="Create Account">
                </div>
              </form>

              <?php endif; ?>

              <hr>
              <div class="text-center">
                <a href="index.php">&#8592; Back to Login</a>
              </div>
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