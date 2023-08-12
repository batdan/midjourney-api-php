FROM composer:latest
WORKDIR /app
ADD . /app
RUN ls -lah 
ENTRYPOINT ["sh", "runserver.sh"]
EXPOSE 8062

