#!/bin/bash
cd ~

DISTRO=$(lsb_release -i | cut -d: -f2 | sed 's/^\s//g')


sudo apt install -y software-properties-common

#Get current php version
PHP_VER_ORIG=$(readlink /etc/alternatives/php)

if [[ "$DISTRO" = "Debian" ]]; then
    #Install php 8.0 and dependencies

    echo "Detected DEBIAN distribution"
    echo "Installing some pre-reqs"
    sudo apt install -y ca-certificates apt-transport-https gnupg2
    
    echo "Adding php source sury-php.list"
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list

    echo "Adding apt key"
    wget -qO - https://packages.sury.org/php/apt.gpg | sudo apt-key add -
elif [[ "$DISTRO" = "Ubuntu" ]]; then
    echo "Detected UBUNTU distribution"
    echo "Adding ppa:ondrej/php"
    sudo add-apt-repository ppa:ondrej/php
else
    echo "Could not determine distribution, failed!"
    exit 1;
fi

sudo apt update
sudo apt install -y php8.0 php8.0-xml php8.0-curl php8.0-sqlite3 php8.0-pgsql php8.0-fpm php8.0-mbstring

#Install composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

#Clone fusionapi repo
git clone https://github.com/codemonkey76/fusionapi
cd fusionapi
composer install
cp .env.example .env
php artisan key:generate

echo "Make directory /etc/fusionapi"
sudo mkdir /etc/fusionapi

echo "Make database.sqlite file"
sudo touch /etc/fusionapi/database.sqlite

echo "Change ownership & permissions of database file"
sudo chown www-data:www-data /etc/fusionapi/database.sqlite
sudo chmod 777 /etc/fusionapi
sudo chmod 777 /etc/fusionapi/database.sqlite

echo "Migrate database"
php artisan migrate

echo "Setup permissions for laravel to access logs"
sudo chmod -R 777 storage

sudo update-alternatives --set php $PHP_VER_ORIG

# Alter the .env to grab the local FusionPBX database password
sed -i "s/DB2_PASSWORD=$/DB2_PASSWORD=$(cat /etc/fusionpbx/config.php | grep db_password | awk -F\' '{print $2}')/g" .env

# alter the nginx config so that the servername is reflected

sudo cp ~/fusionapi/nginx/fusionapi /etc/fusionapi/nginx.conf
sudo sed -i "s/server_name ;/server_name $(hostname -f);/g" /etc/fusionapi/nginx.conf
sudo sed -i "s:root ;:root $(pwd)/public;:g" /etc/fusionapi/nginx.conf
sudo ln -s /etc/fusionapi/nginx.conf /etc/nginx/sites-enabled/fusion-api

sudo /etc/init.d/nginx reload
sudo iptables -A INPUT -p tcp --dport 82 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT
sudo iptables -A OUTPUT -p tcp --sport 82 -m conntrack --ctstate ESTABLISHED -j ACCEPT
sudo iptables-save | sudo tee /etc/iptables/rules.v4

crontab -l | { cat; echo "* * * * * cd ~/fusionapi && php artisan schedule:run >> /dev/null 2>&1"; } | crontab -
