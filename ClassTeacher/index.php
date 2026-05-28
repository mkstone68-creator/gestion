<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

$error = '';

// 1. GESTION DE LA SÉLECTION D'UNE CLASSE
if (isset($_GET['select_class']) && isset($_GET['class_id'])) {
    $_SESSION['classId'] = intval($_GET['class_id']);
    $_SESSION['classArmId'] = isset($_GET['arm_id']) ? intval($_GET['arm_id']) : 0;
    
    $redirect = $_GET['select_class'];
    // Sécurité de redirection basique
    $allowed_pages = ['takeAttendance.php', 'viewStudents.php', 'viewAttendance.php', 'viewStudentAttendance.php', 'downloadRecord.php'];
    if (in_array($redirect, $allowed_pages)) {
        header("Location: " . $redirect);
    } else {
        header("Location: index.php");
    }
    exit();
}

// 2. GESTION DU LOGIN DEPUIS CETTE PAGE (SI BESOIN)
if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Requête sur tblusers qui est la table principale de connexion
    $stmt = $conn->prepare("SELECT * FROM tblusers WHERE emailAddress = ? AND role = 'ClassTeacher'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        if(password_verify($password, $row['password'])){
            // Récupérer l'Id Enseignant dans tblteachers
            $teacherQuery = mysqli_query($conn, "SELECT Id FROM tblteachers WHERE emailAddress = '$email'");
            $teacherData = mysqli_fetch_assoc($teacherQuery);
            
            $_SESSION['userId'] = $row['Id'];
            $_SESSION['teacherId'] = $teacherData ? $teacherData['Id'] : 0;
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName'] = $row['lastName'];
            $_SESSION['emailAddress'] = $row['emailAddress'];
            $_SESSION['userType'] = 'ClassTeacher';
            $_SESSION['role'] = 'teacher';
            $_SESSION['LAST'] = time();
            
            // Sélectionner la première classe disponible par défaut
            if ($teacherData) {
                $firstClassQuery = mysqli_query($conn, "
                    SELECT tc.classId, ca.Id as classArmId 
                    FROM tblteacherclass tc 
                    LEFT JOIN tblclassarms ca ON ca.classId = tc.classId 
                    WHERE tc.teacherId = '{$teacherData['Id']}' 
                    LIMIT 1
                ");
                if ($firstClass = mysqli_fetch_assoc($firstClassQuery)) {
                    $_SESSION['classId'] = $firstClass['classId'];
                    $_SESSION['classArmId'] = $firstClass['classArmId'];
                }
            }
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Mot de passe incorrect";
        }
    } else {
        $error = "Email non trouvé ou profil invalide";
    }
}

