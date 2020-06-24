FROM php:fpm-alpine

LABEL maintainer="Yakup Arslan <me@yakupars.pw>"

RUN apk update && apk upgrade
RUN apk add git && apk add bash

# symfony executable fix
RUN addgroup -S _www && adduser -S _www -G _www

RUN wget https://get.symfony.com/cli/installer -O - | bash

COPY . /app

WORKDIR /app

CMD /root/.symfony/bin/symfony server:start --allow-http --no-tls

EXPOSE 8000