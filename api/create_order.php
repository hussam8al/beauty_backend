<?php
// تحديد نوع المحتوى كـ JSON ليعرف التطبيق كيفية التعامل مع النتيجة
header('Content-Type: application/json');
// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// استقبال البيانات الخام (Raw Data) القادمة من جسد الطلب (Request Body) وتحويلها من JSON لمصفوفة PHP
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من وصول البيانات
if (!$data) {
    http_response_code(400); // خطأ 400 يعني طلب غير صالح
    echo json_encode(["status" => "error", "message" => "No data provided."]);
    exit;
}

// التحقق من وجود الحقول الأساسية المطلوبة لإتمام الطلب
if (!isset($data['user_id']) || !isset($data['total_amount']) || !isset($data['items']) || !isset($data['shipping_address']) || !isset($data['phone_number'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// استخراج البيانات وتخزينها في متغيرات سهلة الاستخدام
$user_id = $data['user_id'];
$total_amount = $data['total_amount'];
$currency = $data['currency'] ?? 'ر.س'; // استخدام "ريال سعودي" كعملة افتراضية إذا لم ترسل
$shipping_address = $data['shipping_address'];
$phone_number = $data['phone_number'];
$latitude = $data['latitude'] ?? null; // إحداثيات الموقع (اختياري)
$longitude = $data['longitude'] ?? null;
$payment_method = $data['payment_method'] ?? 'Cash on Delivery'; // طريقة الدفع
$items = $data['items']; // قائمة المنتجات المطلوبة

// التأكد من أن قائمة المنتجات ليست فارغة
if (!is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Order must contain at least one item."]);
    exit;
}

try {
    // بدء "عملية مركبة" (Transaction) لضمان حفظ الطلب ومنتجاته معاً، أو إلغاء الكل في حال حدوث خطأ
    $pdo->beginTransaction();

    // 1. إدراج بيانات الطلب الأساسية في جدول orders
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, currency, shipping_address, phone_number, latitude, longitude, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $total_amount, $currency, $shipping_address, $phone_number, $latitude, $longitude, $payment_method]);
    
    // جلب رقم المعرف (ID) الخاص بالطلب الذي تم إنشاؤه للتو لاستخدامه في ربط المنتجات
    $order_id = $pdo->lastInsertId();

    // 2. إدراج كل منتج في الطلب داخل جدول order_items (تفاصيل الطلب)
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, currency) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        // التحقق من صحة بيانات المنتج الواحد
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception("Invalid item data.");
        }
        $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $item['currency'] ?? 'ر.س']);
    }

    // تأكيد حفظ كافة العمليات في قاعدة البيانات
    $pdo->commit();

    // إرسال رد النجاح للتطبيق مع رقم الطلب
    echo json_encode([
        "status" => "success",
        "message" => "Order placed successfully.",
        "order_id" => $order_id
    ]);

} catch (Exception $e) {
    // في حال حدوث أي خطأ، يتم التراجع عن جميع العمليات (Rollback) لمنع وجود طلب بدون منتجات
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
