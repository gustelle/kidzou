<?php 

function test_exports()
{
	return 'test';
}

function do_export_events() 
{
	// define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

	// /** Include PHPExcel */
	// require_once WP_PLUGIN_DIR.'/really-simple-events/admin/PHPExcel.php';

	// global $wpdb;
	// $table_name = $wpdb->prefix . 'reallysimpleevents';

	// $sqlres = $wpdb->get_results( 
	// 	"SELECT e.*
	// 		FROM $table_name as e
	// 		ORDER BY start_date ASC",
	// 	ARRAY_A
	// );

	// // Create new PHPExcel object
	// $objPHPExcel = new PHPExcel();

	// // // Set document properties
	// $objPHPExcel->getProperties()->setCreator("Kidzou")
	// 							 ->setLastModifiedBy("Kidzou")
	// 							 ->setTitle("Kidzou - Agenda des évènements")
	// 							 ->setSubject("Kidzou - Agenda des évènements")
	// 							 ->setDescription("Kidzou - Agenda des évènements")
	// 							 ->setKeywords("kidzou agenda évènements")
	// 							 ->setCategory("kidzou agenda évènements");

	// // // Add some data
	// $objPHPExcel->setActiveSheetIndex(0)
	// 			->setCellValue('A1', 'id')
	// 			->setCellValue('B1', 'Action')
	// 			->setCellValue('C1', 'Date de début')
	// 			->setCellValue('D1', 'Date de fin')
	// 			->setCellValue('E1', 'Titre')
	// 			->setCellValue('F1', 'Featured')
	// 			->setCellValue('G1', 'Lien')
	// 			->setCellValue('H1', 'Description')
	// 			->setCellValue('I1', 'Fiche')
	// 			->setCellValue('J1', 'Nom du lieu')
	// 			->setCellValue('K1', 'Adresse');

	// $i = 2;
	// foreach ( $sqlres as $r ) 
	// {
	// 	$slug = '';
	// 	if ($r['connections_id']!='' && intval($r['connections_id'])>0)
	// 	{
	// 		$slug = kz_connections_to_slug($r['connections_id']);
	// 	}

	// 	$start_date = new DateTime($r['start_date']); 
	// 	$end_date	= new DateTime($r['end_date']); 
	// 	$featVal 	= intval($r['featured'])==1 ? 'Y' : '';

	// 	$objPHPExcel->setActiveSheetIndex(0)
	// 				->setCellValue('A'.$i,  $r['id'])
	// 				->setCellValue('E'.$i, 	$r['title'])
	// 				->setCellValue('F'.$i, 	$featVal)
	// 				->setCellValue('G'.$i, 	$r['link'])
	// 				->setCellValue('H'.$i, 	$r['extra_info'])
	// 				->setCellValue('I'.$i, 	$slug) 	
	// 				->setCellValue('J'.$i, 	$r['venue'])	
	// 				->setCellValue('K'.$i, 	$r['address']);

	// 	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, PHPExcel_Shared_Date::PHPToExcel($start_date));
	// 	$objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY); 
		
	// 	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, PHPExcel_Shared_Date::PHPToExcel($end_date));
	// 	$objPHPExcel->getActiveSheet()->getStyle('D'.$i)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY); 

	// 	$i++;
	// }

	// // // Rename worksheet
	// $objPHPExcel->getActiveSheet()->setTitle('Agenda');

	// // 	// Guide d'utilisation
	// $objPHPExcel->setActiveSheetIndex(1)
	// 			->setCellValue('B1', 'Quand vous importez un classeur, veillez à ce que le classeur ait été fermé avec la feuille "Agenda" active')
	// 			->setCellValue('B2', 'Les colonnes doivent toujours respecter l\'ordre : id|Action|Date de début|Date de fin|Titre|Featured|Lien|Description|Fiche|Nom du lieu|Adresse|Image')
	// 			->setCellValue('B3', 'Les entetes de colonnes peuvent changer de libellé, seul l\'ordre des colonne est important')
	// 			->setCellValue('B4', 'La première ligne de données lue est la ligne n°2')
	// 			->setCellValue('B6', 'id')
	// 			->setCellValue('C6', 'Laisser vide si vous créez une nouvelle ligne')
	// 			->setCellValue('B7', 'Action')
	// 			->setCellValue('C7', 'Si vide, la ligne ne sera pas considérée à l\'import. Les valeurs considérées à l\'import sont "créer", "creer", "modifier", "supprimer"')
	// 			->setCellValue('B8', 'Date de début')
	// 			->setCellValue('C8', 'Doit être de type date, peu importe le format')
	// 			->setCellValue('B9', 'Date de fin')
	// 			->setCellValue('C9', 'Doit être de type date, peu importe le format')
	// 			->setCellValue('B10', 'Featured')
	// 			->setCellValue('C10', 'Peut contenir les valeurs "Y" ou  "y". Laisser vide sinon')
	// 			->setCellValue('B11', 'Lien')
	// 			->setCellValue('C11', 'Doit être au format (Google)[http://www.google.fr]')
	// 			->setCellValue('B11', 'Fiche')
	// 			->setCellValue('C11', 'correspond au-slug-de-la-fiche en lien avec l\'évènement. Si une fiche est liée à un évènement, l\'adresse et le nom du lieu de l\'évènement sont repris de la fiche');

	// $objPHPExcel->getActiveSheet()->setTitle('Guide');

	// // // Set active sheet index to the first sheet, so Excel opens this as the first sheet
	// $objPHPExcel->setActiveSheetIndex(0);

	// $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	// $objWriter->save(str_replace('.php', '.xlsx', __FILE__));

	return 'events exported';

}




?>