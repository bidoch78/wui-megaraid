FROM debian:bookworm-slim

ARG megaraid_wrapper_from;
ARG megaraid_app_version;

RUN apt-get update
RUN apt-get -y upgrade

RUN apt-get -y install lsb-release gnupg cron curl supervisor nginx php8.2-fpm
RUN apt-get -y install php8.2-sqlite3 php8.2-pdo-sqlite smartmontools

RUN curl https://hwraid.le-vert.net/debian/hwraid.le-vert.net.gpg.key | gpg --dearmor | tee /etc/apt/trusted.gpg.d/hwraid-archive-keyring.gpg > /dev/null

#RUN DIST=$(lsb_release -c | grep "Codename:" | awk '{print $2}')
#RUN echo "deb [signed-by=/etc/apt/trusted.gpg.d/hwraid-archive-keyring.gpg] http://hwraid.le-vert.net/debian $DIST main"
#No version for bookworm (use bullseye)
RUN echo "deb [signed-by=/etc/apt/trusted.gpg.d/hwraid-archive-keyring.gpg] http://hwraid.le-vert.net/debian bullseye main" > /etc/apt/sources.list.d/hwraid.list

RUN apt-get update && apt-get -y install megacli && apt-get clean

RUN touch /var/log/php8.2-fpm.log
RUN mkdir -p /var/run/php

# Copy new entry point file
COPY docker-deploy-entrypoint-custom /usr/local/bin/

# nginx vhost
COPY ./nginx/default.conf /etc/nginx/sites-available/default

# php fpm config
COPY ./php/www.conf /etc/php/8.2/fpm/pool.d/www.conf

# php ini
COPY ./php/local.ini /etc/php/8.2/fpm/conf.d

#php opcache.ini
#COPY ./php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# copy source
COPY ./build_app/app /var/www

# Copy supervisor config
COPY ./supervisor/app-supervisor.conf /etc/supervisord.conf

ENTRYPOINT ["/usr/local/bin/docker-deploy-entrypoint-custom"]

EXPOSE 80

STOPSIGNAL SIGTERM