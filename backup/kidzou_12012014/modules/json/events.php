<?php

class JSON_API_Events_Controller {

	public function detachFromClient() {

		global $json_api;
		global $wpdb;

		$table_events = $wpdb->prefix . "reallysimpleevents";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$customer_id 		= $json_api->query->customer_id;
		$event_id 	= $json_api->query->event_id;

		if ($event_id!==null && $event_id!=="" )
		{
			$table_events_cols = array( "customer" => 0);
			
				
			$wpdb->update( $table_events ,
	                     $table_events_cols ,
						 array( 'ID' => $event_id )
					   );
			
			return array();
				
		} else
			$json_api->error("l'identifiant de l'évènement est incorrect.");
	}

	public function attachToClient() {

		global $json_api;
		global $wpdb;

		$table_events = $wpdb->prefix . "reallysimpleevents";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$events 	= $json_api->query->events;

		if ($id!==null && $id!=="" )
		{
			$table_events_cols = array( "customer" => $id);
			//print_r($events);

			foreach ($events as $event) 
			{
				//$wpdb->show_errors();
				$wpdb->update( $table_events ,
		                     $table_events_cols ,
							 array( 'ID' => $event )
						   );
				//$wpdb->print_error();
			}
			return array();
				
		} else
			$json_api->error("l'identifiant ou le nom du client est incorrect.");
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function saveEvents () {

		global $json_api;
		global $wpdb;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$table_events = $wpdb->prefix . "reallysimpleevents";

		$validated		= $json_api->query->validated;
		$unvalidated 	= $json_api->query->unvalidated;
		$validate_list = "(".implode(",", $validated).")";
		$unvalidate_list = "(".implode(",", $unvalidated).")";

		$wpdb->query(
				"
				UPDATE $table_events
				SET validated = 1
				WHERE ID in $validate_list
				"
			);

		$wpdb->query(
				"
				UPDATE $table_events
				SET validated = 0
				WHERE ID in $unvalidate_list
				"
			);

		return array( );


	}

	public function queryAttachableEvents( )
	{
		global $json_api;
		global $wpdb;

		$term 		= $json_api->query->term;

		$table_events = $wpdb->prefix . "reallysimpleevents";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$sqlres = $wpdb->get_results( 
					"SELECT e.id, e.title, e.start_date, e.end_date, e.validated FROM $table_events AS e WHERE e.title like '%$term%' AND e.customer=0 ORDER BY title ASC, start_date ASC",
					ARRAY_A
				);

		return array(
			"events" => $sqlres
		);
	} 

	//recuperation de toutes les infos relatives à un evenement
	//y compris la fiche attachée à l'évenement 
	//la fonction renvoie également un texte "Du xxx au xxx" ou "Le xxx" pour synthétiser les dates de l'évènement ('dates_teaser')
	
	public function get_event() 
	{

		global $json_api;
		
		//Option 1 : faire une requete sur 1 ID unique
		$id = $json_api->query->event_id;
		//Option 2 : faire un requete sur une ensemble d'ID, séparés par des ","
		$in = $json_api->query->events_in;

		//tentative de cache
		if ($id!=null && $id!="")
			$return = getJSONCache($id);
		else
			$return = false;

		if (!$return) 
		{
			$sqlres = null;
			$return = array();

			//Option 1
			if ($id!='') 
			{
				global $wpdb;
				require_once plugin_dir_path( __FILE__).'../annuaire/kidzou-to-connections.php';

				$table_name = $wpdb->prefix . 'reallysimpleevents';
				$con_table_name = $wpdb->prefix . 'connections';
				$sqlres = $wpdb->get_results( 
					"SELECT e.*,c.id as connections_id,c.ts as connections_timestamp, c.organization, c.addresses, c.options FROM $table_name AS e LEFT JOIN $con_table_name AS c ON e.connections_id=c.id WHERE e.id=$id LIMIT 1",
					ARRAY_A
				);

				//si une fiche est attachée, on va la chercher
				$connections_id = $sqlres[0]['connections_id'];

				$link_tmp = $sqlres[0]['link'];
				
				$sqlres[0]['link'] 				= ($link_tmp!=null && $link_tmp!='') ? hc_rse_parse_link($link_tmp) : '';

				if ($connections_id!=null && $connections_id!="" && $connections_id!=0) 
				{
					$sqlres[0]['post_name'] 		= kz_post_by_connections_id($connections_id)->post_name;
					$sqlres[0]['post_title'] 		= kz_post_by_connections_id($connections_id)->post_title;
				}
				else 
				{
					$sqlres[0]['post_name'] 		= "";
					$sqlres[0]['post_title'] 		= "";
					$sqlres[0]['connections_id'] 		= 0;
				}

				//reprise de l'historique : certains enregistrements avaient une modification_date à 0000-00-00 00:00:00
				//ce qui cause un pb de calcul du timestamp
				//cela ne devrait plus arriver pour les enregistrements crées après l'ajout de ce champ
				if ($sqlres[0]['modification_date']=="0000-00-00 00:00:00")
					$sqlres[0]['modification_date'] = "2013-01-01 00:00:00";
				
				$modification_date = strtotime($sqlres[0]['modification_date']); 

				$sqlres[0]['event_timestamp']  = mktime(date("H", $modification_date), date("i", $modification_date), date("s", $modification_date), date("n", $modification_date), date("j", $modification_date), date("Y", $modification_date));

				if ($sqlres[0]['connections_id']!=0)
				{
					$connections_ts = strtotime($sqlres[0]['connections_timestamp']) ;
					$sqlres[0]['connections_timestamp'] = mktime(date("H", $connections_ts), date("i", $connections_ts), date("s", $connections_ts), date("n", $connections_ts), date("j", $connections_ts), date("Y", $connections_ts));;//$connections_timestamp; // mktime(0,0,0,01,01,2013);

					$sqlres[0]['connections_adresse'] = unserialize_address($sqlres[0]['addresses']);
					$sqlres[0]['connections_options'] = unserialize_options($sqlres[0]['options']);
				}
				else
				{
					$con_ts_date = strtotime("2013-01-01 00:00:00");
					$sqlres[0]['connections_timestamp'] = mktime(date("H", $con_ts_date), date("i", $con_ts_date), date("s", $con_ts_date), date("n", $con_ts_date), date("j", $con_ts_date), date("Y", $con_ts_date));;//$con_ts_date->getTimestamp();
					$sqlres[0]['connections_adresse'] = "";
					$sqlres[0]['connections_options'] = "";
				}

				$start_date = new DateTime($sqlres[0]['start_date']);
				$end_date 	= new DateTime($sqlres[0]['end_date']);

				$dates_teaser = '';
				if ($end_date!=null && $end_date!='' && $end_date!=$start_date)  
					$dates_teaser 	.= 'Du ';
				else 
					$dates_teaser 	.= 'Le ';
				$dates_teaser 		.= $start_date->format('d/m');

				if ($end_date!=null && $end_date!='' && $end_date!=$start_date) 
					$dates_teaser 	.= ' au '.$end_date->format('d/m'); 

				$sqlres[0]['dates_teaser'] = $dates_teaser;

				$return = array(
			      "event" 		=> $sqlres
			    );

				putJSONCache($id, $return);
			}

			//option 2
			else if ($in!="")
			{
				global $wpdb;
				require_once plugin_dir_path( __FILE__).'../annuaire/kidzou-to-connections.php';

				$table_name 	= $wpdb->prefix . 'reallysimpleevents';
				$con_table_name = $wpdb->prefix . 'connections';
				$list = "(".$in.")";
				$list_array = explode(",", $in);
				$limit = count($list_array);

				// $wpdb->show_errors();
				$sqlres = $wpdb->get_results( 
					"SELECT e.*,c.id as connections_id,c.ts as connections_timestamp, c.organization, c.addresses, c.options FROM $table_name AS e LEFT JOIN $con_table_name AS c ON e.connections_id=c.id WHERE e.id IN $list LIMIT $limit",
					ARRAY_A
				);

				$i=0;
				foreach ($sqlres as &$ares) 
				{
					//si une fiche est attachée, on va la chercher
					$connections_id = $sqlres[$i]['connections_id'];

					$link_tmp = $sqlres[$i]['link'];
					
					$sqlres[$i]['link'] 				= ($link_tmp!=null && $link_tmp!='') ? hc_rse_parse_link($link_tmp) : '';

					if ($connections_id!=null && $connections_id!="" && $connections_id!=0) 
					{
						$sqlres[$i]['post_name'] 		= kz_post_by_connections_id($connections_id)->post_name;
						$sqlres[$i]['post_title'] 		= kz_post_by_connections_id($connections_id)->post_title;
					}
					else 
					{
						$sqlres[$i]['post_name'] 		= "";
						$sqlres[$i]['post_title'] 		= "";
						$sqlres[$i]['connections_id'] 		= 0;
					}

					//reprise de l'historique : certains enregistrements avaient une modification_date à 0000-00-00 00:00:00
					//ce qui cause un pb de calcul du timestamp
					//cela ne devrait plus arriver pour les enregistrements crées après l'ajout de ce champ
					// echo 'modif '.$sqlres[$i]['modification_date'];
					if ($sqlres[$i]['modification_date']=="0000-00-00 00:00:00")
						$sqlres[$i]['modification_date'] = "2013-01-01 00:00:00";
					
					$modification_date = strtotime($sqlres[$i]['modification_date']); 
					
					$sqlres[$i]['event_timestamp']  = mktime(date("H", $modification_date), date("i", $modification_date), date("s", $modification_date), date("n", $modification_date), date("j", $modification_date), date("Y", $modification_date));

					if ($sqlres[$i]['connections_id']!=0)
					{
						$connections_ts = strtotime($sqlres[$i]['connections_timestamp']) ;//new DateTime($sqlres[0]['connections_timestamp']);
						$sqlres[$i]['connections_timestamp'] = mktime(date("H", $connections_ts), date("i", $connections_ts), date("s", $connections_ts), date("n", $connections_ts), date("j", $connections_ts), date("Y", $connections_ts));;//$connections_timestamp; // mktime(0,0,0,01,01,2013);

						$sqlres[$i]['connections_adresse'] = unserialize_address($sqlres[$i]['addresses']);
						$sqlres[$i]['connections_options'] = unserialize_options($sqlres[$i]['options']);
					}
					else
					{
						$con_ts_date = strtotime("2013-01-01 00:00:00");//new MyDateTime("2013-01-01 00:00:00");
						$sqlres[$i]['connections_timestamp'] = mktime(date("H", $con_ts_date), date("i", $con_ts_date), date("s", $con_ts_date), date("n", $con_ts_date), date("j", $con_ts_date), date("Y", $con_ts_date));;//$con_ts_date->getTimestamp();

						$sqlres[$i]['connections_adresse'] = "";
						$sqlres[$i]['connections_options'] = "";
					}
					

					$start_date = new DateTime($sqlres[$i]['start_date']);
					$end_date 	= new DateTime($sqlres[$i]['end_date']);

					$dates_teaser = '';
					if ($end_date!=null && $end_date!='' && $end_date!=$start_date)  
						$dates_teaser 	.= 'Du ';
					else 
						$dates_teaser 	.= 'Le ';
					$dates_teaser 		.= $start_date->format('d/m');

					if ($end_date!=null && $end_date!='' && $end_date!=$start_date) 
						$dates_teaser 	.= ' au '.$end_date->format('d/m'); 

					$sqlres[$i]['dates_teaser'] = $dates_teaser;

					$i++;
				}

				$return = array(
			      "events" 		=> $sqlres
			    );
			}
		}

		return cacheable_json($return);
	}


	//possibilité de passer dans le front un nombre de jours pour définir une fourchette de temps
	//par défaut, si aucun param n'est passé, la fourchette de temps est de 7j
	public function upcoming() {

		global $json_api;
		$days = $json_api->query->days;

		if ($days=='' || $days==null)
		 	$days = 7;

		$results = get_upcoming_events_xdays_nogroup($days);

		$return = array(
	      	"events" 	=> $results
	    );

	    return cacheable_json($return);
	}

	//amélioration : externaliser la logique d'exportation dans really simple events
	//n'a pas fonctionné, c'est pour cela que je l'ai mise ici 
	public function export_events() {

		// require_once WP_PLUGIN_DIR.'/really-simple-events/admin/export_events.php';

		// $message = do_export_events();

		//doto : externaliser la logique Excel dans really-simple-events
		//mais passer en param les resultats SQL
		//do_export($sqlres);

		global $wpdb;
		$table_name = $wpdb->prefix . 'reallysimpleevents';

		define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

		require_once WP_PLUGIN_DIR.'/kidzou/plugins_integration/really-simple-events/admin/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// // Set document properties
		$objPHPExcel->getProperties()->setCreator("Kidzou")
									 ->setLastModifiedBy("Kidzou")
									 ->setTitle("Kidzou - Agenda des évènements")
									 ->setSubject("Kidzou - Agenda des évènements")
									 ->setDescription("Kidzou - Agenda des évènements")
									 ->setKeywords("kidzou agenda évènements")
									 ->setCategory("kidzou agenda évènements");

		// 	données de validation pour le lien avec la fiche
		$objPHPExcel->createSheet(NULL, 1);
		$slugCount = 1; //pas de header, on commence à la ligne 1
		$sqlres = kz_connections_export_slugs();
		foreach ( $sqlres as $r ) 
		{
			$objPHPExcel->setActiveSheetIndex(1)
						->setCellValue('A'.$slugCount,  $r['slug']);
			$slugCount++;
		}

		// // Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle('Fiches');

		// remplir le header de la feuille contenant les evenements
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A1', 'id')
					->setCellValue('B1', 'Action')
					->setCellValue('C1', 'Date de début')
					->setCellValue('D1', 'Date de fin')
					->setCellValue('E1', 'Titre')
					->setCellValue('F1', 'Featured')
					->setCellValue('G1', 'Lien')
					->setCellValue('H1', 'Description')
					->setCellValue('I1', 'Fiche')
					->setCellValue('J1', 'Ville/Quartier')
					->setCellValue('K1', 'Adresse')
					->setCellValue('L1', 'Image');

		//selectionner un random pour être certain de ne pas
		//passer par le cache DB Reloaded
		$sqlres = $wpdb->get_results( 
			"SELECT e.*,".rand()." FROM $table_name as e ORDER BY start_date ASC",
			ARRAY_A
		);


		$i = 2;
		foreach ( $sqlres as $r ) 
		{
			$slug = '';
			if ($r['connections_id']!='' && intval($r['connections_id'])>0)
			{
				$slug = kz_connections_to_slug($r['connections_id']);
			}

			$start_date = new DateTime($r['start_date']); 
			$end_date	= new DateTime($r['end_date']); 
			$featVal 	= intval($r['featured'])==1 ? 'Y' : '';
			$link_tab	= hc_rse_parse_link($r['link']);

			$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A'.$i,  $r['id'])
						->setCellValue('E'.$i, 	$r['title'])
						->setCellValue('F'.$i, 	$featVal)
						->setCellValue('G'.$i, 	$link_tab['link'])
						->setCellValue('H'.$i, 	$r['extra_info'])
						->setCellValue('I'.$i, 	$slug) 	
						->setCellValue('J'.$i, 	$r['venue'])	
						->setCellValue('K'.$i, 	$r['address'])
						->setCellValue('L'.$i, 	$r['image']);

			//mettre les dates au format date
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, PHPExcel_Shared_Date::PHPToExcel($start_date));
			$objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY); 
			
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, PHPExcel_Shared_Date::PHPToExcel($end_date));
			$objPHPExcel->getActiveSheet()->getStyle('D'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY); 

