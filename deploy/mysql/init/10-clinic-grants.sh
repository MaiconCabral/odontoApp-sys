#!/bin/sh
# Runs once, when the MySQL volume is created. The app user gets every database whose name
# starts with "odonto_": the central one (odonto_app) and one per clinic (odonto_tenant_<id>),
# which the app creates and drops itself. It never needs root.
set -e

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
GRANT ALL PRIVILEGES ON \`odonto\_%\`.* TO '$MYSQL_USER'@'%';
FLUSH PRIVILEGES;
SQL
