<h4>Fourty</h4>
<h5>Mars 2016</h5>
<ul>
	<li>Affichage des PostPreview sans effet de fade in dans les Portfolio</li>
	<li>fix sur la notification de vote mobile</li>
	<li>fix sur les plugins d'import chrome</li>

	<li>TODO : React Vote</li>

	<li>TODO : Reactisation full de la Home Page</li>
	<li>TODO : les auteurs voient la liste des autres article d'un client mais ils n'ont pas le droit d'y accéder...que fait-on ?</li>
	<li>TODO : Fix de paramétrage, les contributeurs peuvent créer des articles en recette</li>
	<li>TODO : selection d'un client à l'import depuis le plugin chrome</li>
	<li>TODO : Import de nouvelles sources depuis le plugin chrome</li>
</ul>

<h4>ImportExtension v3</h4>
<h5>Saint Valentin</h5>
<ul>
	<li>fix : un post peut ne pas avoir de date d'event</li>
	<li>fix : bug étrange dans Kidzou_Customer::getCustomerIDByPostID() qui causait un bug dans les contenus par défaut d'un nouveau post</li>
	<li>Passage à ReactJS sur le front / Kidzou_Vote</li>
	<li>Correctif sur l'affichage du website et phone_number</li>

</ul>


<h4>ImportExtension v3</h4>
<h5>Fevrier 2015</h5>
<ul>
	<li>Fix : Amélioration du composant React CheckBoxGroup et Fix sur la date de fin de récurrence qd l'événement ne se termine jamais</li>
	<li>Fix : Bug dans le CRON de dépublication des events, suite à refactoring de Kidzou_GeoDS</li>
	<li>Fix : Renforcement des controles dans les events pour éviter des cas ou end_date est invalide</li>
</ul>

<h4>ImportExtension v3</h4>
<h5>Fevrier 2015</h5>
<ul>
	
	<li>Refactoring / modularisation de Kidzou_Metaboxes_Event pour isoler un composant d'import</li>
	<li>Migration du composant d'import Facebook en ReactJS et modularisation pour utilisation dans un Widget</li>
	<li>Passage à React pour toutes les metabox Kidzou</li>
	<li>Refactoring pour mieux isoler les metaboxes les unes des autres, les rendre plus modulaires</li>
	<li>Paramétrisation des permissions</li>
	<li>Fix : import facebook / format de date 2016-03-26T20:30:00+0100</li>
	<li>Fix : import facebook / Remplacement de titre et contenu lorsqu'ils existent</li>
	<li>Fix : la CSS du hint sur les events en recette</li>
	<li>Fix : import facebook / champ telephone rempli à 'undefined'</li>
	<li>Fix : adresse non enregistrée pour les contribs lorsqu'on choisi un lieu</li>
	<li>Fix des evements / Recurrence dispo uniquement pour user > auteur</li>
	<li>Fix de l'adresse / Bouton "utiliser cette adresse pour le client" uniquement visible pour user > auteur</li>
	<li>Fix : Utiliser cette adresse pour le client uniquement si un client est selectionné...sinon disabled</li>
	<li>Fix : import facebook / Widget dispo uniquement pour user > contributeur pro</li>
	<li>Fix : setState(...): Can only update a mounted or mounting component. This usually means you called setState() on an unmounted component. This is a no-op. Please check the code for the HintMessage component.</li>
	<li>Refactoring de Kidzou_API et Kidzou_Customer</li>
	<li>Fix : import facebook, les adresses BE sont considérées correctes dans /api/content/create_post</li>

</ul>

<h4>Améliorations admin</h4>
<h5>Janvier 2015</h5>
<ul>

	<li>Correction d'un bug qui double les clients à la créatio d'un nouveau client depuis un post</li>
	<li>Correction d'un bug qui propose le lieu alors qu'il est déjà renseigné dans la saisie d'un event</li>
	<li>Possibilité d'éditer le client depuis un lien dans l'écran d'édition d'un post</li>
	<li>Affichage des posts du même client dans l'écran d'édition d'un post</li>
	<li>TODO : possibilité de naviguer vers les posts du client depuis l'écran d'édition d'un client</li>

</ul>

<h4>Hernie Discale V2</h4>
<h5>Janvier 2015</h5>
<ul>
	<li>Validation de l'import Familyscope par extension chrome</li>
	<li>Refactoring du code d'import pour etre plus flexible par rapport aux différentes sources de données</li>
	<li>Import d'événements Facebook par l'extension chrome</li>
	<li>Correction d'un bug qui empeche la mise à jour de la checkbox 'recurrence' dans l'admin</li>
	<li>Refactoring de Kidzou_Geolocator et Kidzou_Geofilter pour réinjecter le code dans Kidzou_Metropole et Kidzou_Geoloc</li>
	<li>Correction d'un bug qui n'affiche pas les bons contenus pour la métropole régionale</li>
	<li>Correction d'un bug lorsque l'on change de métropole et qu'on l'on navigue, on revient à l'ancienne métropole</li>
	<li>Correction d'un bug qui ne retrouve pas les cutomer related posts</li>
	<li>Correction d'un bug qui affiche toujours le même thumbnail dans le widget des customer related posts</li>

	<li>TODO : Correction d'un bug qui double les clients à la créatio d'un nouveau client depuis un post</li>
	<li>TODO : Correction d'un bug qui propose le lieu alors qu'il est déjà renseigné dans la saisie d'un event</li>

