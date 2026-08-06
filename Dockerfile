FROM artifactory-edge-staging.cloud.capitalone.com/bacloudosimages-docker/cof-approved-images/ubuntu:24.04-260630
# Log into artifactory-edge-staging.cloud.capitalone.com FIRST! as described in:
# https://devnavhub.cloud.capitalone.com/docs/default/component/ci636034135/enforcing-authentication-in-artifactory-local-development-macos/?page_referrer=devportal#docker

USER root


RUN --mount=type=secret,id=apt-auth,target=/etc/apt/auth.conf DEBIAN_FRONTEND=noninteractive apt update && apt upgrade -y && apt-get install -y vim php libapache2-mod-php php-mysql
RUN --mount=type=secret,id=apt-auth,target=/etc/apt/auth.conf DEBIAN_FRONTEND=noninteractive apt-get install -y vim php apache2 \
ghostscript \
libapache2-mod-php \
mysql-server \
php \
php-bcmath \
php-curl \
php-imagick \
php-intl \
php-json \
php-mbstring \
php-mysql \
php-xml \
php-zip

RUN --mount=type=secret,id=apt-auth,target=/etc/apt/auth.conf DEBIAN_FRONTEND=noninteractive apt-get install -y unzip curl

COPY wordpress-7.0.1.zip /app/

RUN mkdir -p /srv/www && unzip /app/wordpress-7.0.1.zip -d /srv/www && chown -R www-data:www-data /srv/www
RUN cp /srv/www/wordpress/wp-config-sample.php /srv/www/wordpress/wp-config.php
RUN sed -i 's/database_name_here/wordpress/' /srv/www/wordpress/wp-config.php
RUN sed -i 's/username_here/wordpress/' /srv/www/wordpress/wp-config.php
RUN sed -i 's/password_here/changeme/' /srv/www/wordpress/wp-config.php
RUN sed -i "s/define( 'WP_DEBUG', false )/define( 'WP_DEBUG', true )/" /srv/www/wordpress/wp-config.php && \
    sed -i "/define( 'WP_DEBUG', true )/a define( 'WP_DEBUG_DISPLAY', false );" /srv/www/wordpress/wp-config.php
RUN sed -i "s/define( 'DB_HOST', 'localhost' )/define( 'DB_HOST', '127.0.0.1' )/" /srv/www/wordpress/wp-config.php

# Update config file using local script and values from https://api.wordpress.org/secret-key/1.1/salt/
COPY update_wp_config.sh /app/
COPY wp_config_values.txt /app/
RUN cd /app && bash update_wp_config.sh

# Salesforce JWT credentials
RUN printf "\ndefine('SPARK_SF_CONSUMER_KEY', '3MVG9C7wVcFOM8jln9TLHYFrMXm8Ay4AGMgZQJYvRdMd6AJ.b4crQ_UusaLXOd9wSK9ffnUJgkd4p4jotPePt');\ndefine('SPARK_SF_USERNAME',     'integrationuser@capitalone.com');\ndefine('SPARK_SF_AUTH_URL',     'https://test.salesforce.com/services/oauth2/token');\ndefine('SPARK_SF_KEY_PATH',     '/srv/www/wordpress/wp-content/secrets/server.key');\n" >> /srv/www/wordpress/wp-config.php

RUN printf '# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %%{REQUEST_FILENAME} !-f\nRewriteCond %%{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress\n' > /srv/www/wordpress/.htaccess

RUN chown -R www-data:www-data /srv/www

# Needed to import dev spark sandbox WP file
RUN echo "upload_max_filesize = 256M\npost_max_size = 256M" > /etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/apache2/conf.d/99-uploads.ini

COPY wordpress.conf /etc/apache2/sites-available/

RUN a2ensite wordpress
RUN a2enmod rewrite
RUN a2dissite 000-default
RUN sed -i 's/Listen 80/Listen 9090/' /etc/apache2/ports.conf

COPY init.sql /tmp/init.sql
COPY seed.sql /tmp/seed.sql

RUN service mysql start && \
    mysql -u root < /tmp/init.sql && \
    if [ -s /tmp/seed.sql ]; then mysql -u root wordpress < /tmp/seed.sql; fi && \
    rm /tmp/init.sql /tmp/seed.sql

COPY wp_startup.sh /app/startup.sh
RUN chmod +x /app/startup.sh

ENTRYPOINT ["/app/startup.sh"]