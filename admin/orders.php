<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/../includes/db.php';

$page_title = 'إدارة الطلبات';
$active_page = 'orders';

// استدعاء الهيدر العالمي
include __DIR__ . '/includes/header.php';
?>

<style>
    /* تحسينات إضافية خاصة بصفحة الطلبات لتتناسب مع التصميم الجديد */
    .order-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        border: 1px solid #f0e6da;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f5f0eb;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }

    .status-badge {
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 0.85em;
        font-weight: bold;
    }

    .status-pending { background: #fff8e1; color: #ff8f00; }
    .status-delivered { background: #e8f5e9; color: #2e7d32; }
    .status-cancelled { background: #ffebee; color: #c62828; }

    .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        background: #fafaf9;
        padding: 15px;
        border-radius: 8px;
    }

    .items-list {
        margin-top: 15px;
        padding: 10px;
    }

    .item {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px dashed #e5e5e5;
    }

    .item:last-child { border-bottom: none; }

    .actions {
        margin-top: 20px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-confirm { background-color: #22c55e; color: white; }
    .btn-cancel { background-color: #ef4444; color: white; }
    .btn-map { background-color: #d4a373; color: white; text-decoration: none; }
</style>

<div class="header">
    <h1>إدارة الطلبات 📦</h1>
</div>

<!-- حاوية الطلبات -->
<div id="orders-container">
    <div class="card" style="text-align: center; padding: 50px;">
        <svg width="40" height="40" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="#d4a373" stroke-width="3" fill="none" style="opacity:0.3"/><path d="M12 2a10 10 0 0 1 10 10" stroke="#d4a373" stroke-width="3" fill="none" stroke-linecap="round"/></svg>
        <p style="margin-top: 15px; color: #666;">جاري تحميل الطلبات الحديثة...</p>
    </div>
</div>

<script>
    // دالة محددة لأسماء الحالات بالعربي
    function getStatusName(status) {
        const statuses = {
            'pending': 'قيد الانتظار',
            'delivered': 'تم التوصيل',
            'cancelled': 'ملغي'
        };
        return statuses[status] || status;
    }

    // جلب الطلبات من السيرفر
    async function fetchOrders() {
        try {
            const response = await fetch('../api/admin/get_orders.php');
            const result = await response.json();

            if (result.status === 'success') {
                renderOrders(result.data);
            } else {
                document.getElementById('orders-container').innerHTML = '<div class="card" style="color:red">خطأ في جلب البيانات</div>';
            }
        } catch (e) {
            document.getElementById('orders-container').innerHTML = '<div class="card" style="color:red">فشل الاتصال بالسيرفر</div>';
        }
    }

    // تحديث حالة طلب
    async function updateStatus(orderId, status) {
        let reason = null;
        let delivery = null;

        if (status === 'delivered') {
            delivery = prompt('موعد الوصول المتوقع (اختياري):');
            if (delivery === null) return;
        } else if (status === 'cancelled') {
            reason = prompt('سبب الرفض (إلزامي):');
            if (!reason) return;
        }

        try {
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
                fetchOrders();
            } else {
                alert('خطأ: ' + result.message);
            }
        } catch (e) {
            alert('خطأ في الاتصال');
        }
    }

    // عرض الطلبات في الصفحة
    function renderOrders(orders) {
        const container = document.getElementById('orders-container');
        if (orders.length === 0) {
            container.innerHTML = '<div class="card" style="text-align:center">لا توجد طلبات جديدة حالياً</div>';
            return;
        }

        container.innerHTML = orders.map(order => `
            <div class="order-card">
                <div class="order-header">
                    <strong>طلب #${order.id}</strong>
                    <span class="status-badge status-${order.status}">${getStatusName(order.status)}</span>
                    <span style="font-size: 0.9em; color: #888;">${new Date(order.created_at).toLocaleString('ar-SA')}</span>
                </div>
                <div class="order-details">
                    <div>
                        <div style="font-weight:bold; margin-bottom:5px">بيانات العميل</div>
                        <div>الاسم: ${order.user_name}</div>
                        <div>الهاتف: ${order.phone_number}</div>
                        <div>العنوان: ${order.shipping_address}</div>
                    </div>
                    <div>
                        <div style="font-weight:bold; margin-bottom:5px">تفاصيل الدفع</div>
                        <div>الحساب: ${order.total_amount} ر.س</div>
                        <div>طريقة الدفع: ${order.payment_method || 'عند الاستلام'}</div>
                    </div>
                </div>
                
                <div class="items-list">
                    <div style="font-weight:bold; margin-bottom:10px; font-size:0.9em; color:#d4a373">المنتجات المطلوبة:</div>
                    ${order.items.map(item => `
                        <div class="item">
                            <span>${item.product_name} (×${item.quantity})</span>
                            <span style="font-weight:bold">${item.price} ر.س</span>
                        </div>
                    `).join('')}
                </div>

                <div class="actions">
                    ${order.latitude ? `
                        <a href="https://www.google.com/maps?q=${order.latitude},${order.longitude}" target="_blank" class="btn btn-map">📍 موقع العميل على الخريطة</a>
                    ` : '<span style="color:#999; font-size:0.85em; display:flex; align-items:center">⚠️ العميل لم يرفق موقعه بدقة</span>'}
                    
                    ${order.status === 'pending' ? `
                        <button class="btn btn-confirm" onclick="updateStatus(${order.id}, 'delivered')">تأكيد وتحديد موعد</button>
                        <button class="btn btn-cancel" onclick="updateStatus(${order.id}, 'cancelled')">إلغاء الطلب</button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    fetchOrders();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>