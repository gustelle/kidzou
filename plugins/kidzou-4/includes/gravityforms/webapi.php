<?php

/** 
 * Intégration avec Gravity Forms WebAPI pour déclencher des action automatiquement 
 * lorsqu'on recoit des formulaires par API REST
 * 
 * Les actions déclenchées sont :
 * - Transformation d'une image Base64 en fichier Image
 * - Fournit un preview pour les images transférées via WebAPI en REST dans la liste des formulaires recus (entries) et dans la vue détaillée d'une entry
 * - Ajout d'un statut de validation
 *
 */
class Kidzou_GF_Webapi  {

    /**
     *
     *
     * @since     0.1
     */
    public function __construct() { 

        if (class_exists('GFForms')) {

            //recup de la photo au format base64 et écriture en fichier
            add_action( 'gform_pre_submission', array($this,'kz_write_image_file' ));

            //affichage de la liste des photos soumises : thumbnail
            add_action( 'gform_entries_first_column', array($this,'first_column_content'), 10, 5);

            //affichage custom d'une entry (thumbnail clickable, permalink)
            add_filter( 'gform_entry_field_value', array($this,'kz_entry_fields'), 10, 4 );
            
            //uniquement les admins sont listés dans les settings
            add_filter( 'gform_webapi_get_users_settings_page', array($this,'kz_webapi_get_users_args'));

            //recuperation du statut de la photo pour notification user
            add_filter('gform_custom_merge_tags', array($this,'kz_status_merge_tag'), 10, 4);
            add_filter('gform_replace_merge_tags', array($this,'kz_replace_merge_tags'), 10, 7);
        }

    }

    /**
     * Permet de filtrer la liste utilisateurs candidats à l'impersonation pour l'utilisation des API Gravity forms
     * @link http://www.gravityhelp.com/documentation/gravity-forms/extending-gravity-forms/api/web-api/#sample-web-api-client
     *
     */
    function kz_webapi_get_users_args($args){
        $args["role"] = "administrator";
        return $args;
    }

    /**
     * Recupere une image au format Base64 du formulaire n° x (indiqué en config)
     * et la transforme en fichier déposé dans le répertoire d'upload
     *
     * @todo externaliser les ID des input, les rendre configurable
     *
     */
    // $config_form_id        = Kidzou_Utils::get_option('gf_form_id', '1');
    // Kidzou_Utils::log('action_hook'.$action_hook, true);
    function kz_write_image_file( $form ) {

        $config_form_id        = Kidzou_Utils::get_option('gf_form_id', '1');
        $config_image_field    = Kidzou_Utils::get_option('gf_field_photo', '1');
        $config_login_field    = Kidzou_Utils::get_option('gf_field_user_id', '1');
        $config_email_field    = Kidzou_Utils::get_option('gf_field_user_email', 'contact@kidzou.fr');
        $config_post_field     = Kidzou_Utils::get_option('gf_field_post_id', '1');
        $config_comment_field  = Kidzou_Utils::get_option('gf_field_comment', '1');

        if ( $form['id']== intval($config_form_id) ) {

            $upload_dir       = wp_upload_dir();
            $upload_path      = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
            $base64_string    = $_POST['input_'.$config_image_field];
            $base64_string    = preg_replace( '/data:image\/.*;base64,/', '', $base64_string ); 
            $decoded_string   = base64_decode($base64_string);

            $post = get_post($_POST['input_'.$config_post_field]);

            //le user
            $user = get_user_by('login', $_POST['input_'.$config_login_field]);

            //le fichier prend le nom du post + '_' + user_id + '_' + uniqid()
            $filename = $post->post_name . '_' . $user->ID . '_' . uniqid(). '.jpeg'; //todo extension à rendre générique

            $media = file_put_contents($upload_path.$filename, $decoded_string);

            if( !function_exists( 'wp_handle_sideload' ) ) {
              require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
     
            // Without that I'm getting a debug error!?
            if( !function_exists( 'wp_get_current_user' ) ) {
              require_once( ABSPATH . 'wp-includes/pluggable.php' );
            }

            $file             = array();
            $file['error']    = '';
            $file['tmp_name'] = $upload_path.$filename;
            $file['name']     = $filename;
            $file['size']     = filesize( $upload_path . $filename );

            $file_return = wp_handle_sideload($file, array( 'test_form' => false ));

            //desormais le champ input de l'image contient le lien de l'image et plus les data
            $_POST['input_'.$config_image_field] = $file_return['url'];

            //populer le champs email qui pourra être utilisé pour notifier le user 
            $_POST['input_'.$config_email_field] = $user->user_email;
        }

        
    }

    /**
     * Dans la liste des entries
     * Fournit un preview pour les images transférées via WebAPI en REST
     * 
     * @todo externaliser dans une config la classe qui permet de repérer que le champ est une image
     */
    function first_column_content($form_id, $field_id, $value, $lead, $query_string) {

        $config_form_id        = Kidzou_Utils::get_option('gf_form_id', '1');
        if ( $form_id == intval($config_form_id) ) {
            $preview = sprintf(
                    "<img style='width:50px;height:auto;max-height:50px;overflow:hidden;' src='%s'>",
                    $value
                );
            echo  $preview;
        }
    }


    /**
     * dans la vue détaillée d'une entry
     * Fournit un preview et un lien pour les images transférées via WebAPI en REST
     * 
     * @todo externaliser dans une config la classe qui permet de repérer que le champ est une image
     */
    function kz_entry_fields($value, $field, $lead, $form) { //$content, $field, $value, $lead_id, $form_id

        $config_form_id        = Kidzou_Utils::get_option('gf_form_id', '1');
        $config_post_field     = Kidzou_Utils::get_option('gf_field_post_id', '1');
        $config_image_field    = Kidzou_Utils::get_option('gf_field_photo', '1');

        if ( $form['id']== intval($config_form_id) ) {

            if ( $field['id'] == intval($config_image_field)  ) {

                $value = sprintf(
                        "<a href='%s' target='_blank'><img style='width:100px;height:auto;max-height:100px;overflow:hidden;' src='%s'></a>",
                        $value,
                        $value
                    );

            }
            if ( $field['id'] == intval($config_post_field) ) {

                $link = get_permalink($value);

                $value = sprintf(
                        "<a href='%s' target='_blank'>%s</a>",
                        $link,
                        $link
                    );

            }
        }

        return $value;
    }


    /**
    * add custom merge tags
    * @param array $merge_tags
    * @param int $form_id
    * @param array $fields
    * @param int $element_id
    * @return array
    */
    function kz_status_merge_tag($merge_tags, $form_id, $fields, $element_id) {
        $merge_tags[] = array('label' => 'Statut de validation', 'tag' => '{status}');
        return $merge_tags;
    }

    /**
    * replace custom merge tags in notifications
    * @param string $text
    * @param array $form
    * @param array $lead
    * @param bool $url_encode
    * @param bool $esc_html
    * @param bool $nl2br
    * @param string $format
    * @return string
    */
    function kz_replace_merge_tags($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format) {
        $status = '';
        switch ($lead['approval_status']) {
            case 'rejected':
                $status = __( 'Rejet&eacute;e', 'kidzou' );
                break;
            case 'approved':
                $status = __( 'Accept&eacute;e', 'kidzou' );
                break;
            default:
                # code...
                break;
        }
        $text = str_replace('{status}', $status, $text);
        Kidzou_Utils::log($text);
        return $text;
    }

    
}

//init
new Kidzou_GF_Webapi();



?>