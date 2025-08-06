<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise']);
    exit;
}

if (!isConsumer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès réservé aux consommateurs']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    // Vérifier que le produit existe et est disponible
    $stmt = $pdo->prepare("
        SELECT p.*, bp.company_name 
        FROM products p 
        JOIN business_profiles bp ON p.business_id = bp.id 
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produit non disponible']);
        exit;
    }
    
    // Vérifier le stock
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
        exit;
    }
    
    // Ajouter ou mettre à jour le panier
    $stmt = $pdo->prepare("
        INSERT INTO carts (user_id, product_id, quantity) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    
    $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté au panier',
        'cart_count' => getCartCount($_SESSION['user_id'], $pdo)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout au panier'
    ]);
    logError("Add to cart error: " . $e->getMessage());
}
?>