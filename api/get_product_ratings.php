<?php
// تحديد نوع المحتوى وتفعيل CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// جلب رقم المنتج من الرابط
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : null;

// التحقق من تزويد المعرف
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
    exit;
}

try {
    // 1. جلب كافة التقييمات الفردية لهذا المنتج مع أسماء المستخدمين الذين قيموا
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as user_name FROM ratings r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$product_id]);
    $ratings = $stmt->fetchAll();

    // 2. حساب متوسط التقييم (Average) وإجمالي عدد التقييمات لهذا المنتج
    $stmt = $pdo->prepare("SELECT AVG(rating) as average_rating, COUNT(*) as total_ratings FROM ratings WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $stats = $stmt->fetch();

    // إرسال البيانات المجمعة للتطبيق
    echo json_encode([
        'status' => 'success',
        'data' => [
            'ratings' => $ratings, // القائمة التفصيلية للتعليقات
            'average_rating' => round($stats['average_rating'], 1), // تقريب التقييم لخانة عشرية واحدة
            'total_ratings' => intval($stats['total_ratings']) // العدد الإجمالي للمقيمين
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
