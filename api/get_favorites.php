<?php
// تحديد نوع الملف كـ JSON
header('Content-Type: application/json');
// استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// الحصول على رقم المستخدم من الرابط (GET Parameter)
$user_id = $_GET['user_id'] ?? null;

// التحقق من تزويد الـ API برقم المستخدم
if (!$user_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

try {
    // استعلام لجلب كافة المنتجات الموجودة في قائمة مفضلة هذا المستخدم حصراً
    // نستخدم JOIN لربط جدول favorites بجدول products
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM favorites f 
        JOIN products p ON f.product_id = p.id 
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll();

    // إرسال قائمة المنتجات المفضلة للتطبيق
    echo json_encode(["status" => "success", "data" => $favorites]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
