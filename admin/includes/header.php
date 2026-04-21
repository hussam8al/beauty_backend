<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'لوحة التحكم'; ?> - منتجات تجميل</title>
    <!-- أيقونة الموقع (Favicon) - تم إضافة إصدار لتجاوز الكاش -->
    <link rel="icon" type="image/png" href="favicon.png?v=1.1">
    <link rel="shortcut icon" type="image/png" href="favicon.png?v=1.1">
    
    <!-- الربط مع ملف التنسيق الأساسي -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- خط Tajawal لتحسين مظهر الخط العربي -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-glow: rgba(212, 163, 115, 0.4);
        }

        body { 
            font-family: 'Tajawal', sans-serif; 
            background-color: #fdfaf7; 
        }

        .sidebar { 
            right: 0; left: auto; 
            border-right: none; 
            border-left: 1px solid #E5E7EB; 
            background: #fffcf9; 
        }

        .main-content { 
            margin-right: 250px; 
            margin-left: 0; 
            text-align: right; 
        }

        th, td { 
            text-align: right; 
            border-bottom: 1px solid #f0e6da; 
        }

        /* --- تأثيرات النبض والتفاعل (Premium UI) --- */
        
        /* تأثير النبض الناعم عند التفاعل مع الأزرار والحقول */
        .btn, input, select, textarea, .card, .stat-card { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        /* تأثير النبض (Pulse Animation) */
        @keyframes pulse-interactive {
            0% { box-shadow: 0 0 0 0 var(--primary-glow); transform: scale(1); }
            50% { box-shadow: 0 0 0 10px rgba(212, 163, 115, 0); transform: scale(1.01); }
            100% { box-shadow: 0 0 0 0 rgba(212, 163, 115, 0); transform: scale(1); }
        }

        /* تطبيق التأثير على الأزرار عند المرور (Hover) */
        .btn:hover {
            transform: translateY(-2px);
            animation: pulse-interactive 1.5s infinite ease-in-out;
            box-shadow: 0 10px 20px -5px rgba(212, 163, 115, 0.4);
        }

        /* تطبيق التأثير على حقول الإدخال عند التركيز (Focus) */
        input:focus, select:focus, textarea:focus {
            border-color: #d4a373 !important;
            background-color: #fff;
            animation: pulse-interactive 2s infinite ease-in-out;
            outline: none;
        }

        /* تحريك الكروت بخفة عند المرور عليها */
        .card:hover, .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
        }

        /* تحسين مظهر الجداول */
        tr:hover td {
            background-color: #fffaf5;
        }

        /* تخصيص مؤشر الفأرة للأزرار */
        .btn { cursor: pointer; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
