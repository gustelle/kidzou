<?php 

function do_import_events($filename) 
{

	require_once plugin_dir_path( __FILE__ ).'/PHPExcel/IOFactory.php';

	global $wpdb;
	$inserted = 0;
	$modified = 0;
	$deleted  = 0;
	$table_name = $wpdb->prefix . 'reallysimpleevents';

	try {
		$objPHPExcel = PHPExcel_IOFactory::load(plugin_dir_path( __FILE__ )."/".$filename);
	} catch (Exception $e) {
	 	die("Error loading file: ".$e->getMessage()."<br />\n");
	}

	$sheet = $objPHPExcel->getActiveSheet();

	$modification_date = date( 'Y-m-d H:i:s' );

	//lire la colonne action : 'creer'|'modifier'|'supprimer'
	//en cas de pb de parsing sur la colonne id sur 'modifier' ou 'creer' - stop
	//en cas de pb de parsing sur la colonne start_date ou end_date - stop
	//lire le slug et retrouver l'id

	foreach ($sheet->getRowIterator() as $row) {

		$rowIndex = $row->getRowIndex();

		if ($rowIndex==1) continue; //on est sur le header

		$tableCols = array();

		$cellId 	= $sheet->getCell('A' . $rowIndex);
		$cellAction	= $sheet->getCell('B' . $rowIndex);

		$id 	= $cellId->getCalculatedValue(); //id
		$action = $cellAction->getCalculatedValue(); //action 

		// echo 'id avant controle '.$id;
		//echo 'action avant controle '.$action;

		//controles generiques
		if ($action=='creer' || $action=='créer' || $action=='modifier') 
		{
			$extra_venue = 0;

			$startDateValue = $sheet->getCell('C' . $rowIndex)->getValue();

			if (!$startDateValue) 
				continue; //do not import this row
			if ( PHPExcel_Shared_Date::isDateTime( $sheet->getCell('C' . $rowIndex) ) ) {
			   $dateValue 	= PHPExcel_Shared_Date::ExcelToPHP($startDateValue);
			   $start_date 	= date('Y-m-d H:i:s',$dateValue);
			} 

			$endDateValue = $sheet->getCell('D' . $rowIndex)->getValue();
			
			//si la date de fin n'est pas renseignée, on prend par défaut la fin de la journée de la date de début
			if (!$endDateValue || $endDateValue=='') {
				$start_day_arr 	= explode(" ", $start_date);
				$start_day 	= $start_day_arr[0]." 00:00:00";
				$endtime	= new DateTime($start_day);
				$endtime->add(new DateInterval('PT23H59M59S'));//fin à 23:59:59 par défaut
				$end_date	= $endtime->format('Y-m-d H:i:s');

			}
			else if ( PHPExcel_Shared_Date::isDateTime( $sheet->getCell('D' . $rowIndex) ) ) {
			   $dateValue 	= PHPExcel_Shared_Date::ExcelToPHP($endDateValue);
			   $end_date 	= date('Y-m-d H:i:s',$dateValue);
			} 

			$titre 	= $sheet->getCell('E' . $rowIndex)->getCalculatedValue();
			$featCell = $sheet->getCell('F' . $rowIndex)->getCalculatedValue(); 
			$featured 	= $featCell!=null && strtoupper($featCell)=='Y' ? 1:0;

			$lien 		= kz_rse_formatlink( $sheet->getCell('G' . $rowIndex)->getCalculatedValue() );
			$desc 		= $sheet->getCell('H' . $rowIndex)->getCalculatedValue();
			$connections_id 	= 0;

			if ($sheet->getCell('I' . $rowIndex)->getCalculatedValue()!='')
				$connections_id = kz_slug_to_connections( $sheet->getCell('I' . $rowIndex)->getCalculatedValue() );

			$venue = $sheet->getCell('J' . $rowIndex)->getCalculatedValue();
			
			//on assume que si le lieu est renseigné alors que la fiche est également renseignée, 
			//alors extra_venue=1 (on force le lieu)
			if ( $connections_id>0 && $venue!="")
				$extra_venue = 1;

			//pas de fiche associée, ou fiche mais lieu forcé (extra_venue)
			if ( $connections_id==0 || $extra_venue==1 ) 
			{
				$venue 		= $sheet->getCell('J' . $rowIndex)->getCalculatedValue();
				$addresse 	= $sheet->getCell('K' . $rowIndex)->getCalculatedValue();
			}
			
			$image	  	= $sheet->getCell('L' . $rowIndex)->getCalculatedValue();

			$adr = kz_sanitize_text($addresse);

			$tableCols = array(
							    "title" 		=> $titre ,
							    "start_date" 	=> $start_date,
							    "end_date" 		=> $end_date,
							    "extra_info" 	=> $desc,
							    "link" 			=> $lien,
							    "connections_id"=> $connections_id,
							    "image" 		=> $image,
							    "address" 		=> $adr,
							    "venue" 		=> $venue,
							    "featured" 		=> $featured,
							    "extra_venue"	=> $extra_venue,
							    "modification_date" => $modification_date
							  );
			//print_r($tableCols);
		}

		if ($action=='creer' || $action=='créer') {
			$isInserted = $wpdb->insert( $table_name, $tableCols  );
			if($isInserted) $inserted++;
		}
		elseif ($action=='modifier') {
			$isInserted = $wpdb->update( $table_name, $tableCols, array( 'ID' => $id ) );
			if($isInserted) $modified++;

			//pas beau, création de dépendance avec JSON API -- passer par un système de notifications
			//pour notifier JSON API que le cache doit être supprimé
			removeJSONCache( $id );
		}
		elseif ($action=='supprimer') {
			$isDeleted = $wpdb->delete( $table_name, array( 'ID' => $id  ) );
			if($isDeleted) $deleted++;

			//pas beau, création de dépendance avec JSON API -- passer par un système de notifications
			//pour notifier JSON API que le cache doit être supprimé
			removeJSONCache( $id  );
		}

	}

	// delete_transient( 'get_upcoming_events_7days');

	return array(
			"inserted"  => $inserted,
			"modified"	=> $modified,
			"deleted"	=> $deleted
		);
}

/**
 * retrouve le slug d'une fiche a partir de son ID
 *
 * @return le slug du style je-suis-un-slug
 * @author 
 **/
function kz_slug_to_connections ($slug)
{
	global $wpdb;
	$res = $wpdb->get_var("SELECT id FROM wp_connections WHERE slug='$slug'");
	return $res;
}

?>