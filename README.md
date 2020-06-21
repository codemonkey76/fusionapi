##Instructions

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

##Step 1. Create nginx config file
Step 1. Create nginx config file in /etc/nginx/sites-available/fusionapi
```
server {
        listen 81;
        listen [::]:81;


        root /var/www/fusionapi;

        index index.php index.html index.htm index.nginx-debian.html;

        server_name s02.alphapbx.com.au;

        location / {
                try_files $uri $uri/ =404;
        }

        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php7.3-fpm.sock;
        }

        location ~ /\.ht {
                deny all;
        }
}
```

Step 2. Add to sites-enabled
ln -s /etc/nginx/sites-available/fusionapi /etc/nginx/sites-enabled/fusionapi

Step 3. Restart nginx
service nginx restart

Step 4. Allow port 81 through IP Tables
sudo iptables -A INPUT -p tcp --dport 81 -m conntrack --ctstate NEW,ESTABLISHED -j ACCEPT
sudo iptables -A OUTPUT -p tcp --sport 81 -m conntrack --ctstate ESTABLISHED -j ACCEPT

Step 5. Save rules
dpkg-reconfigure iptables-persistent
"Choose yes to save rules"

Step 6. Create index.php in /var/www/fusionapi containing
<?php
phpinfo();
?>

Step 7. Set permissions
chown www-data www-data -R /var/www/fusionapi

Step 8. Visit port 81 ensure that php info page is displayed