			//ajouter un controle de validation des slugs fes fiches
			$objValidation = $objPHPExcel->getActiveSheet()->getCell('I'.$i)->getDataValidation();
		    $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
		    $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
		    $objValidation->setAllowBlank(true); //possible qu'aucune fiche ne soit renseignée
		    $objValidation->setShowInputMessage(true);
		    $objValidation->setShowErrorMessage(true);
		    $objValidation->setShowDropDown(true);
		    $objValidation->setErrorTitle("Voir l\'onglet Fiches pour la liste des fiches");
		    $objValidation->setError("cette fiche n\'est pas référencée.");
		    $objValidation->setPromptTitle('Sélectionnez une fiche de la liste');
		    $objValidation->setPrompt('Sélectionnez une fiche de la liste.');
		    //Using a comma separated list here works, but using a range comes back empty
		    $objValidation->setFormula1('Fiches!$A$1:$A'.$slugCount);

			$i++;
		}

		// // Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle('Agenda');

		// 	// Guide d'utilisation
		$objPHPExcel->createSheet(NULL, 2);
		$objPHPExcel->setActiveSheetIndex(2)
					->setCellValue('B1', 'Quand vous importez un classeur, veillez à ce que le classeur ait été fermé avec la feuille "Agenda" active')
					->setCellValue('B2', 'Les colonnes doivent toujours respecter l\'ordre : id|Action|Date de début|Date de fin|Titre|Featured|Lien|Description|Fiche|Nom du lieu|Adresse|Image')
					->setCellValue('B3', 'Les entetes de colonnes peuvent changer de libellé, seul l\'ordre des colonne est important')
					->setCellValue('B4', 'La première ligne de données lue est la ligne n°2')
					->setCellValue('B6', 'id')
					->setCellValue('C6', 'Laisser vide si vous créez une nouvelle ligne')
					->setCellValue('B7', 'Action')
					->setCellValue('C7', 'Si vide, la ligne ne sera pas considérée à l\'import. Les valeurs considérées à l\'import sont "créer", "creer", "modifier", "supprimer"')
					->setCellValue('B8', 'Date de début')
					->setCellValue('C8', 'Doit être de type date, peu importe le format')
					->setCellValue('B9', 'Date de fin')
					->setCellValue('C9', 'Doit être de type date, peu importe le format')
					->setCellValue('B10', 'Featured')
					->setCellValue('C10', 'Peut contenir les valeurs "Y" ou  "y". Laisser vide sinon')
					->setCellValue('B11', 'Lien')
					->setCellValue('C11', 'Une URL qui commence par http:// ou https://')
					->setCellValue('B11', 'Fiche')
					->setCellValue('C11', 'correspond au-slug-de-la-fiche en lien avec l\'évènement. Si une fiche est liée à un évènement, l\'adresse et le nom du lieu de l\'évènement sont repris de la fiche');

		$objPHPExcel->getActiveSheet()->setTitle('Guide');

		// // Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save(str_replace('.php', '.xlsx', __FILE__));

		return array(
	      "message" 		=> 'OK'
	    );
	}
}

?>