</ul>

<h4>Chrome Extention - Import de contenu</h4>
<h5>Janvier 2015</h5>
<ul>
	<li>Refactoring des API et des classes Admin pour permettre l'import de contenu par API depuis une extension Chrome</li>
</ul>

<h4>NOEL 2015</h4>
<h5>Janvier 2015</h5>
<ul>
	<li>Amélioration de la Documentation PHP</li>
	<li>Filtrage du widget CustomerPosts pour exclure les events non actifs et le post encours d'affichage</li>
	<li>Possibilité pour le user voir tous les contenus du site (pas de filtrage par métropole)</li>
</ul>

<h4>SEO-DEC</h4>
<h5>décembre 2015</h5>
<ul>
	<li>Améliorations / corrections d'erreurs suite rapport Woorank</li>
</ul>

<h4>Performances API-V38</h4>
<h5>novembre 2015</h5>
<ul>
	<li>Optimisations de performance au travers du chargement des JS/CSS</li>
	<li>Amélioration des import d'événements Facebook : le contenu Facebook est ajouté à la fin du contenu pré-existant</li>
</ul>

<h4>Proximite V30</h4>
<h5>novembre 2015</h5>
<ul>
	<li>Refonte de la fonction "A proximité"</li>
</ul>


<h4>API V27</h4>
<h5>7 novembre 2015</h5>
<ul>
	<li>Nouvelle Fonction : Creation d'un client depuis une article</li>
	<li>Changement de framework JS pour appel à Google Maps PlaceComplete : Selectize</li>
	<li>Refactoring des widgets d'admin pour la gestion des lieux (class-kidzou-admin-place)</li>
</ul>

<h4>API V20</h4>
<h5>28 octobre 2015</h5>
<ul>
	<li>Nouvelle Fonction : Import d'événement Facebook dans un article</li>
</ul>

<h4>API V16</h4>
<h5>25 octobre 2015</h5>
<ul>
	<li>Re-engineering de style.css pour optimisation Responsive, notamment sur iPad</li>
	<li>Copyright 2015</li>
	<li>Nouvelle Fonction : filtrage dans les pages qui utilisent le Portfolio Kidzou</li>
	<li>Les Archives sont maintenant triées par nombre de votes</li>
	<li>Choix de la métropole dans le Header par liste déroulante</li>
</ul>

<h4>API V12</h4>
<h5>Début octobre 2015</h5>
<ul>
	<li>Fix sur l'API content/get_related_posts</li>
	<li>Fix sur la Kidzou_GeoHelper::get_post_location() qui ne remonte pas l'adresse pour les customers</li>
	<li>Ajout d'un contenu par defaut lors de l'édition d'un post</li>
	<li>Ajout d'une API pour récupérer l'avatar d'un user dans l'API Utils</li>
	<li>Correctif sur la remontée de latitude/longitude pour les Customers dans wp-admin</li>
</ul>

<h4>API V7</h4>
<h5>Début octobre 2015</h5>
<ul>
	<li>Upgrade général des plugins</li>
	<li>Passage à WP 4.3.1</li>
	<li>Nettoyage des plugins Nextend</li>
	<li>Rebranchement de HHVM</li>
	<li>Correctif sur les dates d'événement</li>
	<li>Correctif sur le nom du répertoire de téléchargement des media</li>
	<li>Préparation des API pour l'App mobile</li>
</ul>

<h4>Paques 2015</h4>
<h5>Vacances de Pâques 2015</h5>
<ul>
	<li>Ouverture de nouvelles API pour l'appli mobile</li>
	<li>Corrections sur l'habillage publicitaire</li>
	<li>Upgrade général des plugins</li>
	<li>Passage à WP 4.2.2</li>
</ul>

<h4>St Cyprien</h4>
<h5>Vacances de février 2015</h5>
<ul>
	<li>Corrections sur le traitement des événements à archiver et événements recurrents</li>
	<li>Corrections sur le traitement qui initialise les votes</li>
	<li>Corrections de problèmes de mise en forme sur la page de recherche</li>
	<li>Suppression du message "aucun contenu" lorsqu'aucun post contextuel (CRP) n'est trouvé</li>
	<li>Correction du bug des Markers en double sur la carte "A proximité"</li>
	<li>Tenir compte des métropoles dans les Notifications</li>
	<li>Résultats de recherche filtrés par Métropole</li>
	<li>Possibilité de bypasser le filtre de contenu par métropole (paramètre de réglage)</li>
	<li>Amélioration du formulaire de login : le formulaire ne s'affiche pas si le user est logué</li>
	<li>La page "proposer une sortie" est désormais clickable sur mobile</li>
	<li>Résolution d'une erreur Javascript au rafraichissement des votes lorsque le use bloque les cookies</li>
	<li>Tentative de résolution du bug de récupération des listes Mailchimp dans les réglages Kidzou</li>
