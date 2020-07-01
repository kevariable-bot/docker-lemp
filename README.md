# LEMP STACK on Docker

## How it's work?

- ## Create structur folder like this

  ```shell
  ├── docker-compose.yml
  ├── nginx
  │   └── vhost.conf
  └── project
      └── index.php
  ```

- ## We fill docker-compose.yml like this

  ```docker
  version: '3'
  services:
    # Container web
    web:
      # Docker Image Nginx
      image: nginx:1.16-alpine
      # port for running PHP in browser http://localhost:8010
      ports:
        - 8010:80
      volumes:
        # we project php index include nginx
        - ./project:/var/www/html
        # This is configuration nginx
        - ./nginx/vhost.conf:/etc/nginx/conf.d/default.conf
      links:
        - php
    # Container php
    php:
      image: php:7.4-fpm-alpine
      # we project php index
      volumes:
        - ./project:/var/www/html
  ```

- ## vhost.conf

  ```nginx
  server {
    listen 80;
    index index.php;
    server_name php-docker;
    root /var/www/html;

    location / {
      try_files $uri $uri/ /index.php?query_string;
    }

    location ~\.php$ {
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      fastcgi_split_path_info ^(.+\.php)(/.+)$;
      fastcgi_pass php:9000;
      fastcgi_index index.php;
    }
  }
  ```

- ## index.php

  ```php
  <?php
    phpinfo();
  ?>
  ```

