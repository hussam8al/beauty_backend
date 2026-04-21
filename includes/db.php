<?php
// جلب بيانات الاتصال من متغيرات البيئة (Environment Variables) لضمان الأمان في السيرفرات السحابية
// سيتم ضبط هذه القيم في لوحة تحكم Vercel لاحقاً
$db_url = getenv('SUPABASE_DB_URL');

if ($db_url) {
    // استخراج معلومات الاتصال من الرابط الذي تقدمه Supabase
    $parsed_url = parse_url($db_url);
    $host = $parsed_url['host'];
    $port = isset($parsed_url['port']) ? $parsed_url['port'] : 5432;
    $user = urldecode($parsed_url['user']);
    $pass = urldecode($parsed_url['pass']);
    $db   = ltrim($parsed_url['path'], '/');
    
    // تعريف الـ DSN الخاص بـ PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
} else {
    // النسخة الاحتياطية (للتطوير المحلي إذا أردت)
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $db   = getenv('DB_NAME') ?: 'beauty_store_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $port = 5432;
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