</ul>


<h4>Navigation par métropole sur mobile</h4>
<h5>Dimanche 15 février 2015 </h5>
<ul>
	<li>- Possibilité de changer de métropole sur mobile</li>
	<li>- Correction du positionnement du menu mobile de l'élément <code>#et_top_search</code></li>
	<li>- Amélioration de la gestion des cookies et du local storage afin de ne pas causer d'erreur lorsque les utilisateurs bloquent les cookies</li>
	<li>- correction CSS dans le header pour la sélection d'une metropole</li>
	<li>- correction de ré-ecriture d'URL pour les liens de page à la premiere visite</li>
	<li>- Correction d'erreur de validation W3C  : pas d'attribut "async defer" sur les chargements JS lorsque l'attrbut src n'est pas spécifiée</li>
</ul>

<h5>Le Samedi 14 février 2015 </h5>
<ul>
	<li>- Ajout d'un JS polyfill pour supporter les apports DOM Level 4 (dom4.js)</li>
	<li>- Modification du rendu dans le header pour surligner la metropole courante de l'utilisateur</li>
	<li>- Support des CustomEvent Javascript sur les vieux navigateurs</li>
	<li>- Correction d'une erreur JS sur la fonction A proximité (<a href="https://bitbucket.org/kidzou_guillaume/kidzou/issue/19/a-proximit-erreur-javascript">Bug #19</a>)</li>
</ul>

<h5>Le Vendredi 13 février 2015 </h5>
<ul>
	<li>- Désactivation de Google Analytics pour Allo Famille</li>
	<li>- Désactivation du mode dev_mode de plugin Redux</li>
	<li>- erreur de Copier/coller à la recuperation des listes mailchimp dans les réglages Kidzou</li>
	<li>- Correctif sur la sync Geo Data Store au changement de statut des posts</li>
	<li>- Tentative de debug de la recuperation des listes mailchimp dans les réglages Kidzou</li>
</ul>

<h5>Le Jeudi 12 février 2015 </h5>
<ul>
	<li>- Ajout d'un filtre par "ville" sur les listes de post dans l'admin  </li>
	<li>- Changement sur le formulaire newsletter dans les notifs : réglage possible des champs de formulaire </li>
</ul>

<h4>Mise en forme Gravity forms</h4>
<h5>Le Mercredi 11 Février février 2015 </h5>
<ul>
	<li>- Mise en forme des formulaires Gravity Forms</li>
</ul>

<h5>Le Lundi O9 février 2015 </h5>
<ul>
	<li>- Correction du bug de tracking des evenements JS dans Google Analytics  </li>
	<li>- Découpage de <code>get_mailchimp_lists</code> dans les réglages pour réutilisation éventuelle de code </li>
</ul>

<h5>Le Dimanche 08 février 2015 </h5>
<ul>
	<li>- Réglages de la newsletter : Ajout de Header/footer </li>
	<li>- Réglages de la newsletter : Gestion de la fréquence d'affichage du formulaire de newsletter </li>
	<li>- Correction sur un décalage de date pour les récurrences mensuelles dans le cas d'une récurrence sur le 1er jour du mois</li>
	<li>- Inclusion des classes d'admin dans le PATH pour les CRON (nécessité pour <code>kidzou_events_scheduler</code> qui appelle Kidzou_Admin::save_meta)</li>
</ul>

<h5>Le Samedi 07 février 2015 </h5>
<ul>
	<li>- Fix sur le CRON <code>kidzou_events_scheduler</code> (synchro avec Geo Data Store)</li>
	<li>- Stockage local : gestion d'expiration dans le stockage local pour que les notifs puissent expirer</li>
	<li>- Mise en place de logs pour débugger le fetch des list mailchimp dans les réglages kidzou</li>
	<li>- Modularisation du composant Newsletter - pas de dépendance avec les notifications pour réutilisation ailleurs</li>
	<li>- Séparation des options de mise en forme Newslette / Notification</li>
</ul>

<h5>Le Vendredi 06 février 2015 - 23h</h5>
<ul>
	<li>- Formulaire de newsletter dans les notifications</li>
	<li>- Introduction de cette note de release</li>
	<li>- Icones plus jolies dans les reglages kidzou (font awesome)</li>
	<li>- Gestion des API Mailchimp</li>
	<li>- Amélioration du rendu des coeurs de vote : apparition progressive apres rafraichissement des données</li>
	<li>- Améliorations de mise en forme de la boite de notification (apparition plus tot, disparition plus tard, fond coloré)</li>
</ul>





