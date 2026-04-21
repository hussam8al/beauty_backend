<?php
// تضمين ملف قاعدة البيانات للاتصال
require_once __DIR__ . '/../includes/db.php';

// تعيين عنوان الصفحة وتحديد الصفحة النشطة للقائمة
$page_title = 'إدارة المنتجات';
$active_page = 'products';

// --- قسم الحذف ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; // جلب معرف المنتج
    // تنفيذ استعلام الحذف
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?deleted=1");
    exit;
}

// --- قسم الإضافة والتعديل ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null; // إذا وجد ID فهو تعديل، وإلا فهو إضافة
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    // التأكد من وجود قيمة للخصم، وإذا كانت فارغة نضع 0
    $discount_amount = !empty($_POST['discount_amount']) ? $_POST['discount_amount'] : 0;
    $currency = $_POST['currency'];
    $description = $_POST['description'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0; // التحقق من "منتج مميز"
    
    // التعامل مع رفع صورة المنتج باستخدام Supabase Storage
    $image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // إنشاء اسم فريد وإزالة الأحرف غير الإنجليزية (عربية وفراغات إلخ)
        $original_name = basename($_FILES["image"]["name"]);
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $file_name = time() . '_' . $safe_name . '.' . $ext;
        
        $supabase_url = getenv('SUPABASE_URL');
        $supabase_key = getenv('SUPABASE_KEY');
        
        if ($supabase_url && $supabase_key) {
            // تنظيف الرابط في حال قام المستخدم بنسخ رابط الـ REST API بالخطأ
            $supabase_url = str_replace('/rest/v1', '', rtrim($supabase_url, '/'));
            $bucket_name = 'products'; // استخدام نفس الدلو
            $upload_url = $supabase_url . "/storage/v1/object/$bucket_name/$file_name";
            
            $file_content = file_get_contents($_FILES["image"]["tmp_name"]);
            $mime_type = mime_content_type($_FILES["image"]["tmp_name"]);
            
            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $supabase_key",
                "Content-Type: $mime_type",
                "apikey: $supabase_key"
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            if ($http_code == 200 || $http_code == 201) {
                // حفظ المسار كـ رابط عام ليتمكن التطبيق من قراءته
                $image_path = rtrim($supabase_url, '/') . "/storage/v1/object/public/$bucket_name/$file_name";
            } else {
                // عرض الخطأ المتسبب بالفشل
                echo "<div style='color:red; background:#fee; padding:10px; border:1px solid red; margin:10px;'>";
                echo "<h3>خطأ في رفع صورة المنتج إلى Supabase:</h3>";
                echo "HTTP Code: " . $http_code . "<br>";
                echo "Error Response: " . htmlspecialchars($response) . "<br>";
                echo "CURL Error: " . $curl_error . "<br>";
                echo "</div>";
                die("تم إيقاف العملية لإصلاح الخطأ.");
            }
        } else {
            // للتطوير المحلي في حالة عدم إعداد Supabase
            $target_dir = "../uploads/";
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = 'uploads/' . $file_name; 
            }
        }
    }

    if ($id) {
        // تحديث بيانات منتج موجود
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, discount_amount = ?, currency = ?, description = ?, is_featured = ?, image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $category_id, $price, $discount_amount, $currency, $description, $is_featured, $image_path, $id]);
        header("Location: products.php?updated=1");
    } else {
        // إضافة منتج جديد للقاعدة
        $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, discount_amount, currency, description, is_featured, image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $category_id, $price, $discount_amount, $currency, $description, $is_featured, $image_path]);
        header("Location: products.php?added=1");
    }
    exit;
}

// استعلام لجلب جميع المنتجات مع اسم القسم الخاص بها باستخدام JOIN
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
// جلب قائمة الأقسام لاستخدامها في القائمة المنسدلة (Dropdown) بالنموذج
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- هيدر الصفحة وزر الإضافة -->
<div class="header">
    <h1>المنتجات</h1>
    <button onclick="showAddForm()" class="btn btn-primary">إضافة منتج جديد</button>
</div>

<!-- رسائل النجاح (Toast Notifications) -->
<?php if(isset($_GET['added'])): ?>
<div id="toast" class="toast toast-success">✅ تم إضافة المنتج بنجاح!</div>
<?php elseif(isset($_GET['updated'])): ?>
<div id="toast" class="toast toast-success">✅ تم تعديل المنتج بنجاح!</div>
<?php elseif(isset($_GET['deleted'])): ?>
<div id="toast" class="toast toast-success">🗑️ تم حذف المنتج بنجاح!</div>
<?php endif; ?>

