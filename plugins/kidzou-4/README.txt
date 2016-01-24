<h4>Hernie Discale V2</h4>
<h5>Janvier 2015</h5>
<ul>
	<li>Validation de l'import Familyscope par extension chrome</li>
	<li>Refactoring du code d'import pour etre plus flexible par rapport aux différentes sources de données</li>
	<li>Import d'événements Facebook par l'extension chrome</li>
	<li>Correction d'un bug qui empeche la mise à jour de la checkbox 'recurrence' dans l'admin</li>

	<li>TODO : Correction d'un bug d'affichage des thumbnail dans le widget des customer related posts</li>
	<li>TODO : Correction d'un bug qui double les clients à la créatio d'un nouveau client depuis un post</li>
	<li>TODO : Correction d'un bug affecte le même clients à tous les posts dan le widget des customer related posts</li>

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





