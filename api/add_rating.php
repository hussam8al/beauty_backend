<?php
// تحديد نوع الاستجابة وتفعيل CORS للسماح بالتطبيق بالوصول
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db.php';

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// استقبال البيانات
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من الحقول الإلزامية
if (!isset($data['user_id']) || !isset($data['product_id']) || !isset($data['rating'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$user_id    = intval($data['user_id']);
$product_id = intval($data['product_id']);
$rating     = intval($data['rating']);
$comment    = isset($data['comment']) ? trim($data['comment']) : '';

// التحقق أن التقييم بين 1 و 5
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    // 1. التحقق من وجود المنتج
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }

    // 2. هل سبق وقيّم هذا المستخدم المنتج؟
    $checkStmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$user_id, $product_id]);
    $existingRating = $checkStmt->fetch();

    if ($existingRating) {
        // المستخدم قيّم مسبقاً → تحديث التقييم القديم
        $updateStmt = $pdo->prepare("UPDATE ratings SET rating = ?, comment = ? WHERE user_id = ? AND product_id = ?");
        $updateStmt->execute([$rating, $comment, $user_id, $product_id]);
        $action = 'updated';
    } else {
        // تقييم جديد → إضافة
        $insertStmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$user_id, $product_id, $rating, $comment]);
        $action = 'added';
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Rating submitted successfully',
        'action'  => $action
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
