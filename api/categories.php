<?php
// ضبط ترويسات الاستجابة لتعود بصيغة JSON والسماح بالوصول الخارجي
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// استدعاء ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// ضبط إعدادات تقارير الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // استعلام لجلب جميع الأقسام مرتبة أبجدياً حسب الاسم
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    
    // تسجيل عملية الدخول في ملف لوق (log.txt) للتأكد من عمل الـ API وكمية البيانات المسترجعة
    file_put_contents('log.txt', date('Y-m-d H:i:s') . " - Categories count: " . count($categories) . "\n", FILE_APPEND);
    
    // إرسال النتيجة الناجحة للتطبيق
    echo json_encode([
        'status' => 'success',
        'data' => $categories
    ]);
} catch (Exception $e) {
    // تسجيل أي خطأ يحدث في ملف اللوق لسهولة تتبعه
    file_put_contents('log.txt', date('Y-m-d H:i:s') . " - API Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
