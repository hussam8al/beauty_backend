<?php
// تحديد نوع الاستجابة وتفعيل CORS للسماح بالتطبيق بالوصول
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// تضمين ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// معالجة طلبات OPTIONS الخاصة بمتصفحات الويب (Preflight requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// استقبال بيانات الـ JSON وتحويلها لمصفوفة
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من أن المستخدم أرسل الحقول الإلزامية (المعرف، المنتج، والتقييم)
if (!isset($data['user_id']) || !isset($data['product_id']) || !isset($data['rating'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$user_id = $data['user_id'];
$product_id = $data['product_id'];
$rating = $data['rating'];
$comment = isset($data['comment']) ? $data['comment'] : ''; // التعليق اختياري

try {
    // 1. التحقق من وجود المنتج فعلياً قبل إضافة تقييم له
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }

    // 2. استخدام خاصية ON DUPLICATE KEY UPDATE لحفظ التقييم
    // إذا كان المستخدم قد قيم المنتج سابقاً، يتم تحديث تقييمه، وإلا يتم إنشاء تقييم جديد
    $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating, comment) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);

    echo json_encode(['status' => 'success', 'message' => 'Rating submitted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
