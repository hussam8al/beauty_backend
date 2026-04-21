<?php
// تضمين ملف الاتصال بقاعدة البيانات لتمكين الوصول إلى الكائن $pdo المستخدم في الاستعلامات
require_once __DIR__ . '/../includes/db.php';

// تعريف متغير لعنوان الصفحة الحالية
$page_title = 'الرئيسية';

// تعريف متغير لتحديد الصفحة النشطة في القائمة الجانبية (Sidebar)
$active_page = 'index';

// جلب الإحصائيات (Stats) لعرضها في الكروت العلوية للوحة التحكم
// استعلام لحساب عدد جميع المنتجات في جدول products
$product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// استعلام لحساب عدد جميع الأقسام في جدول categories
$category_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// استعلام لحساب عدد الطلبات التي حالتها "قيد الانتظار" (pending) فقط
$order_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// جلب آخر 5 منتجات مضافة حديثاً لعرضها في الجدول الرئيسي
// نستخدم JOIN لدمج جدول المنتجات مع جدول الأقسام لجلب اسم القسم بدلاً من رقمه فقط
$latest_products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// استدعاء ملف الهيدر (Header) الذي يحتوي على أكواد HTML العليا وقائمة التنقل
include __DIR__ . '/includes/header.php';
?>

<!-- هيدر الصفحة الرئيسي -->
<div class="header">
    <h1>لوحة التحكم</h1>
</div>

<!-- شبكة تعرض الإحصائيات في كروت منفصلة -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>إجمالي المنتجات</h3>
        <!-- عرض عدد المنتجات الذي جلبناه من قاعدة البيانات -->
        <p><?php echo $product_count; ?></p>
    </div>
    <div class="stat-card">
        <h3>إجمالي الأقسام</h3>
        <!-- عرض عدد الأقسام المستخرج من الاستعلام -->
        <p><?php echo $category_count; ?></p>
    </div>
    <!-- كارت الطلبات الجديدة، عند الضغط عليه ينتقل لصفحة الطلبات -->
    <div class="stat-card" style="border: 2px solid var(--primary); cursor: pointer;"
        onclick="location.href='orders.php'">
        <h3>الطلبات الجديدة</h3>
        <!-- عرض عدد الطلبات المعلقة باللون الرئيسي للمتجر -->
        <p style="color: var(--primary);"><?php echo $order_count; ?></p>
        <small>اضغط للعرض</small>
    </div>
</div>

<!-- قسم "أحدث المنتجات" ويعرض في جدول مبسط -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>أحدث المنتجات</h2>
        <!-- زر للانتقال لصفحة المنتجات الكاملة -->
        <a href="products.php" class="btn btn-primary">عرض الكل</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>المنتج</th>
                <th>القسم</th>
                <th>السعر</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <!-- حلقة تكرارية (Loop) للمرور على قائمة آخر المنتجات وعرضها في صفوف الجدول -->
            <?php foreach ($latest_products as $product): ?>
                <tr>
                    <!-- عرض اسم المنتج مع حماية البيانات من ثغرات XSS باستخدام htmlspecialchars -->
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <!-- عرض اسم القسم المرتبط بالمنتج -->
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <!-- تنسيق السعر ليظهر برقمين عشريين بجانب العملة -->
                    <td><?php echo number_format($product['price'], 2); ?> ر.س</td>
                    <!-- عرض كلمة "مميز" إذا كان المنتج محدداً كمنتج Featured -->
                    <td><?php echo $product['is_featured'] ? 'مميز' : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- استدعاء ملف الفوتر (Footer) الذي يغلق وسوم HTML -->
<?php include __DIR__ . '/includes/footer.php'; ?>