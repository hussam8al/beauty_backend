<?php
// تضمين ملف قاعدة البيانات للاتصال بالسيرفر
require_once __DIR__ . '/../includes/db.php';

// ضبط عنوان الصفحة واسمها البرمجي
$page_title = 'إدارة الأقسام';
$active_page = 'categories';

// كود عمليات الحذف (Delete)
// نتحقق إذا كان هناك معلمة 'delete' مرسلة في الرابط (GET Request)
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; // أخذ معرف القسم المراد حذفه
    // تحضير استعلام الحذف لحماية القاعدة من حقن SQL
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]); // تنفيذ عملية الحذف
    // إعادة توجيه المستخدم لنفس الصفحة مع رسالة نجاح الحذف
    header("Location: categories.php?deleted=1");
    exit; // إيقاف تنفيذ السكريبت بعد التحويل
}

// كود عمليات الإضافة أو التعديل (Add/Update)
// نتحقق إذا تم إرسال بيانات عبر نموذج POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name']; // جلب اسم القسم من النموذج
    $id = isset($_POST['id']) ? $_POST['id'] : null; // جلب المعرف إذا كان عملية تعديل
    
    // التعامل مع رفع الصور (Image Upload Handling) باستخدام Supabase Storage
    $image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : ''; // الاحتفاظ بالصورة القديمة افتراضياً
    // نتحقق إذا تم اختيار ملف جديد ولا يوجد أخطاء في الرفع
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // إنشاء اسم فريد للملف وإزالة الأحرف غير الإنجليزية (العربية، الفراغات، إلخ)
        $original_name = basename($_FILES["image"]["name"]);
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        // استبدال كل ما ليس حرفاً إنجليزياً أو رقماً أو نقطة بشرطة سفلية
        $safe_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $file_name = time() . '_' . $safe_name . '.' . $ext;
        
        $supabase_url = getenv('SUPABASE_URL');
        $supabase_key = getenv('SUPABASE_KEY');
        
        if ($supabase_url && $supabase_key) {
            // تنظيف الرابط في حال قام المستخدم بنسخ رابط الـ REST API بالخطأ
            $supabase_url = str_replace('/rest/v1', '', rtrim($supabase_url, '/'));
            $bucket_name = 'products'; // استخدام نفس الدلو أو إنشاء واحد جديد، سنستخدم 'products' للتخزين العام
            $upload_url = $supabase_url . "/storage/v1/object/$bucket_name/categories/$file_name";
            
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
                $image_path = rtrim($supabase_url, '/') . "/storage/v1/object/public/$bucket_name/categories/$file_name";
            } else {
                // في حالة فشل الرفع، نعرض الخطأ للتصحيح
                echo "<div style='color:red; background:#fee; padding:10px; border:1px solid red; margin:10px;'>";
                echo "<h3>خطأ في رفع الصورة إلى Supabase:</h3>";
                echo "HTTP Code: " . $http_code . "<br>";
                echo "Error Response: " . htmlspecialchars($response) . "<br>";
                echo "CURL Error: " . $curl_error . "<br>";
                echo "URL attempted: " . $upload_url . "<br>";
                echo "</div>";
                die("تم إيقاف العملية لإصلاح الخطأ أعلاه.");
            }
        } else {
            // للتطوير المحلي في حالة عدم إعداد Supabase
            $target_dir = "../uploads/"; // مسار مجلد الرفع
            $target_file = $target_dir . $file_name;
            // محاولة نقل الملف المرفوع من المجلد المؤقت إلى المجلد الدائم
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = 'uploads/' . $file_name; // تخزين المسار الجديد في المتغير
            }
        }
    }

    // التحقق هل العملية تعديل لقسم موجود أم إضافة قسم جديد
    if ($id) {
        // تحديث بيانات قسم موجود (Update)
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $image_path, $id]);
        header("Location: categories.php?updated=1");
    } else {
        // إضافة قسم جديد بالكامل (Insert)
        $stmt = $pdo->prepare("INSERT INTO categories (name, image, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$name, $image_path]);
        header("Location: categories.php?added=1");
    }
    exit;
}

// جلب جميع الأقسام من قاعدة البيانات لتعرض في الجدول أدناه مرتبة من الأحدث
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

