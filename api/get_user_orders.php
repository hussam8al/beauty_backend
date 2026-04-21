<?php
// تحديد نوع الملف كـ JSON
header('Content-Type: application/json');
// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// جلب رقم المستخدم من الرابط
$user_id = $_GET['user_id'] ?? null;

// التحقق من وجود رقم المستخدم
if (!$user_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

try {
    // 1. جلب بيانات الطلبات الخاصة بالمستخدم مرتبة من الأحدث
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    // 2. المرور على كل طلب لجلب المنتجات التابعة له (Order Items)
    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        // تجميع المنتجات داخل مصفوفة تابعة لكل طلب
        $order['items'] = $itemStmt->fetchAll();
    }

    // إرسال قائمة الطلبات المفصلة للتطبيق
    echo json_encode(["status" => "success", "data" => $orders]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
