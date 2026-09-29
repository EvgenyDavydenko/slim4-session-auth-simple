FROM php:8.2.30-fpm-trixie

RUN apt-get update && apt-get install -y --no-install-recommends \
    git zip unzip nano curl wget procps \
    supervisor \
    nginx gettext-base

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/supervisord.conf

WORKDIR /var/www/backend
# для согласования прав доступа к файлам контейнера и хост-системы
# изменим UID и GID служебного аккаунта www-data с системного до пользовательского
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
RUN chown -R www-data:www-data /var/www

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
