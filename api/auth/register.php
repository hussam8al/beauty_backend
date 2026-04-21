<?php
// تحديد نوع الاستجابة كـ JSON
header('Content-Type: application/json');
// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/../../includes/db.php';

// جلب البيانات المرسلة من التطبيق
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من إدخال جميع البيانات المطلوبة لعملية التسجيل
if (!isset($data['full_name']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

$full_name = $data['full_name'];
$email = $data['email'];
// تشفير كلمة المرور باستخدام خوارزمية افتراضية قوية قبل تخزينها
$password = password_hash($data['password'], PASSWORD_DEFAULT);

try {
    // إدراج بيانات المستخدم الجديد في جدول users
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$full_name, $email, $password])) {
        // جلب معرف العضوية الجديد
        $userId = $pdo->lastInsertId();
        $user = [
            "id" => $userId,
            "full_name" => $full_name,
            "email" => $email
        ];
        // إرسال رد النجاح مع بيانات المستخدم الجديدة
        echo json_encode(["status" => "success", "message" => "User registered successfully.", "user" => $user]);
    } else {
        echo json_encode(["status" => "error", "message" => "Registration failed."]);
    }
} catch (PDOException $e) {
    // التحقق من كود الخطأ 23000 والذي يعني وجود بريد مكرر (Duplicate Entry)
    if ($e->getCode() == 23000) {
        http_response_code(409); // خطأ 409 يعني تعارض في البيانات
        echo json_encode(["status" => "error", "message" => "Email already exists."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
