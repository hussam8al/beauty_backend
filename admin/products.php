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
        $file_name = time() . '_' . basename($_FILES["image"]["name"]); // اسم فريد للصورة
        
        $supabase_url = getenv('SUPABASE_URL');
        $supabase_key = getenv('SUPABASE_KEY');
        
        if ($supabase_url && $supabase_key) {
            $bucket_name = 'products'; // اسم الدلو في Supabase Storage
            $upload_url = rtrim($supabase_url, '/') . "/storage/v1/object/$bucket_name/$file_name";
            
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
            curl_close($ch);
            
            if ($http_code == 200 || $http_code == 201) {
                // حفظ المسار كـ رابط عام ليتمكن التطبيق من قراءته
                $image_path = rtrim($supabase_url, '/') . "/storage/v1/object/public/$bucket_name/$file_name";
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
        <button type="submit" class="btn btn-primary">حفظ المنتج</button>
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
                        <img src="../<?php echo $product['image']; ?>" width="50" style="border-radius:5px">
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
                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
