FROM php:8.2-apache

# Cài đặt các extension PHP cần thiết cho MySQLi và xử lý nâng cao
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Kích hoạt các module Apache cần thiết (rewrite, headers)
RUN a2enmod rewrite headers

# Cấu hình VirtualHost Apache (trỏ DocumentRoot vào /var/www/html/src)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Cấu hình PHP
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/custom.ini

# Đặt thư mục làm việc
WORKDIR /var/www/html

# Phân quyền thư mục uploads cho www-data
RUN mkdir -p /var/www/html/src/assets/uploads/tai-san \
    && chown -R www-data:www-data /var/www/html/src/assets/uploads \
    && chmod -R 775 /var/www/html/src/assets/uploads

EXPOSE 80

CMD ["apache2-foreground"]
