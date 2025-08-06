<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            sp.*,
            bp.company_name,
            pi.image_path as product_image
        FROM social_posts sp
        JOIN business_profiles bp ON sp.business_id = bp.id
        LEFT JOIN products p ON sp.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE sp.status = 'published' 
        AND (sp.expires_at IS NULL OR sp.expires_at > NOW())
        ORDER BY 
            sp.is_sponsored DESC,
            sp.published_at DESC
        LIMIT 20
    ");
    
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    // Si aucune image de produit, utiliser l'image du post
    foreach ($posts as &$post) {
        if (empty($post['product_image'])) {
            $post['product_image'] = $post['image_path'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des posts'
    ]);
}
?>