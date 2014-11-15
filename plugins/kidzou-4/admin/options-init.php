<?php

/**
  ReduxFramework Sample Config File
  For full documentation, please visit: https://docs.reduxframework.com
 * */

if (!class_exists('admin_folder_Redux_Framework_config')) {

    class admin_folder_Redux_Framework_config {

        public $args        = array();
        public $sections    = array();
        public $theme;
        public $ReduxFramework;

        public function __construct() {

            if (!class_exists('ReduxFramework')) {
                return;
            }

            // This is needed. Bah WordPress bugs.  ;)
            if ( true == Redux_Helpers::isTheme( __FILE__ ) ) {
                $this->initSettings();
            } else {
                add_action('plugins_loaded', array($this, 'initSettings'), 10);
            }

        }

        public function initSettings() {

            // Just for demo purposes. Not needed per say.
            $this->theme = wp_get_theme();

            // Set the default arguments
            $this->setArguments();

            // Set a few help tabs so you can see how it's done
            $this->setHelpTabs();

            // Create the sections and fields
            $this->setSections();

            if (!isset($this->args['opt_name'])) { // No errors please
                return;
            }

            // If Redux is running as a plugin, this will remove the demo notice and links
            add_action( 'redux/loaded', array( $this, 'remove_demo' ) );
            
            // Function to test the compiler hook and demo CSS output.
            // Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
            add_filter('redux/options/'.$this->args['opt_name'].'/compiler', array( $this, 'compiler_action' ), 10, 2);
            
            // Change the arguments after they've been declared, but before the panel is created
            //add_filter('redux/options/'.$this->args['opt_name'].'/args', array( $this, 'change_arguments' ) );
            
            // Change the default value of a field after it's been set, but before it's been useds
            // add_filter('redux/options/'.$this->args['opt_name'].'/defaults', array( $this,'change_defaults' ) );
            
            // Dynamically add a section. Can be also used to modify sections/fields
            //add_filter('redux/options/' . $this->args['opt_name'] . '/sections', array($this, 'dynamic_section'));

            $this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
        }

        /**

          This is a test function that will let you see when the compiler hook occurs.
          It only runs if a field	set with compiler=>true is changed.

         * */
        function compiler_action($options, $css) {
            //echo '<h1>The compiler hook has run!';
            //print_r($options); //Option values
            //print_r($css); // Compiler selector CSS values  compiler => array( CSS SELECTORS )

            /*
              // Demo of how to use the dynamic CSS and write your own static CSS file
              $filename = dirname(__FILE__) . '/style' . '.css';
              global $wp_filesystem;
              if( empty( $wp_filesystem ) ) {
                require_once( ABSPATH .'/wp-admin/includes/file.php' );
              WP_Filesystem();
              }

              if( $wp_filesystem ) {
                $wp_filesystem->put_contents(
                    $filename,
                    $css,
                    FS_CHMOD_FILE // predefined mode settings for WP files
                );
              }
             */

              Kidzou_Utils::log('Suppression du transient kz_notifications_content');
              delete_transient('kz_notifications_content_offres');
              delete_transient('kz_notifications_content_page');
              delete_transient('kz_notifications_content_post');
        }

        /**

          Custom function for filtering the sections array. Good for child themes to override or add to the sections.
          Simply include this function in the child themes functions.php file.

          NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
          so you must use get_template_directory_uri() if you want to use any of the built in icons

         * */
        // function dynamic_section($sections) {
        //     //$sections = array();
        //     $sections[] = array(
        //         'title' => __('Section via hook', 'redux-framework-demo'),
        //         'desc' => __('<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'redux-framework-demo'),
        //         'icon' => 'el-icon-paper-clip',
        //         // Leave this as a blank section, no options just some intro text set above.
        //         'fields' => array()
        //     );

        //     return $sections;
        // }

        /**

          Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.

         * */
        function change_arguments($args) {
            //$args['dev_mode'] = true;

            return $args;
        }

        /**

          Filter hook for filtering the default value of any given field. Very useful in development mode.

         * */
        function change_defaults($defaults) {
            // $defaults['str_replace'] = 'Testing filter hook!';

            return $defaults;
        }

        // Remove the demo link and the notice of integrated demo from the redux-framework plugin
        function remove_demo() {

            // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
            if (class_exists('ReduxFrameworkPlugin')) {
                remove_filter('plugin_row_meta', array(ReduxFrameworkPlugin::instance(), 'plugin_metalinks'), null, 2);

                // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
                remove_action('admin_notices', array(ReduxFrameworkPlugin::instance(), 'admin_notices'));
            }
        }

        public function setSections() {



            $this->sections[] = array(
                'title'     => __('R&eacute;glages g&eacute;n&eacute;raux', 'kidzou'),
                'desc'      => __('Page de login, etc..', 'kidzou'),
                'icon'      => 'el-icon-cog',
                'fields'    => array(

                        array(
                            'id'        => 'login_page',
                            'type'      => 'select',
                            'data'      => 'page',
                            'title'     => __('Page de login', 'kidzou'),
                            'subtitle'  => __('Ben c&apos;est l&agrave; qu&apos;on se connecte', 'kidzou'),
                        ),

                        array(
                            'id'        => 'debug_mode',
                            'type'      => 'checkbox',
                            'default'      => '0',
                            'title'     => __('Mode debug', 'kidzou'),
                            'subtitle'  => __('En cas de soucis, activez cette option et consultez la console Javascript', 'kidzou'),
                        ),

                        
                    )
                );

            // ACTUAL DECLARATION OF SECTIONS
            $this->sections[] = array(
                'title'     => __('G&eacute;olocalisation', 'kidzou'),
                'desc'      => __('lorem ipsum', 'kidzou'),
                'icon'      => 'el-icon-compass',
                'fields'    => array(

                        array(
                            'id'        => 'geo_mapquest_key',
                            'type'      => 'text',
                            'title'     => __('Cl&eacute; MapQuest', 'kidzou'),
                            'subtitle'  => __('Cette cl&eacute; permet d&apos;utiliser l&apos;API qui fournit des adresses a partir de coordonn&eacute;es GPS et vice-versa', 'kidzou'),
                        ),

                        array(
                            'id'        => 'geo_default_metropole',
                            'type'      => 'select',
                            'data' => 'terms',
                            'args' => array('taxonomies'=>'ville', 'args'=>array()),
                            'title'     => __('Ville par d&eacute;faut', 'kidzou'),
                            'subtitle'  => __('La ville par d&eacute;faut est utilis&eacute;e si l&apos;utilisateur n&apos;utilise pas la geolocalisation', 'kidzou'),
                        ),

                        array(
                            'id'        => 'geo_default_lat',
                            'type'      => 'text',
                            'title'     => __('Latitude de la ville par d&eacute;faut', 'kidzou'),
                            'subtitle'  => __('La ville par d&eacute;faut est utilis&eacute;e si l&apos;utilisateur n&apos;utilise pas la geolocalisation', 'kidzou'),
                        ),

                        array(
                            'id'        => 'geo_default_lng',
                            'type'      => 'text',
                            'title'     => __('Longitude de la ville par d&eacute;faut', 'kidzou'),
                            'subtitle'  => __('La ville par d&eacute;faut est utilis&eacute;e si l&apos;utilisateur n&apos;utilise pas la geolocalisation', 'kidzou'),
                        ),

                        array(
                            'id'        => 'geo_national_metropole',
                            'type'      => 'select',
                            'data' => 'terms',
                            'args' => array('taxonomies'=>'ville', 'args'=>array()),
                            'title'     => __('Ville &agrave; port&eacute;e nationale', 'kidzou'),
                            'subtitle'  => __('Lorsque des contenus y sont attach&eacute;s, ils sont visibles pour tous les utilisateurs quelque soit leur m&eacute;tropole de rattachement', 'kidzou'),
                        ),
                    )
                );

            $this->sections[] = array(
                'title'     => __('Publicit&eacute;', 'kidzou'),
                'desc'      => __('lorem ipsum', 'kidzou'),
                'icon'      => 'el-icon-bullhorn',
                'fields'    => array(

                    array(
                        'id'        => 'pub_habillage',
                        'type'      => 'ace_editor',
                        'title'     => __('Habillage publicitaire', 'kidzou'),
                        'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                        'mode'      => 'html',
                        'theme'     => 'monokai',
                        'desc'      => 'Un javascript est attendu',
                        'default'   => ''
                    ),

                    array(
                        'id'        => 'pub_archive',
                        'type'      => 'ace_editor',
                        'title'     => __('Publicit&eacute; sur Archive', 'kidzou'),
                        'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                        'mode'      => 'html',
                        'theme'     => 'monokai',
                        'desc'      => 'Un bandeau 768x90 est parfait',
                        'default'   => '<a href="#"><img src=""/></a>'
                    ),

                    array(
                        'id'        => 'pub_portfolio',
                        'type'      => 'ace_editor',
                        'title'     => __('Publicit&eacute; sur Portfolio d&apos;articles', 'kidzou'),
                        'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                        'mode'      => 'html',
                        'theme'     => 'monokai',
                        'desc'      => 'Typiquement une pub 300x250',
                        'default'   => '<a href="#"><img src=""/></a>'
                    ),

                    array(
                        'id'        => 'pub_post',
                        'type'      => 'ace_editor',
                        'title'     => __('Publicit&eacute; sur un Article', 'kidzou'),
                        'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                        'mode'      => 'html',
                        'theme'     => 'monokai',
                        'desc'      => 'Typiquement une pub 300x250',
                        'default'   => '<a href="#"><img src=""/></a>'
                    )

                )
            );
            
            $this->sections[] = array(
                'title'     => __('API', 'kidzou'),
                'desc'      => __('R&eacute;glages des API Kidzou', 'kidzou'),
                'icon'      => 'el-icon-rss',
                'fields'    => array(

                    array(
                        'id'        => 'excerpts_max_days',
                        'type'      => 'spinner',
                        'title'     => __('Jusque combien de jours l\'utilisateur peut-il remonter pour exporter les extraits ?', 'kidzou'),
                        'subtitle'  => __('Cela peut impacter la performance du site', 'kidzou'),
                        'desc'     => __('Attention &agrave; la performance pour les synchro de contenu', 'kidzou'),
                        'default'  => '7',
                        'min'      => '0',
                        'step'     => '1',
                        'max'      => '30'
                    ),

                    array(
                        'id'        => 'api_usage_history',
                        'type'      => 'spinner',
                        'title'     => __('Combien de jours d\'historique pour l\'utilisation des API ?', 'kidzou'),
                        'subtitle'  => __('Cela peut impacter la performance du site', 'kidzou'),
                        'desc'     => __('30 jours me semble un max', 'kidzou'),
                        'default'  => '7',
                        'min'      => '1',
                        'step'     => '1',
                        'max'      => '30'
                    ),

                )
            );

            $this->sections[] = array(
                'title'     => __('Espace Contributeurs (Pro)', 'kidzou'),
                'desc'      => __('Les contributeurs (les "Pro") peuvent ajouter leurs propres contenus sur la plateforme', 'kidzou'),
                'icon'      => 'el-icon-edit',
                'fields'    => array(

                    array(
                        'id'       => 'widget_guidelines_activate',
                        'type'     => 'checkbox',
                        'title'    => __('Activer le Tutorial  sur le dashboard des contributeurs ?', 'kidzou'), 
                        'default'  => '0'// 1 = on | 0 = off
                    ),
                    array(
                        'id'       => 'widget_guidelines_title',
                        'type'     => 'text',
                        'title'    => __('Titre du tutorial', 'kidzou')
                    ),
                    array(
                        'id'       => 'widget_guidelines_body',
                        'type'     => 'editor',
                        'title'    => __('Contenu du tutorial', 'kidzou'),
                        'args'   => array(
                            'teeny'            => true,
                            'textarea_rows'    => 10
                        )
                    ),
                    
                )
            );

            
            $this->sections[] = array(
                'title'     => __('Notifications', 'kidzou'),
                'desc'      => __('Les notifications apparaissent en bas &agrave; droite des pages, elles sugg&egrave;rent des contenus ou des actions (call-to-action). <br/>L&apos;ensemble des messages &agrave; afficher sont dans une queue d&eacute;pil&eacute;e au fur et &agrave; mesure. <br/>Lorsqu&apos;un message est affich&eacute; un cookie est stock&eacute; sur le poste de l&apos;utilisateur pendant 30 jours de sorte qu&apos;il ne reverra plus cette notification pendant ce laps de temps. Le message suivant peut &ecirc;tre lu.<br/>Un utilisateur ne recoit que 1 seul message par page', 'kidzou'),
                'icon'      => 'el-icon-envelope',
                'fields'    => array(

                    array(
                        'id'       => 'notifications_activate',
                        'type'     => 'checkbox',
                        'title'    => __('Activer les notifications ?', 'kidzou'), 
                        'default'  => '0',// 1 = on | 0 = off
                        'compiler'  => true
                    ),

                    array(
                        'id' => 'notifications_delay',
                        'type' => 'slider',
                        'title' => __('D&eacute;lais (en secondes) avant affichage d&apos;une notification', 'kidzou'),
                        'subtitle' => __('Le d&eacute;lais court &agrave; partir du moment o&ugrave; la page est charg&eacute;e', 'kidzou'),
                        'desc' => __('Min: 0 secondes , max: 30 secondes, par d&eacute;faut : 2 secondes', 'kidzou'),
                        "default" => 2,
                        "min" => 0,
                        "step" => 0.5,
                        "max" => 30,
                        'display_value' => 'text',
                    ),

                    array(
                        'id' => 'notifications_duration',
                        'type' => 'slider',
                        'title' => __('Dur&eacute;e d&apos;affichage d&apos;une notification', 'kidzou'),
                        'subtitle' => __('Combien de temps une notification doit-elle rester affich&eacute;e &agrave; l&apos;&eacute;cran', 'kidzou'),
                        'desc' => __('Min: 3 seconde , max: 30 secondes, par d&eacute;faut : 5 secondes', 'kidzou'),
                        "default" => 5,
                        "min" => 3,
                        "step" => 0.5,
                        "max" => 30,
                        'display_value' => 'text'
                    ),

                    array(
                        'id'       => 'notifications_post_type',
                        'type'     => 'checkbox',
                        'title'    => __('Activer les notifications pour les types de contenu :', 'kidzou'), 
                     
                        //Must provide key => value pairs for multi checkbox options
                        'options'  => array(
                            'post' => 'Post',
                            'offres' => 'Offres',
                            'page' => 'page'
                        ),
                     
                        //See how default has changed? you also don't need to specify opts that are 0.
                        'default' => array(
                            'post' => '1', 
                            'offres' => '0', 
                            'page' => '0'
                        ),
                        'compiler'  => true
                    
                    ),

                     array(
                        'id'       => 'notifications_context',
                        'type'     => 'radio',
                        'title'    => __('Fr&eacute;quence de notification', 'kidzou'), 
                        'subtitle' => __('todo', 'kidzou'),
                        'desc'     => __('todo.', 'kidzou'),
                        //Must provide key => value pairs for radio options
                        'options'  => array(
                            'daily' => '1 fois par jour', 
                            'page' => 'Sur chaque page consult&eacute;e', 
                            'monthly' => '1 fois par mois',
                            'weekly' => '1 fois par semaine',
                        ),
                        'default' => 'page',
                        'compiler'  => true
                    ),

                     array(
                        'id'       => 'notifications_include_categories',
                        'type'     => 'select',
                        'multi'    => true,
                        'title'    => __('Inclure les cat&eacute;gories suivantes dans les notifications', 'kidzou'), 
                        'subtitle' => __('En plus des recos et des featured. Tous les posts publi&eacute;s dans ces cat&eacute;gories seront dans le &apos;queue&apos; des messages &agrave; afficher', 'kidzou'),
                        'desc'     => __('Le nom de la cat&eacute;gorie', 'kidzou'),
                        //Must provide key => value pairs for radio options
                        'data'      => 'categories',
                        'compiler'  => true
                    ),
                )
            );
            
        }

        public function setHelpTabs() {

            // Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
            $this->args['help_tabs'][] = array(
                'id'        => 'redux-help-tab-1',
                'title'     => __('De l\'aide ?', 'redux-framework-demo'),
                'content'   => __('<p>Ben...contactez nous : guillaume@kidzou.fr ou corinne@kidzou.fr</p>', 'redux-framework-demo')
            );

            // Set the help sidebar
            $this->args['help_sidebar'] = __('<p>En cas de blocage contactez guillaume@kidzou.fr.</p>', 'redux-framework-demo');
        }

        /**

          All the possible arguments for Redux.
          For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments

         * */
        public function setArguments() {

            $theme = wp_get_theme(); // For use with some settings. Not necessary.

            $this->args = array(
                'opt_name' => 'kidzou_options',
                'display_name' => 'Kidzou',
                'page_slug' => '_options',
                'page_title' => 'R&eacute;glages',
                'update_notice' => true,
                'intro_text' => '<p>Nous avons rangé ici tous les petits réglages de Kidzou.</p>’',
                'footer_text' => '<p>Kidzou, sorties en famille</p>',
                'admin_bar' => false,
                'menu_type' => 'menu',
                'menu_title' => 'Kidzou',
                // 'menu_icon' => 'http://www.kidzou.fr/wp-content/uploads/2014/10/Favicon_Kidzou_2014.ico',
                'allow_sub_menu' => true,
                // 'page_parent_post_type' => 'your_post_type',
                'customizer' => true,
                'default_mark' => '*',
                'hints' => 
                        array(
                          'icon' => 'el-icon-question-sign',
                          'icon_position' => 'right',
                          'icon_size' => 'normal',
                          'tip_style' => 
                          array(
                            'color' => 'light',
                          ),
                          'tip_position' => 
                          array(
                            'my' => 'top left',
                            'at' => 'bottom right',
                          ),
                          'tip_effect' => 
                          array(
                            'show' => 
                            array(
                              'duration' => '500',
                              'event' => 'mouseover',
                            ),
                            'hide' => 
                            array(
                              'duration' => '500',
                              'event' => 'mouseleave unfocus',
                            ),
                          ),
                        ),
                'output' => true,
                'output_tag' => true,
                'compiler' => true,
                'page_icon' => 'icon-themes',
                'page_permissions' => 'manage_options',
                'save_defaults' => true,
                'show_import_export' => true,
                'transient_time' => 3600,
                'network_sites' => true,
                // 'dev_mode' => true
                // 'database' => 'network'
              );

            // SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
            $this->args['share_icons'][] = array(
                'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
                'title' => 'Visit us on GitHub',
                'icon'  => 'el-icon-github'
                //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
                'title' => 'Like us on Facebook',
                'icon'  => 'el-icon-facebook'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://twitter.com/reduxframework',
                'title' => 'Follow us on Twitter',
                'icon'  => 'el-icon-twitter'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://www.linkedin.com/company/redux-framework',
                'title' => 'Find us on LinkedIn',
                'icon'  => 'el-icon-linkedin'
            );

        }

    }
    
    global $reduxConfig;
    $reduxConfig = new admin_folder_Redux_Framework_config();
}

/**
  Custom function for the callback referenced above
 */
if (!function_exists('admin_folder_my_custom_field')):
    function admin_folder_my_custom_field($field, $value) {
        print_r($field);
        echo '<br/>';
        print_r($value);
    }
endif;

/**
  Custom function for the callback validation referenced above
 * */
if (!function_exists('admin_folder_validate_callback_function')):
    function admin_folder_validate_callback_function($field, $value, $existing_value) {
        $error = false;
        $value = 'just testing';

        /*
          do your validation

          if(something) {
            $value = $value;
          } elseif(something else) {
            $error = true;
            $value = $existing_value;
            $field['msg'] = 'your custom error message';
          }
         */

        $return['value'] = $value;
        if ($error == true) {
            $return['error'] = $field;
        }
        return $return;
    }
endif;
