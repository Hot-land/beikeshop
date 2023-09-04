FROM php:8.1-cli

ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=${USER_UID}

ARG LOCALE=en_US.UTF-8
ARG TIME_ZONE=UTC

ENV PKG="bash-completion curl dnsutils git jq locales mariadb-client rsync tree unzip vim wget zip"
ENV PKG_LIB="libc-client-dev libfreetype6-dev libjpeg62-turbo-dev libkrb5-dev libonig-dev libpng-dev libpq-dev libxslt-dev libzip-dev"
ENV COMPOSE_ALLOW_SUPERUSER 1
ENV DEBIAN_FRONTEND noninteractive
ENV LANG=${LOCALE}
ENV TZ=${TIME_ZONE}

RUN apt-get update
RUN apt-get install -y $PKG $PKG_LIB
RUN pecl install redis-5.3.4
RUN docker-php-ext-enable redis
RUN pecl install xdebug-3.1.1
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install gd
RUN docker-php-ext-install intl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip

# locale
RUN sed -i -E "s/# (${LOCALE})/\1/" /etc/locale.gen \
  && locale-gen ${LOCALE} \
  && dpkg-reconfigure locales \
  && update-locale LANG=${LOCALE} \
  #
  # timezone
  && ln -snf /usr/share/zoneinfo/${TIME_ZONE} /etc/localtime && echo ${TIME_ZONE} > /etc/timezone \
  #
  # user
  && groupadd --gid ${USER_GID} ${USERNAME} \
  && useradd -s /bin/bash --uid ${USER_UID} --gid ${USER_GID} -m ${USERNAME} \
  && apt-get install -y sudo \
  && echo ${USERNAME} ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/${USERNAME} \
  && chmod 0440 /etc/sudoers.d/${USERNAME}