<style>
.toast {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    padding: 14px 32px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: bold;
    color: #fff;
    z-index: 9999;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: fadeInOut 3.5s forwards;
}
.toast-success { background: #22c55e; }
@keyframes fadeInOut {
    0%   { opacity: 0; top: 10px; }
    10%  { opacity: 1; top: 24px; }
    80%  { opacity: 1; top: 24px; }
    100% { opacity: 0; top: 10px; }
}
.btn-loading { opacity: 0.7; cursor: not-allowed; pointer-events: none; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- نموذج إضافة/تعديل منتج (يظهر عند الطلب) -->
<div id="productForm" class="card" style="display:none; margin-bottom: 2rem;">
    <h2 id="formTitle">إضافة منتج جديد</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="prodId">
        <input type="hidden" name="existing_image" id="existingImage">
        <div>
            <label>اسم المنتج</label>
            <input type="text" name="name" id="prodName" required>
        </div>
        <div>
            <label>القسم</label>
            <select name="category_id" id="prodCategory" required>
                <option value="">اختر القسم</option>
                <?php foreach ($categories as $cat): ?>
                <!-- عرض الأقسام المتاحة في قاعدة البيانات -->
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <div style="flex: 1;">
                <label>السعر الأساسي</label>
                <input type="number" step="0.01" name="price" id="prodPrice" required>
            </div>
            <div style="flex: 1;">
                <label>قيمة الخصم (مبلغ)</label>
                <input type="number" step="0.01" name="discount_amount" id="prodDiscountAmount" value="0">
            </div>
            <div style="flex: 1;">
                <label>العملة</label>
                <select name="currency" id="prodCurrency" required>
                    <option value="ر.س">ريال سعودي (ر.س)</option>
                    <option value="ر.ي">ريال يمني (ر.ي)</option>
                    <option value="$">دولار ($)</option>
                </select>
            </div>
        </div>
        <div>
            <label>الوصف</label>
            <textarea name="description" id="prodDescription" rows="4"></textarea>
        </div>
        <div>
            <label>الصورة</label>
            <input type="file" name="image">
        </div>
        <div>
            <label>
                <input type="checkbox" name="is_featured" id="prodFeatured"> منتج مميز
            </label>
        </div>
        <button type="submit" class="btn btn-primary" onclick="showLoading(this)">حفظ المنتج</button>
        <button type="button" onclick="hideForm()" class="btn btn-danger">إلغاء</button>
    </form>
</div>

<!-- جدول عرض قائمة المنتجات التفصيلي -->
<div class="card">
    <table>
        <thead>
            <tr>
                <th>الصورة</th>
                <th>الاسم</th>
                <th>القسم</th>
                <th>السعر الأساسي</th>
                <th>الخصم</th>
                <th>سعر البيع</th>
                <th>العملة</th>
                <th>العمليات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): 
                // حساب سعر البيع النهائي بعد خصم المبلغ
                $selling_price = $product['price'] - ($product['discount_amount'] ?? 0);
            ?>
            <tr>
                <td>
                    <?php if($product['image']): ?>
                        <?php 
                        $img_src = $product['image'];
                        if (strpos($img_src, 'http') === false) {
                            $img_src = "../" . $img_src;
                        }
                        ?>
                        <img src="<?php echo $img_src; ?>" width="50" style="border-radius:5px">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo number_format($product['price'], 2); ?></td>
                <td style="color:red">-<?php echo number_format($product['discount_amount'] ?? 0, 2); ?></td>
                <td style="font-weight:bold; color:green;"><?php echo number_format($selling_price, 2); ?></td>
                <td><?php echo $product['currency']; ?></td>
                <td>
                    <!-- استدعاء دالة التعديل وتمرير بيانات المنتج كاملاً بصيغة JSON -->
                    <button onclick='showEditForm(<?php echo json_encode($product); ?>)' class="btn btn-primary" style="background: #ffa500; border-color: #ffa500; margin-left: 5px;">تعديل</button>
                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirmDelete(this)">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// دالة لتفريغ النموذج من أجل إضافة منتج جديد
function showAddForm() {
    document.getElementById('formTitle').innerText = 'إضافة منتج جديد';
    document.getElementById('prodId').value = '';
    document.getElementById('prodName').value = '';
    document.getElementById('prodCategory').value = '';
    document.getElementById('prodPrice').value = '';
    document.getElementById('prodDiscountAmount').value = '0';
    document.getElementById('prodCurrency').value = 'ر.س';
    document.getElementById('prodDescription').value = '';
    document.getElementById('prodFeatured').checked = false;
    document.getElementById('existingImage').value = '';
    document.getElementById('productForm').style.display = 'block';
}

// دالة لتعبئة النموذج ببيانات منتج موجود لغرض التعديل
function showEditForm(prod) {
    document.getElementById('formTitle').innerText = 'تعديل منتج';
    document.getElementById('prodId').value = prod.id;
    document.getElementById('prodName').value = prod.name;
    document.getElementById('prodCategory').value = prod.category_id;
    document.getElementById('prodPrice').value = prod.price;
    document.getElementById('prodDiscountAmount').value = prod.discount_amount || '0';
    document.getElementById('prodCurrency').value = prod.currency || 'ر.س';
    document.getElementById('prodDescription').value = prod.description;
    document.getElementById('prodFeatured').checked = prod.is_featured == 1;
    document.getElementById('existingImage').value = prod.image || '';
    document.getElementById('productForm').style.display = 'block';
}

// دالة لإخفاء النموذج
function hideForm() {
    document.getElementById('productForm').style.display = 'none';
}

// إظهار حالة التحميل عند الإرسال
function showLoading(btn) {
    setTimeout(function() {
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px"><svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="3" fill="none"/><path d="M12 2a10 10 0 0 1 10 10" stroke="white" stroke-width="3" fill="none" stroke-linecap="round"/></svg>جاري الحفظ...</span>';
        btn.classList.add('btn-loading');
    }, 10);
    return true;
}

// تأكيد الحذف
function confirmDelete(link) {
    if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return false;
    link.innerHTML = 'جاري الحذف...';
    link.classList.add('btn-loading');
    return true;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
