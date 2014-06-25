<?php

define(JSONCACHE_DIR, WP_PLUGIN_DIR."/kidzou/cache/json/");
define(EVENTS_CACHE_PREFIX, "event-");

function add_Kidzou_controller($controllers) {
  $controllers[] = 'SearchByPostName';
  $controllers[] = 'Events';
  $controllers[] = 'Vote';
  $controllers[] = 'Connections';
  $controllers[] = 'Auth';
  $controllers[] = 'Users';
  $controllers[] = 'Clients';
  return $controllers;
}

add_filter('json_api_controllers', 'add_Kidzou_controller');

function set_searchbypostname_controller_path() {
  return plugin_dir_path( __FILE__ ) ."searchbypostname.php";
}
function set_events_controller_path() {
  return plugin_dir_path( __FILE__ ) ."events.php";
}
function set_vote_controller_path() {
  return plugin_dir_path( __FILE__ ) ."vote.php";
}
function set_connections_controller_path() {
  return plugin_dir_path( __FILE__ ) ."connections.php";
}
function set_auth_controller_path() {
  return plugin_dir_path( __FILE__ ) ."auth.php";
}
function set_users_controller_path() {
  return plugin_dir_path( __FILE__ ) ."users.php";
}
function set_clients_controller_path() {
  return plugin_dir_path( __FILE__ ) ."clients.php";
}

add_filter('json_api_searchbypostname_controller_path', 'set_searchbypostname_controller_path');
add_filter('json_api_events_controller_path', 			    'set_events_controller_path');
add_filter('json_api_vote_controller_path', 			      'set_vote_controller_path');
add_filter('json_api_connections_controller_path', 		  'set_connections_controller_path');
add_filter('json_api_auth_controller_path', 			      'set_auth_controller_path');
add_filter('json_api_users_controller_path',            'set_users_controller_path');
add_filter('json_api_clients_controller_path',          'set_clients_controller_path');


/**
 * cette fonction ajoute à un tableau de résultat JSON API une entrée "cache" => "<json>" 
 * qui permet à WP Super Cache d'identifier le JSON comme objet cachable
 *
 * NON PROBANTE, fonction à retirer (mise en commentaires) 
 *
 * @see wp-cache-phase2.php
 * @return a cacheable Array (cacheable by WP SUper Cache)
 * @author kidzou
 **/
function cacheable_json ($json) 
{
	// if (!is_array($json))
	// 	return $json;

	//si la clé de cache est déjà présente, pas besoin d'aller plus loin
	// if (array_key_exists('cache', $json)) {
	// 	if ($json['cache']=='<json>')
	// 		return $json;
	// }

	//sinon, on ajoute/remplace la clé de cache
	// $json['cache'] = '<json>';

	return $json;
}

/**
 * mecanisme de cache JSON
 * expériences peu probantes avec WP Super Cache pour la gestion du cache
 * d'ou cette fonction reprise d'un code trouvé sur le web
 *
 * @return JSON
 * @param $key : la clé du flux JSON à retrouver en cache
 * @see http://stackoverflow.com/questions/11407514/caching-json-output-in-php
 * @author 
 **/
function getJSONCache($key) 
{
    $cache_life = 43200; // 1 mois en minutes (60*24*30*1)
    //if ($cache_life <= 0) return null;

    // fully-qualified filename
    $fqfname = getJSONCacheFileName($key);

    if (file_exists($fqfname)) {
        if (filemtime($fqfname) > (time() - $cache_life)) {

            // The cache file is fresh.
            $fresh = file_get_contents($fqfname);
            $results = json_decode($fresh,true);
            return $results;
        }
        else {
            unlink($fqfname);
        }
    }

    return null;
}

/**
 * mecanisme de cache JSON
 * expériences peu probantes avec WP Super Cache pour la gestion du cache
 * d'ou cette fonction reprise d'un code trouvé sur le web
 *
 * @return void
 * @see http://stackoverflow.com/questions/11407514/caching-json-output-in-php
 * @author 
 **/
function putJSONCache($key, $results) 
{
    $json = json_encode($results);
    $fqfname = getJSONCacheFileName($key);
    $f = fopen( $fqfname, 'w' );
    fwrite($f, $json);
    fclose($f);

}


/**
 * retourne le nom du fichier correspondant à la clé de cache
 *
 * @return filename
 * @param $key la clé du fichier à retrouver
 * @author Kidzou
 **/
function getJSONCacheFileName($key)
{
  
  if ($key==null || $key=="")
    return null;

  return JSONCACHE_DIR.EVENTS_CACHE_PREFIX.$key.".json";
}

/**
 * 
 *
 * @return true si le fichier est plus jeune que le timestamp donné
 * @param $timestamp (int)
 * @author Kidzou
 **/
function maybeCleanupJSONCache($key, $timestamp)
{

  if ($key==null || $key=="")
    return null;

  $fqfname  = getJSONCacheFileName($key);
  if (file_exists($fqfname)) 
  {
    if (filemtime($fqfname) < $timestamp) //le cache est ancien, il faut le supprimer
    {
      unlink($fqfname);
    }
  }
  
}


/**
 * nettoie le cache pour $key
 *
 * @return void
 * @param $key la clé du fichier à retrouver
 * @author Kidzou
 **/
function removeJSONCache($key)
{
  
  if ($key!=null && $key!="")
  {
    $fqfname = getJSONCacheFileName($key);
    if (file_exists($fqfname))
      unlink($fqfname);
  }
}

/**
 * nettoie les JSON stockés dans le répertoire de cache
 *
 * @return void
 * @author Kidzou
 **/
function flushJSONCache( )
{
  $files = glob(JSONCACHE_DIR."*.json" );
  foreach ($files as $filename){unlink($filename);}
}

?>