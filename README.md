# Procédure de MISE EN PROD #

* aller sur [DeployHQ](https://kidzou.deployhq.com/projects/kidzou-web/deployments) pour deployer le commit 2dcd78
*
* sudo nano /etc/apache2/mods-available/pagespeed.conf
* mettre en commentaire le filtre defer_javascript
*
* sudo nano /var/www/.htaccess

#######################################################################
# Reecriture des URLS metropole (suppression de la feature metropole) #
#######################################################################
RewriteRule ^(lille|littoral|regional|valenciennes)/(agenda|les-recommandations)/?$ /rubrique/$2  [R=301,NC,L]
RewriteRule ^(lille|littoral|regional|valenciennes)/(rubrique|ville|divers|age|famille|nature)/(.*)/?$ /$2/$3 [R=301,NC,L]

*
* sudo touch /var/cache/mod_pagespeed/cache.flush
* sudo apt-get update
* sudo apt-get upgrade
* sudo service apache2 restart
*
*
* Mettre à jour les réglages du thème, les menus, les réglages de permaliens


