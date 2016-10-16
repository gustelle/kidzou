<?php

add_action( 'plugins_loaded', array( 'Kidzou_Utils', 'get_instance' ), 100 );


/**
 * Classe utilitaire
 *
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Utils {


	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function log( $log, $force = false) {

		$logme = $force ;

		if (!$logme)
			$logme = ( true === WP_DEBUG && self::current_user_is('admin') );

        if ( $logme ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
 
	}

	public static function printStackTrace() {

	    $e = new Exception();
	    $trace = explode("\n", $e->getTraceAsString());
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse($trace);
	    array_shift($trace); // remove {main}
	    array_pop($trace); // remove call to this method
	    $length = count($trace);
	    $result = array();
	    
	    for ($i = 0; $i < $length; $i++)
	    {
	        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	    }
	    
	    self::log( "\t" . implode("\n\t", $result) );

	}

	public static function get_option( $option_name='', $default='' ) {

		global $kidzou_options;

		if (''==$option_name)
			return $default;

		if (isset( $kidzou_options[$option_name] ) )
			return $kidzou_options[$option_name];

		return $default;

	}

	public static function get_options(  ) {

		global $kidzou_options;

		return $kidzou_options;

	}

	/**
	 * les AJAX sont identifiées dans le domaine de l'admin
	 * il faut les exclure
	 *
	 * @return Bool
	 * @author 
	 **/
	public static function is_really_admin( ) {

		if (defined('DOING_AJAX') && DOING_AJAX)
			return false;

		return is_admin() ;

	}

	/**
	 * true si la requete en cours est une api json
	 *
	 * @return Bool
	 * @deprecated 
	 **/
	public static function is_api()
	{
		return preg_match( '#\/api\/#', self::get_request_path() );
	}


	public static function get_request_path() {

		return $_SERVER['REQUEST_URI'];
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function current_user_is($role = '')
	{

		$is_user = false;
		switch ($role) {
			case 'subscriber':
				$is_user = current_user_can('read');
				break;

			case 'contributor':
				$is_user = current_user_can('edit_posts');
				break;

			case 'author':
				$is_user = current_user_can('edit_published_posts');
				break;

			case 'editor':
				$is_user = current_user_can('manage_categories');
				break;

			case 'admin':
				$is_user = current_user_can('manage_options');
				break;

			case 'administrator':
				$is_user = current_user_can('manage_options');
				break;

			default:
				return new WP_Error( 'unknown_role', __( "Role inconnu", "kidzou" ) );
				break;
		}

		// Kidzou_Utils::log('Kidzou_Utils [current_user_is] '. $role . ' = ' . ($is_user ? 'yes' : 'no') , true );

		return $is_user;
	}

	/**
	 * Cette méthode permet d'attribuer des permissions fonctionnelles fines telles que l'import d'événement, l'édition client, ...
	 *
	 * @param $permission string permission Kidzou
	 **/
	public static function current_user_can($permission = '') 
	{
		if ($permission=='') return false;

		return self::current_user_is(
				self::get_option($permission)
			);
	}

	/**
	 * Allow to remove method for an hook when, it's a class method used and class don't have global for instanciation !
	 *
	 * @since customer-analytics
	 * @author BeAPI - Copyright 2012 Amaury Balmer - amaury@beapi.fr
	 * @see https://github.com/herewithme/wp-filters-extras/blob/master/wp-filters-extras.php
	 **/
	public static function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		
		// Take only filters on right hook name and priority
		if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
			return false;
		
		// Loop on filters registered
		foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method)
			if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
				// Test if object is a class and method is equal to param !
				if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && $filter_array['function'][1] == $method_name ) {
					unset($wp_filter[$hook_name][$priority][$unique_id]);
				}
			}
			
		}
		
		return false;
	}

	/**
	 * Allow to remove method for an hook when, it's a class method used and class don't have variable, but you know the class name :)
	 * 
	 * @since customer-analytics
	 * @author BeAPI - Copyright 2012 Amaury Balmer - amaury@beapi.fr
	 * @see https://github.com/herewithme/wp-filters-extras/blob/master/wp-filters-extras.php
	 **/
	public static function remove_filters_for_anonymous_class( $hook_name = '', $class_name ='', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		// Kidzou_Utils::log('remove_filters_for_anonymous_class : '.$hook_name.'|'.$class_name ,true);
		// Kidzou_Utils::log(array('method'=> __METHOD__,'wp_filter[hook_name]'=> $wp_filter[$hook_name][$priority]), true);
		
		// Take only filters on right hook name and priority
		if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) ) {
			// Kidzou_Utils::log(array('method'=> __METHOD__,'message'=> 'filter not found'), true);
			return false;
		}
			
		// Loop on filters registered
		foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method)
			if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
				// Kidzou_Utils::log(array('method'=> __METHOD__,'class'=> get_class($filter_array['function'][0])), true);
				// Test if object is a class, class and method is equal to param !
				if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && get_class($filter_array['function'][0]) == $class_name && $filter_array['function'][1] == $method_name ) {
					unset($wp_filter[$hook_name][$priority][$unique_id]);
					// Kidzou_Utils::log(array('method'=> __METHOD__,'message'=> 'filter unset'), true);
				}
			}
			
		}
		
		return false;
	}

	/**
	 * supprime la gallerie d'un contenu de post
	 */
	public static function  strip_shortcode_gallery( $content ) {
	    preg_match_all( '/'. get_shortcode_regex() .'/s', $content, $matches, PREG_SET_ORDER );
	    if ( ! empty( $matches ) ) {
	        foreach ( $matches as $shortcode ) {
	            if ( 'gallery' === $shortcode[2] ) {
	                $pos = strpos( $content, $shortcode[0] );
	                if ($pos !== false)
	                    return substr_replace( $content, '', $pos, strlen($shortcode[0]) );
	            }
	        }
	    }
	    return $content;
	}

	/**
	 * Ajoute une image à une gallery
	 * ou créée une gallery et l'ajoute au post si le poste ne contenait pas de gallery
	 */
	public static function  add_to_gallery( $post_id, $attach_id=0 ) {

		Kidzou_Utils::log('add_to_gallery '. $post_id . '/' . $attach_id, true);
	   
	    if ($attach_id==0 || $post_id==0)
	    	return;

	    $post = get_post($post_id);

	    $new_content = $post->post_content;
	    $gallery_found = false;

	    preg_match_all( '/'. get_shortcode_regex() .'/s', $post->post_content , $matches, PREG_SET_ORDER );
	    
	    //il y a des shortcodes dans le contenu
	    if ( ! empty( $matches ) ) {

	        //il y a une gallery dans le contenu du post
	        foreach ( $matches as $shortcode ) {
	            
	            if ( 'gallery' === $shortcode[2] ) {
	            	Kidzou_Utils::log('Gallery trouvée', true);
	            	$gallery_found = true;
	                //recupere la string des ids, et transforme en array
	                $atts = shortcode_parse_atts( $shortcode[3] ); //$shortcode[3] contient ids="xxx"
	                //ajout de l'id de la photo dans les args du shortcode
	                $atts['ids'] = $atts['ids'].','.$attach_id; 
	                $new_gallery = '[gallery ';
	                foreach ($atts as $key => $value) {
	                	$new_gallery = $new_gallery.$key.'="'.$value.'"';
	                }
	                $new_gallery .= ']';
	                $new_content = str_replace($shortcode[0], $new_gallery,$post->post_content);
	                Kidzou_Utils::log('Nouvelle gallery : '.$new_gallery, true);
	            }
	        }
	    }

	    //il n'y aps de gallery dans le contenu
	   	if (!$gallery_found) {

	    	//il n'y a pas de shortcode dans le post, il faut en créer une 
	    	Kidzou_Utils::log('Pas de gallery trouvée', true);
	    	$new_gallery = '[gallery ids="'.$attach_id.'"]';
	    	$new_content = $post->post_content . $new_gallery;
	    	Kidzou_Utils::log('Gallery ajoutée au contenu '. $new_content, true);
	    }

	    // Kidzou_Utils::log('Nouveau contenu : '. $new_content);

	    //ne pas oublier d'enregistrer les modifs..
	    $post->post_content = $new_content;
	    wp_update_post( $post );

	}

	/**
	 * Télécharge un media au format Base64 dans le fichier d'upload Wordpress
	 *
	 * @param data String le binaire au format Base64
	 * @param filename String le nom du media a enregistrer
	 * @param parent_id int ID du WP_Post auquel est attaché le media
	 * @return (int|WP_Error) The post ID on success. The value 0 or WP_Error on failure.
	 *
	 **/
	public static function uploadBase64($filename='', $data='', $parent_id=0) {

		if ($data=='') return new WP_Error( 'no data', __( "Pour enregistrer une image au format Base64, encore faut il des données", "kidzou" ) );

	    if ($filename=='') $filename = 'uploaded_'.uniqid();

	    preg_match('/^data:(.+);base64,/', $data, $type);

	    Kidzou_Utils::log('uploadBase64 : '.$type[1], true);

		$upload_dir       = wp_upload_dir();

		// @new
		$upload_path      = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

		$parts = preg_split('/[,]/', $data);
		$img = $parts[1];

		$decoded          = base64_decode($img) ;

		if (!$decoded) {
			Kidzou_Utils::log('erreur d\'import', true);
			return 0;
		} 

		$hashed_filename  = md5( $filename . microtime() ) . '_' . $filename;

		// @new
		$image_upload     = file_put_contents( $upload_path . $hashed_filename, $decoded );

		//HANDLE UPLOADED FILE
		if( !function_exists( 'wp_handle_sideload' ) ) {
		  require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// @new
		$file             = array();
		$file['error']    = '';
		$file['tmp_name'] = $upload_path . $hashed_filename;
		$file['name']     = $hashed_filename;
		$file['type']     = $type[1];
		$file['size']     = filesize( $upload_path . $hashed_filename );

		// upload file to server
		// @new use $file instead of $image_upload
		$file_return      = wp_handle_sideload( $file, array( 'test_form' => false ) );

		$filename = $file_return['file'];
		$attachment = array(
			'post_mime_type' => $file_return['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_status' => 'inherit',
			'guid' => $wp_upload_dir['url'] . '/' . basename($filename)
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_id ); 

		if (!$attach_id) {
			Kidzou_Utils::log('erreur wp_insert_attachment', true);
			return 0;
		}
		if (wp_attachment_is_image($attach_id)) {
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}
		
		return $attach_id;
	}

	/**
	 * renvoie l'URL de la thumbnail d'un post
	 *
	 * @return URL (String)
	 * @param size (thumbnail|medium|large|full)
	 *
	 **/
	public static function get_post_thumbnail($post_id=0, $size='medium')
	{
	    if ( $post_id == 0 ) return '';

		$thumb = '';

		if ( has_post_thumbnail( $post_id ) ) {

			$et_fullpath = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
			$thumb = $et_fullpath[0];
		}

		return $thumb;
	}

	/**
	 * renvoie l'URL de l'avatar d'un commentaire
	 *
	 * @return URL (String)
	 * @author http://wordpress.stackexchange.com/questions/59442/how-do-i-get-the-avatar-url-instead-of-an-html-img-tag-when-using-get-avatar
	 **/
	public static function get_comment_avatar($comment='') 
	{
		if ($comment=='') return '';

		if (function_exists('get_avatar_url'))  //since WP 4.2
			$url = get_avatar_url($comment->comment_author_email);
		else {
			preg_match("/src=['\"](.*?)['\"]/i", $get_avatar, $matches);
			$url = $matches[1];
		}

		return $url;
	}

	/**
	 * renvoie l'adresse IP de l'utilisateur
	 * pour securiser les vote des users  et mesurer les appels d'API
	 *
	 * @return IP Address (String)
	 * @author http://www.media-camp.fr/blog/developpement/recuperer-adresse-ip-visiteur-php
	 **/
	public static function get_ip()
	{
	    if ( isset ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	    {
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    elseif ( isset ( $_SERVER['HTTP_CLIENT_IP'] ) )
	    {
	        $ip  = $_SERVER['HTTP_CLIENT_IP'];
	    }
	    else
	    {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}

	/**
	 * hash pour identifier un user de facon anonyme (vote ou appel d'API)
	 *
	 * @return a hash string to identify "uniquely" an anonymous user
	 * @author Kidzou
	 **/
	public static function hash_anonymous()
	{
	  $ip = self::get_ip(); 
	  $ua = $_SERVER['HTTP_USER_AGENT'];

	  return md5( $ip . $ua );
	}

	/**
	 * fonction generique de sauvegarde des meta d'un post, gere les cas de Update (meta existantes) / Delete (valeurs nulles) 
	 *
	 * @param int $post_id ID du post en cours d'édition
	 * @param Array $arr un tableau de meta/valeurs
	 * @param string $prefix Prefixe optionnel des meta à enregistrer (ex: kz_)
	 * @return static
	 */
	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

		// Kidzou_Utils::log(array('post_id'=>$post_id,'save_meta'=>$arr, 'prefix'=>$prefix), true);

		// Add values of $events_meta as custom fields
		foreach ($arr as $key => $value) { // Cycle through the $events_meta array!

			$pref_key = $prefix.$key; 
			$prev = get_post_meta($post_id, $pref_key, TRUE);
			// Kidzou_Utils::log('save_meta ' . $pref_key. ' / prev = '. $prev . ' / new = '. $value,true);
			if ($prev!='') { // If the custom field already has a value
				update_post_meta($post_id, $pref_key, $value);
			} else { // If the custom field doesn't have a value
				if ($prev=='') delete_post_meta($post_id, $pref_key);
				add_post_meta($post_id, $pref_key, $value, TRUE);
			}
			if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
		}
		return true;
	}



} //fin de classe

?>