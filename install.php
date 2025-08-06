<?php
// Script d'installation automatique pour LUCOCHER
$errors = [];
$success = [];

// Vérifier les prérequis
function checkRequirements() {
    global $errors, $success;
    
    // Version PHP
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = "PHP 8.0+ requis. Version actuelle : " . PHP_VERSION;
    } else {
        $success[] = "PHP " . PHP_VERSION . " ✓";
    }
    
    // Extensions PHP
    $required_extensions = ['pdo', 'pdo_mysql', 'gd', 'json', 'session'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extension PHP manquante : $ext";
        } else {
            $success[] = "Extension $ext ✓";
        }
    }
    
    // Permissions dossiers
    $directories = ['uploads', 'logs', 'config'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Impossible de créer le dossier : $dir";
            } else {
                $success[] = "Dossier $dir créé ✓";
            }
        }
        
        if (!is_writable($dir)) {
            $errors[] = "Dossier non accessible en écriture : $dir";
        } else {
            $success[] = "Permissions $dir ✓";
        }
    }
}

// Configuration base de données
function setupDatabase() {
    global $errors, $success;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_db'])) {
        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? 'lucocher_db';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        
        try {
            // Connexion MySQL
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");
            
            // Exécuter le schéma
            $schema = file_get_contents('database/schema.sql');
            if ($schema) {
                $pdo->exec($schema);
                $success[] = "Base de données créée avec succès ✓";
            } else {
                $errors[] = "Impossible de lire le fichier schema.sql";
            }
            
            // Créer le fichier de configuration
            $config = "<?php
define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user');
define('DB_PASS', '$pass');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Erreur de connexion à la base de données: \" . \$e->getMessage());
}
?>";
            
            if (file_put_contents('config/database.php', $config)) {
                $success[] = "Configuration base de données sauvegardée ✓";
            } else {
                $errors[] = "Impossible de sauvegarder la configuration";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}

// Créer un utilisateur admin
function createAdminUser() {
    global $errors, $success;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
        require_once 'config/database.php';
        require_once 'includes/functions.php';
        
        $email = $_POST['admin_email'];
        $password = $_POST['admin_password'];
        $company_name = $_POST['company_name'];
        
        try {
            $pdo->beginTransaction();
            
            // Créer l'utilisateur admin
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, user_type, status) VALUES (?, ?, 'business', 'active')");
            $stmt->execute([$email, $hashed_password]);
            $user_id = $pdo->lastInsertId();
            
            // Créer le profil entreprise admin
            $stmt = $pdo->prepare("
                INSERT INTO business_profiles 
                (user_id, company_name, business_type, validation_status, products_limit) 
                VALUES (?, ?, 'producer', 'approved', 1000)
            ");
            $stmt->execute([$user_id, $company_name]);
            
            $pdo->commit();
            $success[] = "Compte administrateur créé ✓";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Erreur création admin : " . $e->getMessage();
        }
    }
}

// Vérifier l'installation
checkRequirements();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['setup_db'])) {
        setupDatabase();
    } elseif (isset($_POST['create_admin'])) {
        createAdminUser();
    }
}

$installation_complete = file_exists('config/database.php') && empty($errors);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation LUCOCHER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        .step-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .step-completed {
            border-left-color: #28a745;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="install-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-shopping-cart me-3"></i>LUCOCHER
            </h1>
            <p class="lead">Installation de votre plateforme de commerce</p>
        </div>
    </div>

    <div class="container my-5">
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle me-2"></i>Étapes réussies</h5>
                <ul class="mb-0">
                    <?php foreach ($success as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Installation complète -->
        <?php if ($installation_complete): ?>
            <div class="alert alert-success text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3>Installation terminée !</h3>
                <p class="mb-4">LUCOCHER est maintenant prêt à être utilisé.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Accéder au site
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                    </a>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Étapes d'installation -->
            <div class="row">
                <!-- Étape 1: Prérequis -->
                <div class="col-lg-6 mb-4">
                    <div class="card step-card h-100 <?php echo empty($errors) ? 'step-completed' : ''; ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-server me-2"></i>
                                1. Vérification des prérequis
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($errors)): ?>
                                <p class="text-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Tous les prérequis sont satisfaits !
                                </p>
                            <?php else: ?>
                                <p class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Veuillez corriger les erreurs ci-dessus.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Base de données -->
                <div class="col-lg-6 mb-4">
                    <div class="card step-card h-100 <?php echo file_exists('config/database.php') ? 'step-completed' : ''; ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-database me-2"></i>
                                2. Configuration base de données
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!file_exists('config/database.php')): ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Serveur</label>
                                        <input type="text" class="form-control" name="db_host" value="localhost" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Base de données</label>
                                        <input type="text" class="form-control" name="db_name" value="lucocher_db" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Utilisateur</label>
                                        <input type="text" class="form-control" name="db_user" value="root" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe</label>
                                        <input type="password" class="form-control" name="db_pass">
                                    </div>
                                    <button type="submit" name="setup_db" class="btn btn-primary" <?php echo !empty($errors) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-play me-2"></i>Configurer
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Base de données configurée !
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Compte admin -->
                <?php if (file_exists('config/database.php')): ?>
                    <div class="col-lg-12 mb-4">
                        <div class="card step-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-shield me-2"></i>
                                    3. Créer un compte administrateur
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email administrateur</label>
                                                <input type="email" class="form-control" name="admin_email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Mot de passe</label>
                                                <input type="password" class="form-control" name="admin_password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Nom de l'entreprise</label>
                                                <input type="text" class="form-control" name="company_name" value="LUCOCHER Admin" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="create_admin" class="btn btn-success">
                                        <i class="fas fa-user-plus me-2"></i>Créer le compte admin
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Informations système -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informations système
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Version PHP:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Serveur:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu'; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>OS:</strong> <?php echo PHP_OS; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>