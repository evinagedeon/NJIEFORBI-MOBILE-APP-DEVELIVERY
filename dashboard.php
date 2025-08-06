<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id'], $pdo);
$is_business = isBusiness();
$is_consumer = isConsumer();

// Obtenir les statistiques selon le type d'utilisateur
if ($is_business) {
    // Statistiques entreprise
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(p.id) as total_products,
            COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products,
            COALESCE(SUM(oi.quantity), 0) as total_sales,
            COALESCE(SUM(oi.total_price), 0) as total_revenue
        FROM business_profiles bp
        LEFT JOIN products p ON bp.id = p.business_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        WHERE bp.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $business_stats = $stmt->fetch();
    
    // Commandes récentes
    $stmt = $pdo->prepare("
        SELECT o.*, cp.first_name, cp.last_name, COUNT(oi.id) as items_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN business_profiles bp ON p.business_id = bp.id
        JOIN consumer_profiles cp ON o.user_id = cp.user_id
        WHERE bp.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
    
} else {
    // Statistiques consommateur
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            COUNT(f.id) as favorites_count,
            COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        LEFT JOIN favorites f ON u.id = f.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $consumer_stats = $stmt->fetch();
    
    // Commandes récentes
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
}

// Notifications non lues
$notifications = getUnreadNotifications($_SESSION['user_id'], $pdo, 5);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - LUCOCHER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-shopping-cart me-2"></i>LUCOCHER
            </a>
            
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-outline-light position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo count($notifications); ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <?php if (empty($notifications)): ?>
                            <li><span class="dropdown-item-text text-muted">Aucune notification</span></li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $notification['action_url'] ?: '#'; ?>">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-circle text-primary me-2 mt-1" style="font-size: 0.5rem;"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fs-6"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <p class="mb-1 text-muted small"><?php echo truncateText($notification['message'], 50); ?></p>
                                                <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="notifications.php">Voir toutes les notifications</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user_info['display_name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <?php if ($is_consumer): ?>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>Mes commandes</a></li>
                            <li><a class="dropdown-item" href="favorites.php"><i class="fas fa-heart me-2"></i>Favoris</a></li>
                        <?php endif; ?>
                        <?php if ($is_business): ?>
                            <li><a class="dropdown-item" href="products.php"><i class="fas fa-box me-2"></i>Mes produits</a></li>
                            <li><a class="dropdown-item" href="business-orders.php"><i class="fas fa-chart-line me-2"></i>Ventes</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                            </a>
                            
                            <?php if ($is_consumer): ?>
                                <a href="marketplace.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-store me-2"></i>Marketplace
                                </a>
                                <a href="cart.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shopping-cart me-2"></i>Panier
                                    <span class="badge bg-primary cart-count ms-auto"><?php echo getCartCount($_SESSION['user_id'], $pdo); ?></span>
                                </a>
                                <a href="orders.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shopping-bag me-2"></i>Mes commandes
                                </a>
                                <a href="favorites.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-heart me-2"></i>Favoris
                                </a>
                                <a href="forums.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-users me-2"></i>Forums
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($is_business): ?>
                                <a href="products.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-box me-2"></i>Mes produits
                                </a>
                                <a href="add-product.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-plus me-2"></i>Ajouter un produit
                                </a>
                                <a href="business-orders.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-line me-2"></i>Ventes
                                </a>
                                <a href="social-posts.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-bullhorn me-2"></i>Posts sociaux
                                </a>
                                <a href="analytics.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-analytics me-2"></i>Statistiques
                                </a>
                            <?php endif; ?>
                            
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Welcome Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Bienvenue, <?php echo htmlspecialchars($user_info['display_name']); ?>!</h1>
                        <p class="text-muted">
                            <?php if ($is_business): ?>
                                Gérez votre boutique et vos produits
                            <?php else: ?>
                                Découvrez les meilleures offres et promotions
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($is_business): ?>
                            <a href="add-product.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ajouter un produit
                            </a>
                        <?php else: ?>
                            <a href="marketplace.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Faire ses courses
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <?php if ($is_business): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-box fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $business_stats['total_products']; ?></span>
                                    <div>Produits</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-success">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $business_stats['active_products']; ?></span>
                                    <div>Actifs</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-warning">
                                <div class="card-body">
                                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $business_stats['total_sales']; ?></span>
                                    <div>Ventes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-info">
                                <div class="card-body">
                                    <i class="fas fa-euro-sign fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo formatPrice($business_stats['total_revenue']); ?></span>
                                    <div>Revenus</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center">
                                <div class="card-body">
                                    <i class="fas fa-shopping-bag fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $consumer_stats['total_orders']; ?></span>
                                    <div>Commandes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-success">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $consumer_stats['delivered_orders']; ?></span>
                                    <div>Livrées</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-warning">
                                <div class="card-body">
                                    <i class="fas fa-euro-sign fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo formatPrice($consumer_stats['total_spent']); ?></span>
                                    <div>Dépensé</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card text-center bg-danger">
                                <div class="card-body">
                                    <i class="fas fa-heart fa-2x mb-2"></i>
                                    <span class="stat-number"><?php echo $consumer_stats['favorites_count']; ?></span>
                                    <div>Favoris</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo $is_business ? 'Commandes récentes' : 'Mes dernières commandes'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">
                                            <?php echo $is_business ? 'Aucune commande pour le moment' : 'Aucune commande passée'; ?>
                                        </p>
                                        <?php if (!$is_business): ?>
                                            <a href="marketplace.php" class="btn btn-primary">Commencer vos achats</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>N° Commande</th>
                                                    <?php if ($is_business): ?>
                                                        <th>Client</th>
                                                    <?php endif; ?>
                                                    <th>Articles</th>
                                                    <th>Montant</th>
                                                    <th>Statut</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                        <?php if ($is_business): ?>
                                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                        <?php endif; ?>
                                                        <td><?php echo $order['items_count']; ?></td>
                                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo match($order['status']) {
                                                                    'pending' => 'warning',
                                                                    'confirmed' => 'info',
                                                                    'processing' => 'primary',
                                                                    'shipped' => 'secondary',
                                                                    'delivered' => 'success',
                                                                    'cancelled' => 'danger',
                                                                    default => 'secondary'
                                                                };
                                                            ?>">
                                                                <?php 
                                                                echo match($order['status']) {
                                                                    'pending' => 'En attente',
                                                                    'confirmed' => 'Confirmée',
                                                                    'processing' => 'En cours',
                                                                    'shipped' => 'Expédiée',
                                                                    'delivered' => 'Livrée',
                                                                    'cancelled' => 'Annulée',
                                                                    default => 'Inconnue'
                                                                };
                                                                ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Actions rapides
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($is_business): ?>
                                        <a href="add-product.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Ajouter un produit
                                        </a>
                                        <a href="social-posts.php" class="btn btn-outline-primary">
                                            <i class="fas fa-bullhorn me-2"></i>Créer un post
                                        </a>
                                        <a href="business-orders.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-chart-line me-2"></i>Voir les ventes
                                        </a>
                                    <?php else: ?>
                                        <a href="marketplace.php" class="btn btn-primary">
                                            <i class="fas fa-store me-2"></i>Marketplace
                                        </a>
                                        <a href="flash-sales.php" class="btn btn-outline-warning">
                                            <i class="fas fa-bolt me-2"></i>Ventes Flash
                                        </a>
                                        <a href="forums.php" class="btn btn-outline-info">
                                            <i class="fas fa-users me-2"></i>Forums
                                        </a>
                                        <a href="cart.php" class="btn btn-outline-success">
                                            <i class="fas fa-shopping-cart me-2"></i>Mon panier
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Account Status -->
                        <?php if ($is_business && $user_info['validation_status'] === 'pending'): ?>
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>Statut du compte
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">Votre compte entreprise est en cours de validation.</p>
                                    <small class="text-muted">
                                        Vous pourrez publier des produits une fois votre compte approuvé.
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>