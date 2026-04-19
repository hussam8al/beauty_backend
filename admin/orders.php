<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات - متجر الجمال</title>
    <!-- استرداد خط Tajawal من جوجل فونتس لتحسين مظهر الخط العربي -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* تعريف الأساسيات والألوان المستخدمة في التصميم */
        :root {
            --primary: #9C27B0;
            --secondary: #E1BEE7;
            --text: #333;
            --bg: #bdb7c1ff;
            --white: #eee9f3ff;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: var(--primary);
        }

        /* تصميم "الكرت" الخاص بكل طلب */
        .order-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(13, 16, 159, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #EEE;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        /* حالات الطلبات (قيد الانتظار، تم التوصيل، ملغي) بتنسيقات ألوان مختلفة */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-pending {
            background: #FFECB3;
            color: #FFA000;
        }

        .status-delivered {
            background: #C8E6C9;
            color: #2E7D32;
        }

        .status-cancelled {
            background: #FFCDD2;
            color: #C62828;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .items-list {
            margin-top: 15px;
            background: #FAFAFA;
            padding: 10px;
            border-radius: 8px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        button {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: 'Tajawal';
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-confirm {
            background-color: #4CAF50;
            color: white;
        }

        .btn-cancel {
            background-color: #F44336;
            color: white;
        }

        .btn-map {
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        @media (max-width: 600px) {
            .order-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>إدارة الطلبات 📦</h1>
            <a href="index.php" style="text-decoration:none; color: var(--primary);">العودة للرئيسية</a>
        </header>

        <!-- هذا الوعاء سيتم تعبئته بالطلبات القادمة من السيرفر عبر JavaScript -->
        <div id="orders-container">
            <p>جاري تحميل الطلبات...</p>
        </div>
    </div>

    <script>
        // دالة لجلب الطلبات من السيرفر عبر API
        async function fetchOrders() {
            try {
                // استخدام fetch لجلب البيانات من ملف PHP الذي يعيد JSON
                const response = await fetch('../api/admin/get_orders.php');
                const result = await response.json();

                if (result.status === 'success') {
                    // إذا نجح الجلب، نقوم بعرض الطلبات في الصفحة
                    renderOrders(result.data);
                } else {
                    document.getElementById('orders-container').innerHTML = '<p>خطأ في جلب الطلبات</p>';
                }
            } catch (e) {
                console.error(e);
                document.getElementById('orders-container').innerHTML = '<p>تعذر الاتصال بالسيرفر</p>';
            }
        }

        // دالة لتحديث حالة الطلب (توصيل أو إلغاء)
        async function updateStatus(orderId, status) {
            let reason = null;
            let delivery = null;

            // إذا كانت الحالة "تم التوصيل"، نطلب من المدير إدخال وقت الوصول
            if (status === 'delivered') {
                delivery = prompt('يرجى إدخال موعد الوصول المتوقع (مثلاً: خلال ساعتين، غداً صباحاً):');
                if (delivery === null) return; // الخروج في حال إلغاء النافذة المنبثقة
            }
            // إذا كانت الحالة "ملغي"، نطلب إدخال السبب
            else if (status === 'cancelled') {
                reason = prompt('يرجى إدخال سبب الرفض:');
                if (reason === null) return;
            } else {
                if (!confirm(`هل أنت متأكد من تغيير حالة الطلب إلى ${status}؟`)) return;
            }

            try {
                // إرسال طلب POST لتحديث الحالة في قاعدة البيانات
                const response = await fetch('../api/admin/update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status,
                        rejection_reason: reason,
                        estimated_delivery: delivery
                    })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert('تم نحديث الحالة بنجاح');
                    fetchOrders(); // إعادة جلب الطلبات لتحديث الواجهة
                } else {
                    alert('فشل التحديث: ' + result.message);
                }
            } catch (e) {
                alert('خطأ في الاتصال');
            }
        }

        // دالة لتحويل بيانات الطلبات من مصفوفة إلى كود HTML وعرضه
        function renderOrders(orders) {
            const container = document.getElementById('orders-container');
            if (orders.length === 0) {
                container.innerHTML = '<p>لا توجد طلبات بعد.</p>';
                return;
            }

            // استخدام دالة map للمرور على كل طلب وإنشاء الـ HTML الخاص به
            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-header">
                        <strong>طلب رقم #${order.id}</strong>
                        <span class="status-badge status-${order.status}">${order.status}</span>
                        <!-- تنسيق تاريخ الطلب ليظهر بتوقيت السعودية -->
                        <span>${new Date(order.created_at).toLocaleString('ar-SA', { hour12: true })}</span>
                    </div>
                    <div class="order-details">
                        <div>
                            <strong>العميل:</strong> ${order.user_name}<br>
                            <strong>العنوان:</strong> ${order.shipping_address}<br>
                            <strong>الجوال:</strong> ${order.phone_number}
                        </div>
                        <div>
                            <strong>الدفع:</strong> ${order.payment_method}<br>
                            <strong>الإجمالي:</strong> <span style="color:var(--primary); font-weight:bold;">${order.total_amount}</span>
                        </div>
                    </div>
                    
                    <div class="items-list">
                        <div class="order-items">
                            <!-- عرض المنتجات الموجودة داخل هذا الطلب -->
                            ${order.items.map(item => `
                                <div class="item">
                                    <span>${item.product_name} x ${item.quantity}</span>
                                    <span>${item.price} ${item.currency || order.currency}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div class="actions">
                        <!-- إذا كان العميل قد أرسل إحداثيات موقعه، يتم عرض زر لفتح خريطة جوجل -->
                        ${order.latitude ? `
                            <a href="https://www.google.com/maps?q=${order.latitude},${order.longitude}" target="_blank" class="btn-map button" style="padding: 8px 16px; border-radius: 8px;">📍 موقع العميل</a>
                        ` : '<span style="color:#999; font-size:0.9em;">(الموقع غير متوفر)</span>'}
                        
                        <!-- إظهار أزرار التحكم فقط إذا كان الطلب لا يزال في حالة "انتظار" -->
                        ${order.status === 'pending' ? `
                            <button class="btn-confirm" onclick="updateStatus(${order.id}, 'delivered')">تأكيد التوصيل</button>
                            <button class="btn-cancel" onclick="updateStatus(${order.id}, 'cancelled')">إلغاء الطلب</button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        // تشغيل الدالة تلقائياً عند فتح الصفحة لجلب الطلبات لأول مرة
        fetchOrders();
    </script>
</body>

</html>