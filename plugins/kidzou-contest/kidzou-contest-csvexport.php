<?php

class CSVExport
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		if( isset($_GET['download_report']) && isset($_GET['post_id']) )
		{
			$csv = $this->generate_csv($_GET['post_id']);

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"report.csv\";" );
			header("Content-Transfer-Encoding: binary");

			echo $csv;
			exit;
		}

		// // Add extra menu items for admins
		// add_action('admin_menu', array($this, 'admin_menu'));

		// // Create end-points
		// add_filter('query_vars', array($this, 'query_vars'));
		// add_action('parse_request', array($this, 'parse_request'));
	}

	// /**
	//  * Add extra menu items for admins
	//  */
	// public function admin_menu()
	// {
	// 	add_menu_page('Download Report', 'Download Report', 'manage_options', 'download_report', array($this, 'download_report'));
	// }

	// /**
	//  * Allow for custom query variables
	//  */
	// public function query_vars($query_vars)
	// {
	// 	$query_vars[] = 'download_report';
	// 	return $query_vars;
	// }

	// /**
	//  * Parse the request
	//  */
	// public function parse_request(&$wp)
	// {
	// 	if(array_key_exists('download_report', $wp->query_vars))
	// 	{
	// 		$this->download_report();
	// 		exit;
	// 	}
	// }

	// /**
	//  * Download report
	//  */
	// public function download_report()
	// {
	// 	echo '<div class="wrap">';
	// 	echo '<div id="icon-tools" class="icon32"></div>';
	// 	echo '<h2>Download Report</h2>';
	// 	//$url = site_url();

	// 	echo '<p><a href="'.site_url().'/wp-admin/admin.php?page=download_report&download_report">Export the Subscribers</a>';
	// }

	/**
	 * Converting data to CSV
	 */
	public function generate_csv($post_id=0)
	{
		if ($post_id==0)
			return '';

		$csv_output = '';

		$user_query = new WP_User_Query(
				array(
					'meta_key'	  	=>	'kz_contest_'.$post_id,
					'meta_value'	=>	'',
					'meta_compare'	=> 	'EXISTS'
				)
			);

		// Get the results from the query, returning the first user
		$participants = $user_query->get_results();

		foreach ($participants as $user) {
			
			$user_meta = (array)get_user_meta( $user->ID, 'kz_contest_'.$post_id, TRUE );
			
			if ($csv_output=='') {
				$csv_output .= 'user_login,';
				foreach ($user_meta as $key => $value) {
					$csv_output .= $value['name'].',';
				}
				$csv_output .= "\n";
			}

			$csv_output .= $user->user_login.',';
			foreach ($user_meta as $key => $value) {
				$csv_output .= $value['value'].',';
			}
			$csv_output .= "\n";

		}

		// $csv_output = '';
		// $table = 'users';

		// $result = mysql_query("SHOW COLUMNS FROM ".$table."");

		// $i = 0;
		// if (mysql_num_rows($result) > 0) {
		// 	while ($row = mysql_fetch_assoc($result)) {
		// 		$csv_output = $csv_output . $row['Field'].",";
		// 		$i++;
		// 	}
		// }
		// $csv_output .= "\n";

		// $values = mysql_query("SELECT * FROM ".$table."");
		// while ($rowr = mysql_fetch_row($values)) {
		// 	for ($j=0;$j<$i;$j++) {
		// 		$csv_output .= $rowr[$j].",";
		// 	}
		// 	$csv_output .= "\n";
		// }

		return $csv_output;
	}
}

// Instantiate a singleton of this plugin
$csvExport = new CSVExport();

?>