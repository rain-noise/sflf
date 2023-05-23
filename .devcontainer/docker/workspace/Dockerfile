FROM php:7.4-cli

# Use bash
SHELL ["/bin/bash", "-c"]

# Set Args Defaults
ARG USER_NAME=vscode
ARG USER_UID=1000
ARG USER_GID=${USER_UID}

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install DevContainer/Development tools and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client vim unzip jq \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

# Install Required extensions and depends
RUN apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev libzip-dev libicu-dev libcurl4 libcurl4-openssl-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure            gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)   gd bcmath zip curl intl mbstring xml

# Install MySQL/MariaDB extensions and depends
RUN apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install -j$(nproc)   mysqli

# Clean
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create User
RUN groupadd --gid ${USER_GID} ${USER_NAME} \
    && useradd -s /bin/bash --uid ${USER_UID} --gid ${USER_GID} -m ${USER_NAME} \
    && apt-get update \ 
    && apt-get install -y sudo \
    && echo ${USER_NAME} ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/${USER_NAME} \
    && chmod 0440 /etc/sudoers.d/${USER_NAME}
