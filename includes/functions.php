<?php
// Fonctions utilitaires pour LUCOCHER

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est une entreprise
function isBusiness() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business';
}

// Vérifier si l'utilisateur est un consommateur
function isConsumer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'consumer';
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Rediriger si non autorisé pour les entreprises
function requireBusiness() {
    requireLogin();
    if (!isBusiness()) {
        header('Location: index.php');
        exit;
    }
}

// Rediriger si non autorisé pour les consommateurs
function requireConsumer() {
    requireLogin();
    if (!isConsumer()) {
        header('Location: index.php');
        exit;
    }
}

// Nettoyer les données d'entrée
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Valider l'email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hacher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Générer un token aléatoire
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

// Calculer le pourcentage de réduction
function calculateDiscount($original_price, $current_price) {
    if ($original_price <= 0) return 0;
    return round((($original_price - $current_price) / $original_price) * 100);
}

// Générer un numéro de commande unique
function generateOrderNumber() {
    return 'LC' . date('Y') . date('m') . date('d') . rand(1000, 9999);
}

// Obtenir l'avatar par défaut
function getDefaultAvatar($name) {
    $initial = strtoupper(substr($name, 0, 1));
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
    $color = $colors[ord($initial) % count($colors)];
    
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=" . substr($color, 1) . "&color=fff&size=150";
}

// Temps écoulé depuis une date
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'À l\'instant';
    if ($time < 3600) return floor($time/60) . ' min';
    if ($time < 86400) return floor($time/3600) . ' h';
    if ($time < 2592000) return floor($time/86400) . ' j';
    if ($time < 31536000) return floor($time/2592000) . ' mois';
    return floor($time/31536000) . ' an' . (floor($time/31536000) > 1 ? 's' : '');
}

// Tronquer le texte
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// Obtenir les informations utilisateur
function getUserInfo($user_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COALESCE(cp.first_name, bp.company_name) as display_name,
               cp.first_name, cp.last_name,
               bp.company_name, bp.business_type
        FROM users u 
        LEFT JOIN consumer_profiles cp ON u.id = cp.user_id 
        LEFT JOIN business_profiles bp ON u.id = bp.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Obtenir le nombre d'articles dans le panier
function getCartCount($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Obtenir les notifications non lues
function getUnreadNotifications($user_id, $pdo, $limit = 5) {
    try {
        // Valider et sécuriser la limite
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 100) {
            $limit = 5;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = FALSE 
            ORDER BY created_at DESC 
            LIMIT " . $limit
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Erreur getUnreadNotifications: " . $e->getMessage());
        return []; // Retourner un tableau vide en cas d'erreur
    }
}

// Marquer une notification comme lue
function markNotificationAsRead($notification_id, $user_id, $pdo) {
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = TRUE 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$notification_id, $user_id]);
}

// Ajouter une notification
function addNotification($user_id, $type, $title, $message, $action_url = null, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, action_url) 
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $type, $title, $message, $action_url]);
}

// Obtenir les secteurs actifs
function getActiveSectors($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM sectors WHERE is_active = TRUE ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Obtenir les catégories d'un secteur
function getSectorCategories($sector_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE sector_id = ? AND is_active = TRUE 
        ORDER BY name
    ");
    $stmt->execute([$sector_id]);
    return $stmt->fetchAll();
}

// Vérifier les permissions d'entreprise
function checkBusinessPermission($user_id, $business_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT bp.id FROM business_profiles bp 
        JOIN users u ON bp.user_id = u.id 
        WHERE u.id = ? AND bp.id = ?
    ");
    $stmt->execute([$user_id, $business_id]);
    return $stmt->fetch() !== false;
}

// Upload d'image
function uploadImage($file, $directory = 'uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Paramètres invalides.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('Aucun fichier envoyé.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Fichier trop volumineux.');
        default:
            throw new RuntimeException('Erreur inconnue.');
    }

    if ($file['size'] > 5000000) { // 5MB
        throw new RuntimeException('Fichier trop volumineux.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    if (!in_array($mimeType, $allowedTypes)) {
        throw new RuntimeException('Format de fichier non autorisé.');
    }

    $extension = array_search($mimeType, [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ]);

    $filename = sprintf('%s.%s', sha1_file($file['tmp_name']), $extension);
    $destination = $directory . $filename;

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible d\'enregistrer le fichier.');
    }

    return $destination;
}

// Redimensionner une image
function resizeImage($source, $destination, $width, $height) {
    list($original_width, $original_height, $type) = getimagesize($source);
    
    $ratio_orig = $original_width / $original_height;
    
    if ($width / $height > $ratio_orig) {
        $width = $height * $ratio_orig;
    } else {
        $height = $width / $ratio_orig;
    }

    $image_resized = imagecreatetruecolor($width, $height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            imagealphablending($image_resized, false);
            imagesavealpha($image_resized, true);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($image_resized, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($image_resized, $destination);
            break;
        case IMAGETYPE_GIF:
            imagegif($image_resized, $destination);
            break;
    }

    imagedestroy($image);
    imagedestroy($image_resized);
    
    return true;
}

// Envoyer un email (configuration basique)
function sendEmail($to, $subject, $message, $from = 'noreply@lucocher.com') {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: LUCOCHER <$from>" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Logger les erreurs
function logError($message, $file = 'error.log') {
    try {
        // Créer le répertoire logs s'il n'existe pas
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message" . PHP_EOL;
        error_log($log_message, 3, "logs/$file");
    } catch (Exception $e) {
        // En cas d'erreur de logging, utiliser le log système
        error_log("LUCOCHER Error: $message");
    }
}

// Pagination
function paginate($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}

// Générer des liens de pagination
function generatePaginationLinks($pagination, $base_url) {
    $links = [];
    
    if ($pagination['has_prev']) {
        $links[] = '<a href="' . $base_url . '?page=' . $pagination['prev_page'] . '" class="btn btn-outline-primary">&laquo; Précédent</a>';
    }
    
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $class = ($i == $pagination['current_page']) ? 'btn btn-primary' : 'btn btn-outline-primary';
        $links[] = '<a href="' . $base_url . '?page=' . $i . '" class="' . $class . '">' . $i . '</a>';
    }
    
    if ($pagination['has_next']) {
        $links[] = '<a href="' . $base_url . '?page=' . $pagination['next_page'] . '" class="btn btn-outline-primary">Suivant &raquo;</a>';
    }
    
    return implode(' ', $links);
}
?>