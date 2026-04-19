<?php
// تحديد نوع الاستجابة وتضمين ملف القاعدة
header('Content-Type: application/json');
require_once '../../includes/db.php';

// استقبال بيانات الـ JSON (معرف الطلب، الحالة الجديدة، وسبب الرفض أو موعد التوصيل)
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من الحقول الأساسية
if (!isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Order ID and status are required."]);
    exit;
}

$order_id = $data['order_id'];
$status = $data['status'];
$rejection_reason = $data['rejection_reason'] ?? null;
$estimated_delivery = $data['estimated_delivery'] ?? null;

// مصفوفة بالحالات المسموح بها في النظام
$allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $allowed)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid status."]);
    exit;
}

try {
    // بدء Transaction لضمان تحديث الطلب وإرسال الإشعار معاً
    $pdo->beginTransaction();

    // 1. تحديث حالة الطلب في قاعدة البيانات
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, rejection_reason = ?, estimated_delivery = ? WHERE id = ?");
    $stmt->execute([$status, $rejection_reason, $estimated_delivery, $order_id]);

    // 2. جلب معرف المستخدم صاحب الطلب لإرسال إشعار له بالتحديث
    $userStmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
    $userStmt->execute([$order_id]);
    $user = $userStmt->fetch();

    if ($user) {
        $user_id = $user['user_id'];
        $title = "تحديث لطلبك رقم #$order_id";
        $message = "";

        // صياغة رسالة الإشعار بناءً على الحالة الجديدة
        if ($status === 'delivered') {
            $message = "تمت الموافقة على طلبك بنجاح! موعد الوصول المتوقع: $estimated_delivery";
        } else if ($status === 'cancelled') {
            $message = "نأسف، تم إلغاء طلبك. السبب: $rejection_reason";
        } else {
            $message = "حالة طلبك الآن هي: " . ($status === 'processing' ? 'جاري التجهيز' : ($status === 'shipped' ? 'تم الشحن' : $status));
        }

        // 3. إدراج الإشعار في جدول notifications ليظهر في تطبيق الموبايل
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $notifStmt->execute([$user_id, $title, $message]);
    }

    // تأكيد العمليات
    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Order updated successfully and notification sent."]);

} catch (Exception $e) {
    // التراجع في حال حدوث خطأ
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
