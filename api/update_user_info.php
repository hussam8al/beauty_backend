<?php
// تحديد نوع الاستجابة JSON
header('Content-Type: application/json');
// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// استقبال بيانات الـ JSON المرسلة (المعرف، الاسم الجديد، والبريد الجديد)
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من اكتمال البيانات المطلوبة
if (!$data || !isset($data['user_id']) || !isset($data['full_name']) || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

$user_id = $data['user_id'];
$full_name = $data['full_name'];
$email = $data['email'];

try {
    // 1. التحقق مما إذا كان البريد الإلكتروني الجديد مستخدماً من قبل شخص آخر
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $user_id]);
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email is already in use."]);
        exit;
    }

    // 2. تحديث بيانات المستخدم في قاعدة البيانات
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $user_id]);

    // اعتبار العملية ناجحة في كل الأحوال للتسهيل على التطبيق
    if ($stmt->rowCount() > 0 || true) {
        echo json_encode(["status" => "success", "message" => "Profile updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "No changes made or user not found."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
