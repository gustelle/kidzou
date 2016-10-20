# Procédure de MISE EN PROD #

* aller sur [DeployHQ](https://kidzou.deployhq.com/projects/kidzou-web/deployments) pour deployer le commit 2dcd78
*
* sudo nano /etc/apache2/mods-available/pagespeed.conf
* mettre en commentaire le filtre defer_javascript
*
* sudo touch /var/cache/mod_pagespeed/cache.flush
* sudo apt-get update
* sudo apt-get upgrade
* sudo service apache2 restart


