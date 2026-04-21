<?php
// إرسال البيانات بصيغة JSON والسماح لكافة التطبيقات بالوصول
header('Content-Type: application/json');
// الرجوع مجلدين للخلف للوصول لملف قاعدة البيانات
require_once __DIR__ . '/../../includes/db.php';

// استقبال بيانات الـ JSON المرسلة (البريد وكلمة المرور)
$data = json_decode(file_get_contents("php://input"), true);

// التأكد من أن المستخدم أدخل البريد وكلمة المرور
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    // 1. البحث عن المستخدم في قاعدة البيانات باستخدام البريد الإلكتروني
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. التحقق من وجود المستخدم ومطابقة كلمة المرور المشفرة
    // دالة password_verify تقارن كلمة المرور المدخلة مع الهاش (Hash) المخزن
    if ($user && password_verify($password, $user['password'])) {
        // إزالة كلمة المرور من بيانات المستخدم قبل إرسالها للتطبيق لزيادة الأمان
        unset($user['password']);
        echo json_encode(["status" => "success", "message" => "Login successful.", "user" => $user]);
    } else {
        // إظهار خطأ 401 في حال كانت البيانات غير صحيحة
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
