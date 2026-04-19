<?php
// تحديد نوع الملف كـ JSON ليعرف المتصفح كيفية قراءته
header('Content-Type: application/json');
// استدعاء ملف الاتصال بقاعدة البيانات
require_once '../../includes/db.php';

try {
    // 1. جلب كافة الطلبات من قاعدة البيانات مع دمج معلومات المستخدم (الاسم والبريد)
    $stmt = $pdo->query("
        SELECT o.*, u.full_name as user_name, u.email as user_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();

    // 2. المرور على كل طلب لجلب قائمة المنتجات التابعة له (Order Items)
    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        // تخزين المنتجات داخل مصفوفة باسم items داخل الطلب نفسه
        $order['items'] = $itemStmt->fetchAll();
    }

    // إرسال البيانات النهائية بصيغة JSON
    echo json_encode(["status" => "success", "data" => $orders]);

} catch (Exception $e) {
    // في حال حدوث خطأ يتم إرسال كود 500
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
