<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'لوحة التحكم'; ?> - متجر الجمال</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, 'Inter'; }
        .sidebar { right: 0; left: auto; border-right: none; border-left: 1px solid #E5E7EB; }
        .main-content { margin-right: 250px; margin-left: 0; text-align: right; }
        th, td { text-align: right; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