// 3. VÉRIFICATION DE L'AUTHENTIFICATION POUR LE TABLEAU DE BORD
$isLoggedIn = false;
if (isset($_SESSION['userId']) && $_SESSION['userType'] === 'ClassTeacher') {
    $isLoggedIn = true;
    
    // Récupérer le teacherId s'il n'est pas déjà défini
    if (!isset($_SESSION['teacherId']) || $_SESSION['teacherId'] == 0) {
        $teacherQuery = mysqli_query($conn, "SELECT Id FROM tblteachers WHERE emailAddress = '{$_SESSION['emailAddress']}'");
        if ($teacherData = mysqli_fetch_assoc($teacherQuery)) {
            $_SESSION['teacherId'] = $teacherData['Id'];
        }
    }
    
    $teacherId = $_SESSION['teacherId'];
    
    // Récupérer les classes de l'enseignant
    $classesQuery = mysqli_query($conn, "
        SELECT tc.classId, c.className, c.annee, c.specialisation, s.salleName
        FROM tblteacherclass tc
        JOIN tblclass c ON tc.classId = c.Id
        LEFT JOIN tblsalle s ON c.salleId = s.Id
        WHERE tc.teacherId = '$teacherId'
    ");
    
    $myClasses = [];
    $totalStudents = 0;
    while ($c = mysqli_fetch_assoc($classesQuery)) {
        // Compter les étudiants pour cette classe
        $studentsCountQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblstudents WHERE classId = '{$c['classId']}'");
        $studentsCount = mysqli_fetch_assoc($studentsCountQuery)['count'];
        $c['studentCount'] = $studentsCount;
        $totalStudents += $studentsCount;
        
        // Récupérer les groupes (Class Arms) de cette classe
        $armsQuery = mysqli_query($conn, "SELECT * FROM tblclassarms WHERE classId = '{$c['classId']}'");
        $arms = [];
        while ($arm = mysqli_fetch_assoc($armsQuery)) {
            // Etudiants par groupe
            $armStudentsQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblstudents WHERE classId = '{$c['classId']}' AND classArmId = '{$arm['Id']}'");
            $arm['studentCount'] = mysqli_fetch_assoc($armStudentsQuery)['count'];
            $arms[] = $arm;
        }
        $c['arms'] = $arms;
        $myClasses[] = $c;
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
    <title><?php echo $isLoggedIn ? "Dashboard Enseignant" : "Connexion Enseignant"; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #1a73e8;
            --color-secondary: #5f6368;
            --color-success: #1e8e3e;
            --color-warning: #f9ab00;
            --color-danger: #d93025;
            --border-radius: 12px;
            --shadow-sm: 0 2px 6px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        <?php if(!$isLoggedIn): ?>
        body {
            background: linear-gradient(135deg, #1a73e8 0%, #1557b0 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: none;
        }
        .login-header {
            background: var(--color-primary);
            color: white;
            padding: 30px;
            text-align: center;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        .login-body {
            padding: 40px;
            background: white;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }
        .btn-login {
            background: var(--color-primary);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-login:hover {
            background: #1557b0;
        }
        <?php else: ?>
        .premium-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            background: white;
            margin-bottom: 20px;
        }
        .premium-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .class-badge {
            background: #e8f0fe;
            color: var(--color-primary);
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .bg-light-blue { background: #e8f0fe; color: var(--color-primary); }
        .bg-light-green { background: #e6f4ea; color: var(--color-success); }
        .bg-light-yellow { background: #fef7e0; color: var(--color-warning); }
        
        .active-class-banner {
            background: linear-gradient(90deg, #1a73e8, #1557b0);
            color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .btn-action {
            border-radius: 8px;
            font-weight: 600;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        .group-item {
            border-left: 4px solid var(--color-primary);
            background: #f8f9fa;
            padding: 12px 18px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 10px;
        }
        <?php endif; ?>
    </style>
</head>

<body id="page-top">

<?php if(!$isLoggedIn): ?>
    <!-- FORMULAIRE DE CONNEXION -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                        <h3>Espace Enseignant</h3>
                        <p class="mb-0">Connectez-vous avec vos identifiants</p>
                    </div>
                    <div class="login-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="form-group mb-3">
                                <label>Email professionnel</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" name="email" class="form-control" placeholder="professeur@ecole.com" required>
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label>Mot de passe</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-login btn-block text-white">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </button>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <a href="../index.php" class="small text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Retour au portail principal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- TABLEAU DE BORD ENSEIGNANT -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        <!-- Sidebar -->
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->
                
                <div class="container-fluid" id="container-wrapper">
                    <!-- Fil d'ariane -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Tableau de Bord</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Accueil</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                    
                    <!-- Bannière d'accueil -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card premium-card" style="border-left: 5px solid var(--color-primary); background: #fdfdfd;">
                                <div class="card-body p-4">
                                    <h4 class="font-weight-bold text-primary">Bonjour, <?php echo htmlspecialchars($_SESSION['firstName']); ?> ! 👋</h4>
                                    <p class="text-muted mb-0">Bienvenue sur votre portail enseignant. Vous pouvez suivre l'assiduité, gérer vos étudiants et consulter les statistiques de vos classes.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Affichage de la classe active actuelle -->
                    <?php if (isset($_SESSION['classId']) && $_SESSION['classId'] > 0): 
                        // Récupérer le nom de la classe active
                        $activeClassId = $_SESSION['classId'];
                        $activeArmId = $_SESSION['classArmId'] ?? 0;
                        $activeClassQuery = mysqli_query($conn, "
                            SELECT c.className, c.annee, c.specialisation, s.salleName
                            FROM tblclass c 
                            LEFT JOIN tblsalle s ON c.salleId = s.Id
                            WHERE c.Id = '$activeClassId'
                        ");
                        $activeClass = mysqli_fetch_assoc($activeClassQuery);
                        
                        $activeArmName = "Tous les groupes";
                        if ($activeArmId > 0) {
                            $activeArmQuery = mysqli_query($conn, "SELECT classArmName FROM tblclassarms WHERE Id = '$activeArmId'");
                            if ($activeArm = mysqli_fetch_assoc($activeArmQuery)) {
                                $activeArmName = $activeArm['classArmName'];
                            }
                        }
                    ?>
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="active-class-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                <div class="mb-3 mb-md-0">
                                    <span class="text-xs text-uppercase font-weight-bold text-white-50">Classe active sélectionnée</span>
                                    <h4 class="font-weight-bold mb-0 text-white">
                                        <i class="fas fa-graduation-cap mr-2"></i>
                                        <?php echo htmlspecialchars(($activeClass['salleName'] ?? $activeClass['className']) . ' - ' . ($activeClass['annee'] == 1 ? '1ère' : ($activeClass['annee'] == 2 ? '2ème' : '3ème')) . ' (' . $activeClass['specialisation'] . ')'); ?>
                                    </h4>
                                    <p class="mb-0 text-white-50 text-sm mt-1">
                                        <i class="fas fa-users mr-1"></i> Groupe : <strong><?php echo htmlspecialchars($activeArmName); ?></strong>
                                    </p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="takeAttendance.php" class="btn btn-light text-primary btn-action mr-2">
                                        <i class="fas fa-calendar-check mr-1"></i> Faire l'appel
                                    </a>
                                    <a href="viewStudents.php" class="btn btn-outline-light btn-action">
                                        <i class="fas fa-user-graduate mr-1"></i> Voir les élèves
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Stats Row -->
                    <div class="row mb-4">
                        <!-- Total Classes -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card premium-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Classes assignées</div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo count($myClasses); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat-icon bg-light-blue">
                                                <i class="fas fa-door-open"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Students -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card premium-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total élèves suivis</div>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat-icon bg-light-green">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Link Alert -->
                        <div class="col-xl-4 col-md-12 mb-4">
                            <div class="card premium-card h-100" style="background: #fffdf5; border: 1px dashed var(--color-warning);">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Note d'information</div>
                                            <span class="text-xs text-muted d-block">Cliquez sur une classe ci-dessous pour la définir comme active et accéder à sa gestion.</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat-icon bg-light-yellow">
                                                <i class="fas fa-info-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section principales : Liste des classes -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card premium-card">
                                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-book-reader mr-2"></i>Mes Classes Assignées</h6>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($myClasses)): ?>
                                        <div class="alert alert-info text-center py-4 mb-0">
                                            <i class="fas fa-info-circle fa-2x mb-2 d-block text-info"></i>
                                            Aucune classe ne vous a encore été assignée par l'administration.
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach($myClasses as $class): ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card h-100 shadow-sm" style="border-radius: var(--border-radius); overflow: hidden; border: 1px solid #e3e6f0;">
                                                        <div class="card-header bg-light py-3">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <span class="class-badge">
                                                                    <?php echo ($class['annee'] == 1 ? '1ère' : ($class['annee'] == 2 ? '2ème' : '3ème')) . ' Année'; ?>
                                                                </span>
                                                                <small class="text-muted"><i class="fas fa-user mr-1"></i><?php echo $class['studentCount']; ?> élèves</small>
                                                            </div>
                                                            <h5 class="font-weight-bold text-gray-800 mt-2 mb-0">
                                                                <?php echo htmlspecialchars($class['salleName'] ?? $class['className']); ?>
                                                            </h5>
                                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($class['specialisation']); ?></small>
                                                        </div>
                                                        <div class="card-body">
                                                            <h6 class="font-weight-bold text-xs text-uppercase text-muted mb-3">Groupes disponibles (Class Arms)</h6>
                                                            
                                                            <?php if (empty($class['arms'])): ?>
                                                                <p class="text-xs text-warning mb-0">
                                                                    <i class="fas fa-exclamation-triangle mr-1"></i> Aucun groupe configuré.
                                                                </p>
                                                            <?php else: ?>
                                                                <?php foreach($class['arms'] as $arm): ?>
                                                                    <div class="group-item d-flex align-items-center justify-content-between">
                                                                        <div>
                                                                            <strong class="text-gray-800"><?php echo htmlspecialchars($arm['classArmName']); ?></strong>
                                                                            <small class="text-muted d-block text-xs"><?php echo $arm['studentCount']; ?> élèves</small>
                                                                        </div>
                                                                        <div class="dropdown">
                                                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle font-weight-bold" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                Gérer
                                                                            </button>
                                                                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                                                                <a class="dropdown-item" href="index.php?select_class=takeAttendance.php&class_id=<?php echo $class['classId']; ?>&arm_id=<?php echo $arm['Id']; ?>">
                                                                                    <i class="fas fa-calendar-check mr-2 text-success"></i> Faire l'appel
                                                                                </a>
                                                                                <a class="dropdown-item" href="index.php?select_class=viewStudents.php&class_id=<?php echo $class['classId']; ?>&arm_id=<?php echo $arm['Id']; ?>">
                                                                                    <i class="fas fa-user-graduate mr-2 text-primary"></i> Liste des élèves
                                                                                </a>
                                                                                <a class="dropdown-item" href="index.php?select_class=viewAttendance.php&class_id=<?php echo $class['classId']; ?>&arm_id=<?php echo $arm['Id']; ?>">
                                                                                    <i class="fas fa-history mr-2 text-info"></i> Historique présence
                                                                                </a>
                                                                                <a class="dropdown-item" href="index.php?select_class=viewStudentAttendance.php&class_id=<?php echo $class['classId']; ?>&arm_id=<?php echo $arm['Id']; ?>">
                                                                                    <i class="fas fa-user mr-2 text-secondary"></i> Présence par élève
                                                                                </a>
                                                                                <a class="dropdown-item" href="index.php?select_class=downloadRecord.php&class_id=<?php echo $class['classId']; ?>&arm_id=<?php echo $arm['Id']; ?>">
                                                                                    <i class="fas fa-file-excel mr-2 text-warning"></i> Rapport d'aujourd'hui
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
            <!-- Footer -->
        </div>
    </div>
    
    <!-- Bouton Retour en haut -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
<?php endif; ?>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>

</body>
</html>