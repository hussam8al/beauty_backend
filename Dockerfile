FROM php:8.2-apache

# تثبيت إضافات PDO MySQL اللازمة للاتصال بقاعدة البيانات
RUN docker-php-ext-install pdo pdo_mysql

# نسخ ملفات المشروع إلى السيرفر
COPY . /var/www/html/

# ضبط الصلاحيات للمجلدات
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تفعيل موديل Rewrite في Apache (مهم للروابط)
RUN a2enmod rewrite

# المنفذ الافتراضي
EXPOSE 80
