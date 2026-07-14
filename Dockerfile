FROM php:8.5-fpm-alpine

WORKDIR /var/www/html
COPY ./src /var/www/html

# Install MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# opcache is already built-in in 8.5, just configure it
# Move production ini and add opcache config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    { \
      echo "opcache.enable=1"; \
      echo "opcache.memory_consumption=128"; \
      echo "opcache.interned_strings_buffer=8"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/10-opcache.ini && \
    { \
      echo "pm = dynamic"; \
      echo "pm.max_children = 20"; \
      echo "pm.start_servers = 5"; \
      echo "pm.min_spare_servers = 3"; \
      echo "pm.max_spare_servers = 10"; \
      echo "pm.max_requests = 500"; \
    } > /usr/local/etc/php-fpm.d/zz-k8s.conf

# Set permissions for k8s emptyDir mount
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]