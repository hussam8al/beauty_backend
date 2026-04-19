<?php
// تحديد نوع الملف كـ JSON
header('Content-Type: application/json');
// استدعاء ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// استقبال بيانات الـ JSON وتحويلها لمصفوفة
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من وصول المعرفات المطلوبة (المستخدم والمنتج)
if (!$data || !isset($data['user_id']) || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing user_id or product_id."]);
    exit;
}

$user_id = $data['user_id'];
$product_id = $data['product_id'];

try {
    // 1. التحقق مما إذا كان المنتج موجوداً مسبقاً في مفضلة المستخدم
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $fav = $stmt->fetch();

    if ($fav) {
        // 2. إذا كان موجوداً، نقوم بحذفه (إلغاء الإعجاب)
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(["status" => "success", "action" => "removed", "message" => "Removed from favorites."]);
    } else {
        // 3. إذا لم يكن موجوداً، نقوم بإضافته (إعجاب)
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(["status" => "success", "action" => "added", "message" => "Added to favorites."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
