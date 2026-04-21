<?php
// ترويسات السماح بالتراسل من مصادر مختلفة (CORS) وتحديد نوع المحتوى JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// تضمين ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

// جلب كلمة البحث (q) ومعرف المستخدم (user_id) من الرابط
$query = isset($_GET['q']) ? $_GET['q'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// إذا كانت كلمة البحث فارغة، نعيد مصفوفة فارغة فوراً
if (empty($query)) {
    echo json_encode(['status' => 'success', 'data' => []]);
    exit;
}

try {
    // التحقق من حالة "المفضلة" للمنتجات إذا تم توفير معرف المستخدم
    $fav_query = $user_id ? ", (SELECT COUNT(*) FROM favorites WHERE user_id = " . intval($user_id) . " AND product_id = p.id) as is_favorite" : ", 0 as is_favorite";
    
    // استعلام البحث باستخدام LIKE للبحث الجزئي في اسم المنتج، وصفه، أو اسم القسم
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name $fav_query FROM products p JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? ORDER BY p.created_at DESC");
    
    // تجهيز معامل البحث ليحتوي على علامات % (Wildcards) للبحث عن الكلمة في أي مكان بالنص
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();

    // إرسال نتائج البحث
    echo json_encode([
        'status' => 'success',
        'data' => $products
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
