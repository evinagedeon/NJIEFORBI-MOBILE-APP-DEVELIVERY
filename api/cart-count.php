<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'count' => 0
        ]);
        exit;
    }
    
    $count = getCartCount($_SESSION['user_id'], $pdo);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du panier'
    ]);
}
?>