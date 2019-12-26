#! /bin/bash
DEBIAN_FRONTEND=noninteractive
source /vagrant/.env
/vagrant/.setup_tz_20191210.sh

if [ -z "$MYSQL_ROOT_PASSWORD" ]
then
  MYSQL_ROOT_PASSWORD="$(date +%s | sha256sum | base64 | head -c 32)"
fi
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"

apt-get update
apt-get install -y mariadb-server

systemctl stop mariadb.service
sed -ri -e "s/^#?\s*bind-address\s+=.*$/bind-address = 0.0.0.0/" /etc/mysql/mariadb.conf.d/50-server.cnf
systemctl start mariadb.service

echo "CREATE USER IF NOT EXISTS $DB_USERNAME@'%' IDENTIFIED BY '$DB_PASSWORD';" | mysql -uroot -p$MYSQL_ROOT_PASSWORD
echo "CREATE DATABASE IF NOT EXISTS $DB_DATABASE;" | mysql -uroot -p$MYSQL_ROOT_PASSWORD
echo "GRANT ALL ON $DB_DATABASE.* TO $DB_USERNAME@'%';" | mysql -uroot -p$MYSQL_ROOT_PASSWORD
echo "FLUSH PRIVILEGES;" | mysql -uroot -p$MYSQL_ROOT_PASSWORD