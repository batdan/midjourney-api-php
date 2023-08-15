FROM composer:latest
WORKDIR /app
ADD . /app
RUN composer install
ENTRYPOINT ["sh", "runserver.sh"]
EXPOSE 8062

