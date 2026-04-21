<?php
// تحديد نوع الاستجابة بصيغة JSON
header('Content-Type: application/json');
// استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// جلب رقم المستخدم من رابط الـ API
$user_id = $_GET['user_id'] ?? null;

// التحقق من أن رقم المستخدم موجود
if (!$user_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

try {
    // جلب كافة الإشعارات الخاصة بالمستخدم (مثل إشعارات تغير حالة الطلب) مرتبة من الأحدث للأقدم
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // إرسال الإشعارات للتطبيق
    echo json_encode(["status" => "success", "data" => $notifications]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