- ## Now we test running with docker-compose command like this

  ```shell
  $ docker-compose -f "docker-compose.yml" up -d --build
  ```

  **output :**

  ```
  Creating network "dockerlempstack_default" with the default driver
  Creating dockerlempstack_php_1 ... done
  Creating dockerlempstack_web_1 ... done
  ```

  Check Images

  ```shell
  docker images
  REPOSITORY          TAG                 IMAGE ID            CREATED             SIZE
  mariadb             10.4                2f11cf2ec189        7 days ago          355MB
  php                 7.4-fpm-alpine      680410c3cbca        7 days ago          83.5MB
  nginx               1.16-alpine         aaad4724567b        2 months ago        21.2MB
  ```

  And then open your favorite Browser http://localhost:8010

  ![phpinfo](https://i.imgur.com/O0V87yx.png)

  We finished running PHP and Nginx, now what?

  Stopped Docker service :

  ```shell
  $ docker-compose -f "docker-compose.yml" down
  ```

  **output :**

  ```
  Stopping dockerlempstack_web_1 ... done
  Stopping dockerlempstack_php_1 ... done
  Removing dockerlempstack_web_1 ... done
  Removing dockerlempstack_php_1 ... done
  Removing network dockerlempstack_default
  ```

- ## We create MySql connect to PHP with custom image

  Structur folder like this

  ```shell
  ├── README.md
  ├── data
  │   ├── custom-image-php7.3
  │   │   └── Dockerfile
  │   └── mysql-data
  ├── docker-compose.yml
  ├── nginx
  │   └── vhost.conf
  └── project
      └── index.php
      └── mysqli.php
  ```

- ## in the Dockerfile we fill it like this

  ```Dockerfile
  # Gunakan bas image resmi dari PHP
  FROM php:7.4-fpm-alpine

  # Install beberapa library tambahan yang dibutuhkan oleh ektensi GD dan ZIP
  RUN apk add --no-cache\
      freetype libjpeg-turbo libpng libwebp \
      freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
      libzip-dev

  # Lakukan konfigurasi awal sebelum menginstal ektensi GD
  RUN docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
      --with-webp

  # Install ektensi yang kita butuhkan
  RUN docker-php-ext-install -j $(nproc) \
      gd mysqli opcache pdo_mysql zip

  # Urutkan ulang ektensi yang baru saja diinstall
  RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-*.ini \
      && docker-php-ext-enable --ini-name 20-gd.ini gd \
      && docker-php-ext-enable --ini-name 10-mysqli.ini mysqli \
      && docker-php-ext-enable --ini-name 05-opcache.ini opcache \
      && docker-php-ext-enable --ini-name 10-pdo_mysql.ini pdo_mysql \
      && docker-php-ext-enable --ini-name 20-sodium.ini sodium \
      && docker-php-ext-enable --ini-name 20-zip.ini zip
  ```

  We create new image php-custom:7.4-fpm-alpine in data/custom-image-php7.4

  wait for it to finish and make sure you are connected to the internet
  command like this:

  ```shell
  docker build -t php-custom:7.4-fpm-alpine data/custom-image-php7.4/
  ```

  **last Ouput like this**

  ```docker
  ...
  Successfully built f1d60d02743c
  Successfully tagged php-custom:7.4-fpm-alpine
  ```

  this command will produce an image with the name php-custom, with the same version as the original image version, 7.4-fpm-alpine

  ```shell
  $ docker images
  ```

  **Output :**

  ```shell
  $ docker images
  REPOSITORY          TAG                 IMAGE ID            CREATED             SIZE
  php-custom          7.4-fpm-alpine      f1d60d02743c        4 minutes ago       409MB
  <none>              <none>              488fbba72cc7        About an hour ago   90.5MB
  mariadb             10.4                2f11cf2ec189        7 days ago          355MB
  php                 7.4-fpm-alpine      680410c3cbca        7 days ago          83.5MB
  nginx               1.16-alpine         aaad4724567b        2 months ago        21.2MB
  ```

- ## we added in docker-compose.yml like this

  ```docker
  version: "3"
  services:
    # Container web
    web:
      # Docker Image Nginx
      image: nginx:1.16-alpine
      # port for running PHP in browser http://localhost:8010
      ports:
        - 8010:80
      volumes:
        # we project php index include nginx
        - ./project:/var/www/html
        # This is configuration nginx
        - ./nginx/vhost.conf:/etc/nginx/conf.d/default.conf
      links:
        - php
    # Container php
    php:
      # we change php:7.4-fpm-alpine to be php-custom:7.4-fpm-alpine
      image: php-custom:7.4-fpm-alpine
      # we project php index
      volumes:
        - ./project:/var/www/html
      # this environment equate environment in mysql container
      environment:
        MYSQL_HOST: mysql
        MYSQL_USER: local
        MYSQL_PASSWORD: secret
        MYSQL_DATABASE: mynewdb
      depends_on:
        - mysql
    # Container mysql
    mysql:
      image: mariadb:10.4
      command: --default-authentication-plugin=mysql_native_password --bind-address=0.0.0.0
      volumes:
        - ./data/mysql:/var/lib/mysql
      # port for acces mysql in docker exec -it [container mysql] bash
      ports:
        - 13306:3306
      environment:
        MYSQL_ROOT_PASSWORD: secret
        MYSQL_USER: local
        MYSQL_PASSWORD: secret
        MYSQL_DATABASE: mynewdb
  ```

  and then Build this

  ```shell
  $ docker-compose -f "docker-compose.yml" up -d --build
  ```

- ## set MySql with docker exec

  ```shell
  docker exec -it [Container MySql] bash
  ```

  and then

  ```shell
  $ docker exec -it dockerlempstack_mysql_1 bash
  root@d052719a9994:/# mysql_secure_installation
  ```

  please set a new root password and don't forget to peruse

  If it is already

we enter mysql to create a new user according to the environment in the mysql container

```shell
root@d052719a9994:/# mysql -u root -p
```

in mysql

```mysql
MariaDB [(none)]> SELECT host, user FROM mysql.user;
+--------------+------+
| Host         | User |
+--------------+------+
| 127.0.0.1    | root |
| ::1          | root |
| d052719a9994 |      |
| d052719a9994 | root |
| localhost    |      |
| localhost    | root |
+--------------+------+
6 rows in set (0.096 sec)

MariaDB [(none)]> CREATE USER 'local'@'%' IDENTIFIED BY 'secret';
Query OK, 0 rows affected (0.117 sec)

MariaDB [(none)]> grant all on *.* to 'local'@'%';
Query OK, 0 rows affected (0.102 sec)

MariaDB [(none)]> SELECT host, user FROM mysql.user;
+--------------+-------+
| Host         | User  |
+--------------+-------+
| %            | local |
| 127.0.0.1    | root  |
| ::1          | root  |
| d052719a9994 |       |
| d052719a9994 | root  |
| localhost    |       |
| localhost    | root  |
+--------------+-------+
7 rows in set (0.002 sec)
```

we can access it from terminal

```shell
~/doc/Docker LEMP Stack took 2s
$ mysql -h 127.0.0.1 -P 13306 -u local -p
Enter password: secret
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 24
Server version: 10.4.11-MariaDB-1:10.4.11+maria~bionic mariadb.org binary distribution

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]>
```

- ## mysqli.php for testing

  ```php
  <?php

  $db = new mysqli(
    $_ENV['MYSQL_HOST'],
    $_ENV['MYSQL_USER'],
    $_ENV['MYSQL_PASSWORD'],
    // Dont forget create database 'mynewdb'
    $_ENV['MYSQL_DATABASE'],
    $_ENV['MYSQL_PORT'] ?? 3306
  );

  var_dump($db);
  ```

  run docker with docker-compose up

  see in your browser :)

  and result like this

  ![result](https://i.imgur.com/N99OSTe.png)
