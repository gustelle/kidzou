<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Kidzou
 * @author    Guillaume <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Kidzou
 */
?>

<?php
if (isset($_POST['submit']))
{

 	$flush = trim($_POST['flush_rules']);   

 	if ($flush==1)
 	{

	    flush_rewrite_rules();

 		//supprimer les transients relatifs aux metropoles
 		//qui seront regenerés avec les nouvelles metropoles
 		delete_transient( 'kz_get_national_metropoles' );
	    delete_transient( 'kz_default_metropole' );
	    delete_transient( 'kz_covered_metropoles_all_fields' );
	    delete_transient( 'kz_covered_metropoles' );

 	}
}


?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<!-- @TODO: Provide markup for your options page here. -->

	<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" >

	 	<p>
	 		<input type="checkbox" value="1"  id="flush_rules" name="flush_rules">
	 		<span style="padding-left:5px;"><?php _e('Rafraichir les r&egrave;gles de permaliens.<br/><em>Cela est n&eacute;cessaire lorsque vous changez, ajoutez ou supprimez une m&eacute;tropole</em>','Kidzou'); ?></span>
	 	</p>

		 <input name="submit" id="submit" value="Mettre &agrave; jour" type="submit" class="button-primary">

	</form>

</div>

