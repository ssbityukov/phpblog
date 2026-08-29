#!/bin/sh
# Заводит тестовую базу рядом с рабочей и открывает пользователю приложения
# схемы вида `<база>_*` — MigratorTest создаёт и сносит собственную схему,
# поэтому ему нужен не только доступ, но и право CREATE DATABASE.
# Это конфигурация для разработки; прод-пользователь таких прав не получает.
# Пароли берутся из окружения контейнера, чтобы не лежать в трекаемых файлах.
set -eu

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}\_%\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