// استدعاء ملف الهيدر العلوي
include __DIR__ . '/includes/header.php';
?>

<!-- هيدر الصفحة مع زر لفتح نموذج الإضافة -->
<div class="header">
    <h1>الأقسام</h1>
    <button onclick="showAddForm()" class="btn btn-primary">إضافة قسم جديد</button>
</div>

<!-- رسائل النجاح والخطأ (Toast Notifications) -->
<?php if(isset($_GET['added'])): ?>
<div id="toast" class="toast toast-success">✅ تم إضافة القسم بنجاح!</div>
<?php elseif(isset($_GET['updated'])): ?>
<div id="toast" class="toast toast-success">✅ تم تعديل القسم بنجاح!</div>
<?php elseif(isset($_GET['deleted'])): ?>
<div id="toast" class="toast toast-success">🗑️ تم حذف القسم بنجاح!</div>
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
.toast-error   { background: #ef4444; }
@keyframes fadeInOut {
    0%   { opacity: 0; top: 10px; }
    10%  { opacity: 1; top: 24px; }
    80%  { opacity: 1; top: 24px; }
    100% { opacity: 0; top: 10px; }
}
.btn-loading {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}
</style>

<!-- نموذج إضافة/تعديل قسم (مخفي افتراضياً ويظهر بالجافاسكريبت) -->
<div id="categoryForm" class="card" style="display:none; margin-bottom: 2rem;">
    <h2 id="formTitle">إضافة قسم جديد</h2>
    <!-- enctype ضروري لتمكين رفع الملفات والصور -->
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="catId"> <!-- حقل مخفي لتخزين المعرف عند التعديل -->
        <input type="hidden" name="existing_image" id="existingImage"> <!-- حقل مخفي للصورة القديمة -->
        <div>
            <label>اسم القسم</label>
            <input type="text" name="name" id="catName" required>
        </div>
        <div>
            <label>الصورة</label>
            <input type="file" name="image">
        </div>
        <button type="submit" id="submitBtn" class="btn btn-primary" onclick="showLoading(this)">حفظ القسم</button>
        <button type="button" onclick="hideForm()" class="btn btn-danger">إلغاء</button>
    </form>
</div>

<!-- جدول عرض البيانات -->
<div class="card">
    <table>
        <thead>
            <tr>
                <th>الصورة</th>
                <th>الاسم</th>
                <th>العمليات</th>
            </tr>
        </thead>
        <tbody>
            <!-- تكرار لعرض كل قسم في صف منفصل -->
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td>
                    <!-- التحقق إذا كان القسم يحتوي على صورة لعرضها -->
                    <?php if($cat['image']): ?>
                        <?php 
                        $img_src = $cat['image'];
                        // إذا كان الرابط يبدأ بـ http، نعرضه كما هو، وإلا نضيف المسار المحلي
                        if (strpos($img_src, 'http') === false) {
                            $img_src = "../" . $img_src;
                        }
                        ?>
                        <img src="<?php echo $img_src; ?>" width="50" style="border-radius:5px">
                    <?php else: ?>
                        - <!-- عرض خط إذا لم توجد صورة -->
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td>
                    <!-- زر التعديل يقوم بتمرير بيانات القسم لدالة جافاسكريبت لتعبئة النموذج -->
                    <button onclick='showEditForm(<?php echo json_encode($cat); ?>)' class="btn btn-primary" style="background: #ffa500; border-color: #ffa500; margin-left: 5px;">تعديل</button>
                    <!-- رابط الحذف مع رسالة تأكيد لحماية البيانات من الحذف السهو -->
                    <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" onclick="return confirmDelete(this)">حذف</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// إظهار حالة التحميل عند الإرسال
function showLoading(btn) {
    setTimeout(function() {
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px"><svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="3" fill="none"/><path d="M12 2a10 10 0 0 1 10 10" stroke="white" stroke-width="3" fill="none" stroke-linecap="round"/></svg>جاري الحفظ...</span>';
        btn.classList.add('btn-loading');
    }, 10);
    return true;
}

// تأكيد الحذف مع إظهار رسالة تحميل
function confirmDelete(link) {
    if (!confirm('هل أنت متأكد من حذف هذا القسم؟')) return false;
    link.innerHTML = 'جاري الحذف...';
    link.classList.add('btn-loading');
    return true;
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
