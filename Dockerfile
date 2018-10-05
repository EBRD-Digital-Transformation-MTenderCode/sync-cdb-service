FROM eprocurementsystems/php-base

COPY /src /var/www/service/

COPY /nginx.conf /etc/nginx/nginx.conf
COPY /www.conf /etc/php-fpm.d/www.conf
COPY /run.sh /bin/run.sh

RUN chmod a+x /bin/run.sh

EXPOSE 80
CMD ["/bin/run.sh"]
