<?php

add_filter( 'default_content', 'kz_event_editor_content' );

/**
 * permet de structurer l'edition d'un evenement
 * 
 * @see http://www.smashingmagazine.com/2011/10/14/advanced-layout-templates-in-wordpress-content-editor/
 * @return void
 * @author 
 **/
function kz_event_editor_content( $content ) {

	global $current_screen;
	if ( $current_screen->post_type == 'event' ) {
			
		$content = '
		  	<ul>
				<li><strong>Pour qui ?</strong> </li>
				<li><strong>Quoi ?</strong>  </li>
				<li><strong>Où ? </strong> </li>
				<li><strong>Quand ?</strong>   </li>
				<li><strong>Combien ? </strong> </li>
				<li><strong>Contact : </strong> </li>
				<li><strong>Inscriptions : </strong> </li>
			</ul>
		';
	}
	return $content;
}
?>