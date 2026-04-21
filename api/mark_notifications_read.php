<?php
// تحديد نوع الاستجابة JSON
header('Content-Type: application/json');
// تضمين ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// استقبال بيانات الـ JSON (معرف المستخدم ورقم الإشعار) عبر POST
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
$notification_id = $data['id'] ?? null; // إذا كان فارغاً، سيتم اعتبار كافة إشعارات المستخدم كمقروءة

// التحقق من تزويد معرف المستخدم
if (!$user_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

try {
    if ($notification_id) {
        // تحديث إشعار واحد فقط كمقروء (is_read = 1)
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
    } else {
        // تحديث كافة إشعارات المستخدم لتصبح مقروءة في حال عدم تحديد إشعار معين
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    echo json_encode(["status" => "success", "message" => "Notifications marked as read."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
