FROM ubuntu:18.04
COPY . /app
# Check whether we've got a defined laravel APP_KEY or not, and if not, create one!
RUN /app/.setup_tz_20191210.sh
RUN /app/.setup_app_20191210.sh
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && \
    ln -sf /proc/self/fd/1 /var/log/apache2/access.log && \
    ln -sf /proc/self/fd/1 /var/log/apache2/error.log
HEALTHCHECK --interval=5s --timeout=3s --retries=3 CMD curl -f http://localhost || exit 1
CMD until service php7.2-fpm status ; do service php7.2-fpm start ; done ; apachectl -D FOREGROUND 
