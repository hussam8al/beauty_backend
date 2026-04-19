<?php
// ضبط ترويسات الاستجابة لتعود بصيغة JSON والسماح بالوصول من مصادر مختلفة (CORS) لتمكين التطبيق من جلب البيانات
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // السماح لأي مصدر بطلب البيانات
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';

// ضبط إعدادات تقارير الأخطاء (إخفاء الأخطاء المباشرة عن المستخدم النهائي وإظهارها للمطور فقط)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// جلب المعلمات المرسلة في الرابط (مثل رقم المستخدم، رقم القسم، أو رقم منتج معين)
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

try {
    // بناء استعلام فرعي للتحقق مما إذا كان المنتج في قائمة مفضلة المستخدم الحالي
    // إذا تم تمرير رقم مستخدم، نقوم بالبحث في جدول favorites، وإلا نعتبر الحالة 0 (ليس مفضلاً)
    $fav_query = $user_id ? ", (SELECT COUNT(*) FROM favorites WHERE user_id = " . intval($user_id) . " AND product_id = p.id) as is_favorite" : ", 0 as is_favorite";

    if ($id) {
        // حالة طلب منتج واحد فقط (صفحة تفاصيل المنتج)
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name $fav_query FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // إرجاع بيانات المنتج في حال وجوده
            echo json_encode([
                'status' => 'success',
                'data' => $product
            ]);
        } else {
            // إرجاع خطأ 404 في حال عدم العثور على المنتج
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Product not found'
            ]);
        }
    } else if ($category_id) {
        // حالة طلب المنتجات التابعة لقسم معين
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name $fav_query FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$category_id]);
        $products = $stmt->fetchAll();
        echo json_encode([
            'status' => 'success',
            'data' => $products
        ]);
    } else {
        // حالة طلب جميع المنتجات (مثل الصفحة الرئيسية في التطبيق)
        $products = $pdo->query("SELECT p.*, c.name as category_name $fav_query FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
        echo json_encode([
            'status' => 'success',
            'data' => $products
        ]);
    }
} catch (Exception $e) {
    // في حال حدوث أي خطأ برمي استجابة 500 ورسالة الخطأ
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
