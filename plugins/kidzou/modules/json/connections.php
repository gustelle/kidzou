<?php

/**
* API JSON de Connections (www.connections-pro.com) developpee par Kidzou
*/
class JSON_API_Connections_Controller {

	public function get_fiche() {


		global $json_api;
		$id = $json_api->query->fiche_id;

		$fiche = array();

		if ($id!='')
		{
			// if ( false === ( $fiche = get_transient( 'kz_get_fiche_'.$id ) ) ) {

				global $wpdb;

				$table_name = $wpdb->prefix . 'connections';
				$sqlres = $wpdb->get_results( 
					"SELECT f.* FROM $table_name f where f.id=$id", ARRAY_A
				);

				$fiche['id'] = $sqlres[0]['id'];
				$fiche['slug'] = $sqlres[0]['slug'];
				$fiche['organization'] = $sqlres[0]['organization'];

				require_once plugin_dir_path( __FILE__).'../annuaire/kidzou-to-connections.php';
				$fiche['adresse'] = unserialize_address($sqlres[0]['addresses']);
				$fiche['options'] = unserialize_options($sqlres[0]['options']);

				$modification_date = new DateTime($sqlres[0]['ts']);
				$timestamp = $modification_date->getTimestamp();
				$fiche['timestamp'] 		= $timestamp;

				// set_transient( 'kz_get_fiche_'.$id , $fiche, 60 * 60 * 4 ); //expiration toutes les 4 heures
			// }
		}

		$return = array(
	      "fiche" 		=> $fiche
	    );

	    return $return;

	}

	/**
	* retrouver une fiche "connections" a partir du slug-de-la-fiche
	* on ne remonte que le strict minimum (slug et ID) afin de ne pas embarquer du HTML dans le JSON
	* ce qui fait planter les JS de traitement qui appellent ce service
	*/
	public function get_fiche_by_slug() {

		global $json_api;
		$term = $json_api->query->term;

		if ($term!='')
		{
			global $wpdb;
			$res = $wpdb->get_results( 
					"SELECT slug,id FROM wp_connections key1 WHERE key1.slug like '%$term%' LIMIT 0,3",
					ARRAY_A
				);

			$i = 0;
			require_once plugin_dir_path( __FILE__).'../annuaire/kidzou-to-connections.php';
			foreach ($res as &$ares) {
				$res[$i]['addresses']	= unserialize_address($ares['addresses']);
				$i++;
			}
		}

		return array(
	      "fiches" 		=> $res
	    );

	}

}



?>