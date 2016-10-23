# Procédure de MISE EN PROD #
*
## Configuration Pagespeed ##
*
* sudo nano /etc/apache2/mods-available/pagespeed.conf
* mettre en commentaire le filtre defer_javascript
* sudo touch /var/cache/mod_pagespeed/cache.flush

## Reecriture des URLS ##
*
* sudo nano /var/www/.htaccess
*
#######################################################################
# Reecriture des URLS metropole (suppression de la feature metropole) #
#######################################################################
RewriteRule ^(lille|littoral|regional|valenciennes)/(agenda|les-recommandations)/?$ /rubrique/$2  [R=301,NC,L]
RewriteRule ^(lille|littoral|regional|valenciennes)/(rubrique|ville|divers|age|famille|nature)/(.*)/?$ /$2/$3 [R=301,NC,L]

## Upgrade du socle ##
* sudo apt-get update
* sudo apt-get upgrade

## Redémarrage des services ##
* sudo service apache2 restart

## Téléchargement du thème ##
* Télécharger Extra
* Activer le thème Extra-child
* Mettre à jour les réglages du thème, les menus, les réglages de permaliens

## Suppression du role Contributeur Pro ##
* Supprimer tous les droits sur le role "Contributeur Pro" dans Utilisateurs/Capabilities
* Basculer tous les users Contributeur Pro -> Contributeur ("Tous les utilisateurs > Contributeur Pro > CHanger de role pour ...")

