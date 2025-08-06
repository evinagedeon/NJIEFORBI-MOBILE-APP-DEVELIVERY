<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = sanitize($_POST['user_type']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation commune
    if (empty($email) || empty($password) || empty($user_type)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!validateEmail($email)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $pdo->beginTransaction();
                
                // Créer l'utilisateur
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, user_type, status) VALUES (?, ?, ?, 'active')");
                $stmt->execute([$email, $hashed_password, $user_type]);
                $user_id = $pdo->lastInsertId();
                
                if ($user_type === 'consumer') {
                    // Créer le profil consommateur
                    $first_name = sanitize($_POST['first_name']);
                    $last_name = sanitize($_POST['last_name']);
                    $phone = sanitize($_POST['phone']);
                    
                    if (empty($first_name) || empty($last_name)) {
                        throw new Exception('Prénom et nom requis pour les consommateurs.');
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO consumer_profiles (user_id, first_name, last_name, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $first_name, $last_name, $phone]);
                    
                } elseif ($user_type === 'business') {
                    // Créer le profil entreprise
                    $company_name = sanitize($_POST['company_name']);
                    $business_type = sanitize($_POST['business_type']);
                    $siret = sanitize($_POST['siret']);
                    $phone = sanitize($_POST['phone']);
                    $description = sanitize($_POST['description']);
                    
                    if (empty($company_name) || empty($business_type)) {
                        throw new Exception('Nom de l\'entreprise et type d\'activité requis.');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO business_profiles 
                        (user_id, company_name, business_type, siret, phone, description, validation_status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$user_id, $company_name, $business_type, $siret, $phone, $description]);
                }
                
                $pdo->commit();
                
                // Connexion automatique
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['user_name'] = ($user_type === 'consumer') ? $first_name : $company_name;
                
                // Notification de bienvenue
                $welcome_message = ($user_type === 'consumer') ? 
                    "Bienvenue sur LUCOCHER ! Découvrez nos produits et promotions exclusives." :
                    "Bienvenue sur LUCOCHER ! Votre compte entreprise est en cours de validation.";
                
                addNotification($user_id, 'system', 'Bienvenue !', $welcome_message, null, $pdo);
                
                header('Location: dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Erreur lors de la création du compte: ' . $e->getMessage();
            logError("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LUCOCHER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-header text-center py-4">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-shopping-cart text-primary me-2"></i>
                            <strong>LUCOCHER</strong>
                        </h1>
                        <p class="text-muted mt-2">Créez votre compte</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php" id="registrationForm">
                            <!-- Type d'utilisateur -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user-tag text-muted me-2"></i>Type de compte
                                </label>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="card h-100 user-type-card" data-type="consumer">
                                            <div class="card-body text-center p-3">
                                                <i class="fas fa-user fa-2x text-primary mb-2"></i>
                                                <h6>Consommateur</h6>
                                                <small class="text-muted">Acheter des produits, participer aux ventes flash</small>
                                                <input type="radio" name="user_type" value="consumer" class="form-check-input d-none" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="card h-100 user-type-card" data-type="business">
                                            <div class="card-body text-center p-3">
                                                <i class="fas fa-building fa-2x text-success mb-2"></i>
                                                <h6>Entreprise</h6>
                                                <small class="text-muted">Vendre des produits, gérer votre boutique</small>
                                                <input type="radio" name="user_type" value="business" class="form-check-input d-none" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations communes -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope text-muted me-2"></i>Email *
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">
                                            <i class="fas fa-phone text-muted me-2"></i>Téléphone
                                        </label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock text-muted me-2"></i>Mot de passe *
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <small class="text-muted">Minimum 6 caractères</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock text-muted me-2"></i>Confirmer le mot de passe *
                                        </label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Champs spécifiques aux consommateurs -->
                            <div id="consumer-fields" class="user-specific-fields" style="display: none;">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Informations personnelles
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">Prénom *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Nom *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Champs spécifiques aux entreprises -->
                            <div id="business-fields" class="user-specific-fields" style="display: none;">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-building me-2"></i>Informations entreprise
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label">Nom de l'entreprise *</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                                   value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="siret" class="form-label">SIRET</label>
                                            <input type="text" class="form-control" id="siret" name="siret" 
                                                   value="<?php echo isset($_POST['siret']) ? htmlspecialchars($_POST['siret']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="business_type" class="form-label">Type d'activité *</label>
                                    <select class="form-select" id="business_type" name="business_type">
                                        <option value="">Sélectionnez...</option>
                                        <option value="producer" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'producer') ? 'selected' : ''; ?>>
                                            Producteur
                                        </option>
                                        <option value="importer" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'importer') ? 'selected' : ''; ?>>
                                            Importateur
                                        </option>
                                        <option value="retailer" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'retailer') ? 'selected' : ''; ?>>
                                            Revendeur
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description de l'activité</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    J'accepte les <a href="#" class="text-primary">conditions d'utilisation</a> et la 
                                    <a href="#" class="text-primary">politique de confidentialité</a>
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Créer mon compte
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-2">Déjà un compte ?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeCards = document.querySelectorAll('.user-type-card');
            const userSpecificFields = document.querySelectorAll('.user-specific-fields');
            
            // Gestion de la sélection du type d'utilisateur
            userTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    const radio = this.querySelector('input[type="radio"]');
                    
                    // Désélectionner toutes les cartes
                    userTypeCards.forEach(c => c.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10'));
                    
                    // Sélectionner la carte cliquée
                    this.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                    radio.checked = true;
                    
                    // Masquer tous les champs spécifiques
                    userSpecificFields.forEach(field => field.style.display = 'none');
                    
                    // Afficher les champs correspondants
                    const targetFields = document.getElementById(type + '-fields');
                    if (targetFields) {
                        targetFields.style.display = 'block';
                    }
                });
            });

            // Validation du mot de passe
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>