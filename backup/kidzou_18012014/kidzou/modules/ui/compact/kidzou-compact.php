<?php



function kz_home_content()
{
	include( plugin_dir_path( __FILE__ ) . '/compact-home-content.php');
}



function kz_entry_content()
{

	if (is_home()) {
		include( plugin_dir_path( __FILE__ ) . '/compact-article.php');
	} else {
		include( plugin_dir_path( __FILE__ ) . '/article.php');
	}

}

?>