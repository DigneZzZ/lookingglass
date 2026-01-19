FROM php:8.4-fpm-bullseye

RUN apt-get update && \
    apt-get --no-install-recommends -y install iputils-ping mtr traceroute iproute2 libfcgi-bin && \
    rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY docker/php-fpm/src/config.php config.php

# Create entrypoint script to generate speedtest files on first run
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Generate speedtest files if they do not exist\n\
if [ ! -f /var/www/html/files/100MB.bin ]; then\n\
    echo "Generating speedtest files..."\n\
    mkdir -p /var/www/html/files\n\
    dd if=/dev/zero of=/var/www/html/files/100MB.bin bs=1M count=100 2>/dev/null\n\
    dd if=/dev/zero of=/var/www/html/files/1GB.bin bs=1M count=1024 2>/dev/null\n\
    dd if=/dev/zero of=/var/www/html/files/10GB.bin bs=1M count=10240 2>/dev/null\n\
    chown -R www-data:www-data /var/www/html/files\n\
    echo "Speedtest files generated."\n\
fi\n\
\n\
exec php-fpm\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
