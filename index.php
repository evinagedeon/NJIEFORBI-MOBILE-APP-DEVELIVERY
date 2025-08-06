<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUCOCHER - Réseau Commercial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-shopping-cart me-2"></i>LUCOCHER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#marketplace">Marketplace</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#flash-sales">Ventes Flash</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#forums">Forums</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#secteurs">Secteurs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php">Tableau de bord</a></li>
                                <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                                <li><a class="dropdown-item" href="cart.php">Panier</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Bienvenue sur <span class="text-warning">LUCOCHER</span>
                    </h1>
                    <p class="lead text-white mb-4">
                        Le réseau commercial qui révolutionne vos achats en ligne. 
                        Découvrez des produits uniques, participez aux ventes flash et 
                        rejoignez une communauté de consommateurs avertis.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-warning btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i>Rejoindre
                        </a>
                        <a href="#marketplace" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-store me-2"></i>Explorer
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="assets/images/hero-shopping.png" alt="Shopping" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Wall -->
    <section id="social-wall" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Mur Social - Tendances & Promotions</h2>
            <div class="social-wall-container">
                <div id="social-posts" class="row g-4">
                    <!-- Les posts seront chargés dynamiquement via AJAX -->
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-truck fa-3x text-primary"></i>
                        </div>
                        <h5>Livraison à Domicile</h5>
                        <p>Faites vos courses en ligne et recevez vos produits directement chez vous.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-bolt fa-3x text-warning"></i>
                        </div>
                        <h5>Ventes Flash</h5>
                        <p>Participez aux ventes flash, soldes et enchères exclusives.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <h5>Forums Communauté</h5>
                        <p>Rejoignez des forums d'achat selon vos centres d'intérêt.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-store fa-3x text-info"></i>
                        </div>
                        <h5>Marketplace</h5>
                        <p>Explorez tous les secteurs d'activité sur notre plateforme.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>LUCOCHER</h5>
                    <p>Le réseau commercial nouvelle génération pour consommateurs avertis.</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens Utiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">À propos</a></li>
                        <li><a href="#" class="text-white-50">Conditions d'utilisation</a></li>
                        <li><a href="#" class="text-white-50">Politique de confidentialité</a></li>
                        <li><a href="#" class="text-white-50">Support</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p><i class="fas fa-envelope me-2"></i>contact@lucocher.com</p>
                    <p><i class="fas fa-phone me-2"></i>+33 1 23 45 67 89</p>
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter fa-2x"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-instagram fa-2x"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>