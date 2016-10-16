<?php

/**
 * ReduxFramework Sample Config File
*  For full documentation, please visit: https://docs.reduxframework.com
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
            // add_action( 'redux/loaded', array( $this, 'remove_demo' ) );
            
            // Function to test the compiler hook and demo CSS output.
            // Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
            add_filter('redux/options/'.$this->args['opt_name'].'/compiler', array( $this, 'compiler_action' ), 10, 2);
            
            // Change the arguments after they've been declared, but before the panel is created
            //add_filter('redux/options/'.$this->args['opt_name'].'/args', array( $this, 'change_arguments' ) );
            
            // Change the default value of a field after it's been set, but before it's been useds
            add_filter('redux/options/'.$this->args['opt_name'].'/defaults', array( $this,'change_defaults' ) );
            
            // Dynamically add a section. Can be also used to modify sections/fields
            // add_filter('redux/options/' . $this->args['opt_name'] . '/sections', array($this, 'dynamic_section'));

            $this->ReduxFramework = new ReduxFramework($this->sections, $this->args);

            //déclencher les actions Kidzou au changement des settings
            add_filter('redux/options/'.$this->args['opt_name'].'/validate', array( $this, 'validate_action' ), 10, 1);
            add_filter('redux/options/'.$this->args['opt_name'].'/saved', array( $this, 'save_action' ), 10, 1);

        }

        function compiler_action($options, $css) {

             

        }

        function validate_action($options) {

            Kidzou_Utils::log('Enregistrement des options / validate_action');

            //suppression des transients de notification
            Kidzou_Notif::cleanup_transients();

            //rebuild des regles de rewrite
            // Kidzou_Taxonomies::rebuild_geo_rules();

            //si lu user le demande, on synchronise le Geo Data Store avec les meta kidzou
            if (isset($options['geo_sync_geods']) && intval($options['geo_sync_geods'])==1) {

                Kidzou_Utils::log("Déclenchement de la synchro avec Geo Data Store ");

                Kidzou_GeoDS::sync_geo_data();

            }

        }

        function save_action($options) {

        }

        
        function change_arguments($args) {
            //$args['dev_mode'] = true;

            return $args;
        }

        
        function change_defaults($defaults) {
  
            //on force l'option de synchro de Geo Datastore à 0
            //afin que le user le recoche explicitement 
            //cela se fait après que la hook validate_action soit passé
            //de sorte que si le user l'a coché, la syncrho est tout de même déclenchée
            //repositionner à 0 pour que le user le redemande explicitement lors d'une prochaine visite
            $this->ReduxFramework->options['geo_sync_geods'] = '0';

            return $defaults;
        }


        public function setSections() {

            //recuperer les options precedentes pour populer les champs
            //@see https://github.com/ReduxFramework/redux-framework/issues/1042
            $previous_options = get_option('kidzou_options');

            //mailchimp_lists
            $mailchimp_lists = get_transient( 'kz_mailchimp_lists' );

            if (false==$mailchimp_lists) {
                
                $key = $previous_options['mailchimp_key'];
                $lists = get_mailchimp_lists($key);
                set_transient( 'kz_mailchimp_lists', $lists, 0 ); //never expires ! 
            }

            //pour selectionner un user dans les options d'import de contenu
            $_users = get_users(array('role'=>'contributeur_pro', 'fields' => array( 'ID','user_login','display_name' )));
            $users_list = array();
            foreach ($_users as $_u) {
                $users_list[$_u->ID]= $_u->user_login.' ('.$_u->display_name.')';
            }

            //intégration Gravity Forms
            $gf = false;
            if (class_exists('GFForms')) {

                $gf = true;

                $forms = GFAPI::get_forms();
                $form_options = array();
                
                //choix du formulaire 
                foreach ($forms as $form) {
                    $form_options[$form['id']] = $form['title']; 
                }
                
                //une fois le formulaire selectionné, on va chercher les champs
                $fields_options = array();
                if ( isset($previous_options['gf_form_id']) ) {
                    $selected_form = $previous_options['gf_form_id'];
                    if (intval($selected_form)>0) {
                        $fields = GFAPI::get_form( $selected_form );
                        $fields = $fields['fields'];
                        foreach ($fields as $field) {
                            // eécho $field->id;
                            $fields_options[$field->id] = $field->label; 
                        }
                    }
                }
                
            }



            $this->sections[] = array(
                'title'     => __('R&eacute;glages g&eacute;n&eacute;raux', 'kidzou'),
                'desc'      => __('Ces r&eacute;glages ont besoin d&apos;&ecirc;tre export&eacute;s dans la config pour reutilisation (mobile..)', 'kidzou'),
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
                            'id'        => 'login_redirect',
                            'type'      => 'checkbox',
                            'default'      => '0',
                            'title'     => __('Rediriger l&apos;utilisateur apr&egrave;s login', 'kidzou'),
                            'subtitle'  => __('Sinon, le user atteri dans l&apos;interface d&apos;admin', 'kidzou'),
                        ),

                        array(
                            'id'        => 'login_redirect_page',
                            'type'      => 'select',
                            'data'      => 'page',
                            'title'     => __('Page de redirection apr&egrave;s login', 'kidzou'),
                            'subtitle'  => __('Ne fonctionne que si l&apos;option ci-dessus est coch&eacute;e', 'kidzou'),
                        ),

                        array(
                            'id'        => 'legal_page',
                            'type'      => 'select',
                            'data'      => 'page',
                            'title'     => __('Page "Qui sommes nous ?"', 'kidzou'),
                            'subtitle'  => __('Informations l&eacute;gales sur Kidzou', 'kidzou'),
                        ),

                        array(
                            'id'        => 'user_favs_page',
                            'type'      => 'select',
                            'data'      => 'page',
                            'title'     => __('Page de Favoris utilisateur', 'kidzou'),
                            'subtitle'  => __('Les users retrouvent dans cette page les lieux et &eacute;v&eacute;nements qu&apos;ils ont aim&eacute;', 'kidzou'),
                        ),

                        array(
                            'id'        => 'main_cats',
                            'type'      => 'select',
                            'multi'     => true,
                            'data'      => 'categories',
                            'title'     => __('Categories principales', 'kidzou'),
                            'subtitle'  => __('Quel grand rangement d&apos;information proposer aux users', 'kidzou'),
                        ),

                        array(
                            'id'        => 'debug_mode',
                            'type'      => 'checkbox',
                            'default'      => '0',
                            'title'     => __('Mode debug', 'kidzou'),
                            'subtitle'  => __('En cas de soucis, activez cette option et consultez la console Javascript', 'kidzou'),
                        ),

                        array(
                            'id'        => 'analytics_activate',
                            'type'      => 'checkbox',
                            'default'      => '0',
                            'title'     => __('Activer Google Analytics', 'kidzou'),
                        ),

                        array(
                            'id'        => 'analytics_ua',
                            'type'      => 'text',
                            'title'     => __('UA de Google Analytics', 'kidzou'),
                        ),

                        array(
                            'id'        => 'fb_app_id',
                            'type'      => 'text',
                            'title'     => __('Facebook App ID ', 'kidzou'),
                            'subtitle'  => __('Utilis&eacute; pour la connexion Facebook et pour l&apos;import d&apos;&eacute;v&eacute;nements facebook', 'kidzou'),
                        ),

                        array(
                            'id'        => 'fb_app_secret',
                            'type'      => 'text',
                            'title'     => __('Facebook App Secret ', 'kidzou'),
                            'subtitle'  => __('Utilis&eacute; pour l&apos;import d&apos;&eacute;v&eacute;nements facebook', 'kidzou'),
                        ),
                        
                    )
                );

                $this->sections[] = array(
                    'title'     => __('Permissions', 'kidzou'),
                    'desc'      => __('il y a les <strong>Admin</strong>, les <strong>Auteurs</strong>, les <strong>Pro</strong>, les <strong>contributeurs</strong>, ... certains ont droit de faire des choses, d&apos;autres non !', 'kidzou'),
                    'icon'      => 'el el-lock',
                    'fields'    => array(
                    )
                );

                    /**
                     * Sous section des permissions pour les imports
                     */
                    $this->sections[] = array(
                        'icon'       => 'el el-calendar',
                        'title'      => __( 'Evénements', 'kidzou' ),
                        'subsection' => true,
                        'fields'     => array(
                            
                            array(
                                'id'       => 'can_set_event_recurrence',
                                'type'     => 'select',
                                'title'    => __('Affecter une recurrence à un événement', 'kidzou'), 
                                'data'      => 'roles'
                            ),

                        )
                    );

                    /**
                     * Sous section des permissions pour les featured
                     */
                    $this->sections[] = array(
                        'icon'       => 'el el-star',
                        'title'      => __( 'Featured', 'kidzou' ),
                        'subsection' => true,
                        'fields'     => array(
                            
                            array(
                                'id'       => 'can_edit_featured',
                                'type'     => 'select',
                                'title'    => __('Positionner un article \'Featured\'', 'kidzou'), 
                                'subtitle'  => __('Qui a le droit de mettre en avant un article ?', 'kidzou'),
                                'data'      => 'roles'
                            ),

                        )
                    );


                    /**
                     * Sous section des permissions sur les clients
                     */
                    $this->sections[] = array(
                        'icon'       => 'el el-torso',
                        'title'      => __( 'Clients', 'kidzou' ),
                        'subsection' => true,
                        'fields'     => array(

                            array(
                                'id'       => 'can_edit_customer',
                                'type'     => 'select',
                                'title'    => __('Editer un client', 'kidzou'), 
                                // 'subtitle'  => __('Typiquement lorsqu&apos;on choisit un lieu sur un article affect&eacute; &agrave; un client, qui a le droit d&apos;utiliser ce lieu pour l&apos;injecter dans la fiche client ? de telle sorte que les prochains articles affect&eacute;s &agrave; ce client seront automatiquement pr&eacute;-rempli avec la m&ecirc;me adresse ?', 'kidzou'),
                                'data'      => 'roles'
                            ),
                            
                        )
                    );

                    /**
                     * Sous section des permissions pour les contributeurs
                     */
                    $this->sections[] = array(
                        'icon'       => 'el el-edit',
                        'title'      => __( 'Contenus', 'kidzou' ),
                        'subsection' => true,
                        'fields'     => array(
                            
                            array(
                                'id'       => 'can_edit_post',
                                'type'     => 'select',
                                'title'    => __('Créer des contenus', 'kidzou'), 
                                'data'      => 'roles'
                            ),

                        )
                    );

                    
                // ACTUAL DECLARATION OF SECTIONS
                $this->sections[] = array(
                    'title'     => __('G&eacute;olocalisation', 'kidzou'),
                    'desc'      => __('les contenus de la plateforme sont <strong>filtr&eacute;s automatiquement en fonction de la m&eacute;tropole de rattachement du user</strong>. Celle-ci est par d&eacute;faut calcul&eacute;e automatiquement (si le user accepte de se faire g&eacute;olocaliser). Si il n&apos;accepte pas de se faire g&eacute;olocaliser, Les contenus ne sont pas filtr&eacute;s. <br/>A tout moment, le user peut choisir sa m&eacute;tropole dans le header pour changer sa m&eacute;tropole', 'kidzou'),
                    'icon'      => 'fa fa-map-marker',
                    'fields'    => array(


                            array(
                                'id'       => 'geo_activate',
                                'type'     => 'checkbox',
                                'title'    => __('Activer la geolocalisation des contenus ?', 'kidzou'), 
                                'subtitle'  => __('Si cette est active, les contenus seront filtr&eacute;s pour ne s&apos;afficher que si la m&eacute;tropole de rattachement du contenu est celle qui transite dans la requ&ecirc.te.', 'kidzou'),
                                'desc'      => __('La requ&ecirc;te peut soit contenir la m&eacute;tropole <em>(../lille/...)</em> soit contenir un cookie <em>kz_metropole</em>". <br/>Tout ceci est calcul&eacute; automatiquement &agrave; la 1ere connexion du user.<br/>Si le user refuse de se faire geolocaliser ou si vous <strong>d&eacute;sactivez la geolocalisation des contenus</strong> les contenus ne seront pas filtr&eacute;s, m&ecirc;me si ils sont rattach&eacute;s &agrave; une m&eacute;tropole','kidzou'),
                                'default'  => '0',// 1 = on | 0 = off
                                'compiler'  => true
                            ),

                            array(
                                'id'       => 'geo_supported_post_types',
                                'type'     => 'select',
                                'title'    => __('Types de contenus sujets &agrave; geolocalisation ?', 'kidzou'), 
                                'subtitle'  => __('Par d&eacute;faut, les contenus de type <code>post, page</code> sont support&eacute;s.', 'kidzou'),
                                'desc'      => __('les types de contenu que vous choisirez seront <em>ajout&eacute;s</em> aux contenus nativement support&eacutes;s par Kidzou','kidzou'),
                                'data'      => 'post_types',
                                'multi'    => true,
                            ),

                            array(
                                'id'        => 'geo_mapquest_key',
                                'type'      => 'text',
                                'title'     => __('Cl&eacute; d\'API MapQuest', 'kidzou'),
                                'subtitle'  => __('Cette cl&eacute; est <strong>n&eacute;cessaire au bon fonctionnement de la geolocalisation des contenus</strong>. ', 'kidzou'),
                                'desc'      => __('La clef permet d&apos;utiliser l&apos;API <a href="http://developer.mapquest.com/fr/web/products/dev-services/geocoding-ws">Maquest</a> qui fournit la m&eacute;tropole a partir des coordonn&eacute;es GPS du navigateur. La <em>M&eacute;tropole</em> est ensuite pass&eacute;e en param&egrave;tre de la requ&ecirc;te pour filtrer les contenus en base de donn&eacute;e (les contenus sont rattach&eacute;s a une ville)','kidzou')
                            ),

                            array(
                                'id'        => 'googlemaps_key',
                                'type'      => 'text',
                                'title'     => __('Cl&eacute; d\'API Google Maps', 'kidzou'),
                                'subtitle'  => __('Cette cl&eacute; est <strong>n&eacute;cessaire au bon fonctionnement de la recherche de lieux dans l\'interface d\'admin</strong>. ', 'kidzou')
                            ),

                            // array(
                            //     'id'        => 'geo_national_metropole',
                            //     'type'      => 'select',
                            //     'data' => 'terms',
                            //     'args' => array('taxonomies'=>'ville', 'args'=>array()),
                            //     'title'     => __('Quelle ville a une port&eacute;e &eacute;tendue ?', 'kidzou'),
                            //     'subtitle'  => __('Lorsque des contenus y sont attach&eacute;s, ils sont visibles pour tous les utilisateurs quelque soit leur m&eacute;tropole de rattachement', 'kidzou'),
                            // ),

                            // array(
                            //     'id'        => 'geo_default_metropole',
                            //     'type'      => 'select',
                            //     'data' => 'terms',
                            //     'args' => array('taxonomies'=>'ville', 'args'=>array()),
                            //     'title'     => __('Ville par d&eacute;faut ?', 'kidzou'),
                            //     'subtitle'  => __('Si l&apos;utilisateur ne se geolocalise pas ou si une erreur survient lors de la geoloc...les contenus de cette ville lui sont affich&eacute;s', 'kidzou'),
                            // ),

                            array(
                                'id'        => 'geo_default_lat',
                                'type'      => 'text',
                                'validate' => 'numeric',
                                'title'     => __('Latitude par d&eacute;faut', 'kidzou'),
                                'subtitle'  => __('Si vous utilisez les fonctions de localisation de contenu par latitude/longitude, et que le user ne renvoie pas ses coordonn&eacute;es, cette latitude sera utilis&eacute;e par d&eacute;faut', 'kidzou'),
                                'desc'      => __('Si vous ne savez pas quoi mettre, mettez la latitude de la ville par d&eacute;faut. Le s&eacute;parateur de d&eacute;cimale est le : <em>point</em>','kidzou')
                            ),

                            array(
                                'id'        => 'geo_default_lng',
                                'type'      => 'text',
                                'validate' => 'numeric',
                                'title'     => __('Longitude par d&eacute;faut', 'kidzou'),
                                'subtitle'  => __('Si vous utilisez les fonctions de localisation de contenu par latitude/longitude, et que le user ne renvoie pas ses coordonn&eacute;es, cette longitude sera utilis&eacute;e par d&eacute;faut ', 'kidzou'),         
                                 'desc'      => __('Si vous ne savez pas quoi mettre, mettez la longitude de la ville par d&eacute;faut. Le s&eacute;parateur de d&eacute;cimale est le : <em>point</em>','kidzou')
     
                            ),

                            array(
                                'id'       => 'geo_sync_geods',
                                'type'     => 'checkbox',
                                'title'    => __('Synchroniser le plugin Geo Data Store avec Kidzou ?', 'kidzou'), 
                                'subtitle'  => __('Faites cela si vous avez install&eacute; le plugin Geo Data Store apr&egrave,s avoir associ&eacute; des posts avec des lieux. Une fois cette synchro effectu&eacute;e, le plugin sera synchronis&eacute; automatiquement ', 'kidzou'),
                                'desc'      => __('Ce r&eacute;glage sera remis &agrave; 0 une fois la page valid&eacute;e. Toutefois, la synchro sera bien d&eacute;clench&eacute;e','kidzou'),
                                'default'  => '0',// 1 = on | 0 = off
                            ),

                            array(
                                'id'        => 'geo_bypass_param',
                                'type'      => 'text',
                                'title'     => __('Param&egrave;tre de d&eacute;sactivation ?', 'kidzou'),
                                'subtitle'  => __('Lorsque ce param&egrave;tre est rep&eacute;r&eacute; en requ&ecirc;te, la geolocalisation ne filtre pas les contenus', 'kidzou'),     
                                'default'   => 'region',  
                                 // 'desc'      => __('Si vous ne savez pas quoi mettre, mettez la longitude de la ville par d&eacute;faut. Le s&eacute;parateur de d&eacute;cimale est le : <em>point</em>','kidzou')
     
                            ),

                            array(
                                'id'        => 'geo_bypass_regexp',
                                'type'      => 'text',
                                'title'     => __('Ne pas geolocaliser les contenus pour les URLs qui matchent cette Expression R&eacute;guli&egrave;re :', 'kidzou'),
                                'subtitle'  => __('Indiquer une regexp', 'kidzou'),     
                                'default'   => '\/api\/',  
        
                            ),

                            array(
                                'id'        => 'geo_search_radius',
                                'type'      => 'spinner',
                                'title'     => __('Rayon de recherche des lieux autour de l&apos;utilisateur', 'kidzou'),
                                'subtitle'  => __('Cela peut impacter la performance', 'kidzou'),
                                // 'desc'     => __('Attention &agrave; la performance pour les synchro de contenu', 'kidzou'),
                                'default'  => '15',
                                'min'      => '5',
                                'step'     => '1',
                                'max'      => '50'
                            ),



                        )
                    );
                
                //Ads
                $this->sections[] = array(
                    'title'     => __('Publicit&eacute;', 'kidzou'),
                    'desc'      => __('lorem ipsum', 'kidzou'),
                    'icon'      => 'el-icon-bullhorn',
                    'fields'    => array(

                        array(
                            'id'        => 'pub_header',
                            'type'      => 'ace_editor',
                            'title'     => __('Insertion de script dans le header', 'kidzou'),
                            'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                            'mode'      => 'html',
                            'theme'     => 'monokai',
                            'desc'      => 'Un javascript est attendu',
                            'default'   => ''
                        ),

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
                
                //API
                $this->sections[] = array(
                    'title'     => __('API Kidzou', 'kidzou'),
                    'desc'      => __('R&eacute;glages des API Kidzou', 'kidzou'),
                    'icon'      => 'el el-puzzle',
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

                        array(
                            'id'        => 'api_public_key',
                            'type'      => 'multi_text',
                            'subtitle'  => 'Cl&eacute; g&eacute;n&eacute;r&eacute;e au hasard :'.md5(uniqid()),
                            'title'     => __('Cle publique pour utilisation des API sans authentification', 'kidzou'),
                        ),

                        array(
                            'id'       => 'api_activate_cors',
                            'type'     => 'checkbox',
                            'title'    => __('Autoriser les CORS (Cross Origin Resource Sharing)', 'kidzou'), 
                            'subtitle'  => __('Cela permet l\'appel d\'API en dehors du domaine Kidzou', 'kidzou'),
                            'default'  => '0',// 1 = on | 0 = off
                        ),

                    )
                );
    
                //import
                $this->sections[] = array(
                    'title'     => __('Import de contenu', 'kidzou'),
                    'desc'      => __('Allofamille, Facebook', 'kidzou'),
                    'icon'      => 'el el-download',
                    'fields'    => array(

                        //user auquel sont rattachés les contenus importés
                        array(
                            'id'       => 'import_author_id',
                            'type'     => 'select',
                            'title'    => __('Auteur des contenus import&eacute;s', 'kidzou'), 
                            'subtitle' => __('Il est obligatoirement administrateur du site', 'kidzou'),
                            // 'desc'     => __('Cette liste se pr&eacute;-rempli automatiquement lorsque la cl&eacute; Mailchimp est renseign&eacute;e', 'kidzou'),
                            // Must provide key => value pairs for select options
                            'options'  => $users_list
                        ),

                        //Template de contenu (append)
                        array(
                            'id'        => 'import_content_append',
                            'type'      => 'ace_editor',
                            'title'     => __('Ajouter le contenu suivant en fin de contenu import&eacute;', 'kidzou'),
                            'subtitle'  => __('Code HTML', 'kidzou'),
                            'mode'      => 'html',
                            'theme'     => 'monokai',
                            // 'desc'      => 'Un javascript est attendu',
                            'default'   => ''
                        ),

                    )
                );
                
                //intégration Gravity Forms
                if ($gf) {

                    $this->sections[] = array(
                        'title'     => __('Gravity Forms', 'kidzou'),
                        'desc'      => __('Int&eacute;gration avec le plugin Gravity Forms pour exposition dans la config Kidzou', 'kidzou'),
                        'icon'      => 'el el-check',
                        'fields'    => array(

                            array(
                                'id'        => 'gf_form_id',
                                'type'      => 'select',
                                'title'     => __('Formulaire d&apos;envoi de photo ?', 'kidzou'),
                                'multi'    => false,
                                'options'  => $form_options
                            ),

                            array(
                                'id'        => 'gf_field_image_base64',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra la photo au format Base64 dans Gravity Forms WebAPI ?', 'kidzou'),
                                // 'desc'     => __('', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_image_url',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra l\'URL de l\'image t&eacute;l&eacute;charg&eacute;e ?', 'kidzou'),
                                // 'desc'     => __('', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_user_id',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra le login de l&apos;utilisateur courant ?', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_user_email',
                                'type'      => 'select',
                                'title'     => __('Le champ qui contient le mail du user', 'kidzou'),
                                'subtitle'  => __('Ce champ sera rempli automatiquement en fonction du ID du user, il servira pour notifier le user', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_post_id',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra le ID de l&apos;article courant ?', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_comment',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra le commentaire de la photo ?', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_field_title',
                                'type'      => 'select',
                                'title'     => __('Quel est le champ qui recevra le titre de la photo ?', 'kidzou'),
                                'multi'    => false,
                                'options'  => $fields_options
                            ),

                            array(
                                'id'        => 'gf_webapi_public_key',
                                'type'      => 'text',
                                'title'     => __('Public Key pour utilisation de WebAPI', 'kidzou'),
                            ),

                            array(
                                'id'        => 'gf_webapi_private_key',
                                'type'      => 'text',
                                'title'     => __('Private Key pour utilisation de WebAPI', 'kidzou'),
                            ),


                        )
                    );
                }

                //evenements
                $this->sections[] = array(
                    'title'     => __('Ev&eacute;nements', 'kidzou'),
                    'desc'      => __('Gestion des r&eacute;rrences, des &eacute;v&eacute;nements termin&eacute;s ', 'kidzou'),
                    'icon'      => 'el-icon-calendar',
                    'fields'    => array(

                        array(
                            'id'       => 'obsolete_events_unpublish',
                            'type'     => 'checkbox',
                            'title'    => __('D&eacute;publier les &eacute;v&eacute;nements termin&eacute;s ?', 'kidzou'), 
                            'default'  => '0'// 1 = on | 0 = off
                        ),

                        array(
                            'id'       => 'obsolete_events_remove_cats',
                            'type'     => 'select',
                            'multi'    => true,
                            'title'    => __('Supprimer les cat&eacute;gories suivantes des &eacute;v&eacute;nements termin&eacute;s', 'kidzou'), 
                            'data'      => 'categories',
                        ),
                        
                        array(
                            'id'       => 'obsolete_events_add_cats',
                            'type'     => 'select',
                            'multi'    => true,
                            'title'    => __('Ajouter les cat&eacute;gories suivantes pour les &eacute;v&eacute;nements termin&eacute;s', 'kidzou'), 
                            'data'      => 'categories',
                        ),

                        array(
                            'id'       => 'obsolete_events_remove_taxonomies',
                            'type'     => 'select',
                            'multi'    => true,
                            'title'    => __('Supprimer les taxonomies suivantes pour les &eacute;v&eacute;nements termin&eacute;s', 'kidzou'), 
                            'data'     => 'taxonomies',
                        ),
                        
                    )
                );
    
                //contributeurs
                $this->sections[] = array(
                    'title'     => __('Contributeurs', 'kidzou'),
                    'desc'      => __('Les contributeurs (les "Pro") peuvent ajouter leurs propres contenus sur la plateforme', 'kidzou'),
                    'icon'      => 'el-icon-edit',
                    'fields'    => array(

                        array(
                            'id'        => 'default_content',
                            'type'      => 'ace_editor',
                            'title'     => __('Contenu par d&eacute;faut lors de l&apos;&eacute;dition d&apos;un contenu', 'kidzou'),
                            'subtitle'  => __('Collez votre code HTML ici', 'kidzou'),
                            'mode'      => 'html',
                            'theme'     => 'monokai',
                            // 'desc'      => 'Un javascript est attendu',
                            'default'   => ''
                        ),
                        array(
                            'id'       => 'widget_watcher_activate',
                            'type'     => 'checkbox',
                            'title'    => __('Activer la surveillance de contenus externes ?', 'kidzou'), 
                            'default'  => '0'// 1 = on | 0 = off
                        ),
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
                        array(
                            'id'       => 'customer_analytics_activate',
                            'type'     => 'checkbox',
                            'title'    => __('Ouvrir l&apos;acc&egrave;s aux analytics pour les Pro', 'kidzou'), 
                            'default'  => '0'// 1 = on | 0 = off
                        ),
                        
                    )
                );

                //notifications
                $this->sections[] = array(
                    'title'     => __('Notifications', 'kidzou'),
                    'desc'      => __('Les notifications apparaissent en bas &agrave; droite des pages, elles sugg&egrave;rent des contenus ou des actions (call-to-action). <br/>L&apos;ensemble des messages &agrave; afficher sont dans une queue d&eacute;pil&eacute;e au fur et &agrave; mesure. <br/>Lorsqu&apos;un message est affich&eacute; un cookie est stock&eacute; sur le poste de l&apos;utilisateur pendant 30 jours de sorte qu&apos;il ne reverra plus cette notification pendant ce laps de temps. Le message suivant peut &ecirc;tre lu.<br/>Un utilisateur ne recoit que 1 seul message par page', 'kidzou'),
                    'icon'      => 'fa fa-bell-o',
                    'fields'    => array(

                        array(
                            'id'       => 'notifications_activate',
                            'type'     => 'checkbox',
                            'title'    => __('Activer les notifications ?', 'kidzou'), 
                            'default'  => '0',// 1 = on | 0 = off
                            'compiler'  => true
                        ),

                        array(
                            'id'       => 'notifications_messages_order',
                            'type'     => 'sortable',
                            'mode'      => 'checkbox',
                            'title'    => __('Contenu des messages', 'kidzou'), 
                            'subtitle' => __('si plusieurs messages sont dans la queue, trier les messages par ordre d&apos;apparition', 'kidzou'),
                            'options'  => array(
                                'newsletter'    => '<i class="fa fa-newspaper-o"></i>&nbsp;&nbsp;Inscription &agrave; la newsletter',
                                'vote'          => '<i class="fa fa-heart-o"></i>&nbsp;&nbsp;Recommandation d&apos;article', 
                                'featured'      => '<i class="fa fa-star"></i>&nbsp;&nbsp;Promotion d&apos;article', 
                                'cats'          => '<i class="fa fa-pencil-square-o"></i>&nbsp;&nbsp;Articles issues des cat&eacute;gories ci-dessous', 
                            ),
                            'default' => array(
                               'newsletter' => true,
                                'vote'      => true,
                                'featured'  => false
                            )
                        ),

                        array(
                            'id'       => 'notifications_post_type',
                            'type'     => 'checkbox',
                            'title'    => __('Activer les notifications pour les types de contenu :', 'kidzou'), 
                         
                            //Must provide key => value pairs for multi checkbox options
                            'options'  => array(
                                'post' => 'Post',
                                // 'offres' => 'Offres',
                                'page' => 'page'
                            ),
                         
                            //See how default has changed? you also don't need to specify opts that are 0.
                            'default' => array(
                                'post' => '1', 
                                // 'offres' => '0', 
                                'page' => '0'
                            ),
                            'compiler'  => true
                        
                        ),

                        array(
                            'id'       => 'notifications_context',
                            'type'     => 'radio',
                            'title'    => __('Fr&eacute;quence de notification', 'kidzou'), 
                            'subtitle' => __('Les messages appraissent a quelle frequence  ?', 'kidzou'),
                            'desc'     => __('La fr&eacute;quence de notification des newsletter est r&eacute;glable ci-dessous', 'kidzou'),
                            //Must provide key => value pairs for radio options
                            'options'  => array(
                                'daily' => '1 fois par jour', 
                                'page'  => 'Sur chaque page consult&eacute;e', 
                                'monthly' => '1 fois par mois',
                                'weekly' => '1 fois par semaine',
                            ),
                            'default' => 'page',
                            'compiler'  => true
                        ),

                        array(
                            'id'       => 'notifications_newsletter_context',
                            'type'     => 'spinner',
                            'title'    => __('Afficher le formulaire d&apos;inscription &agrave; la newsletter a quelle fr&eacute;quence ?', 'kidzou'), 
                            'subtitle' => __('En nombre de pages vues :', 'kidzou'),
                            'default'  => '3',
                            'min'      => '1',
                            'step'     => '1',
                            'max'      => '100',
                        ),

                        array(
                            'id'       => 'notifications_newsletter_nomobile',
                            'type'     => 'checkbox',
                            'default'  => '1',
                            'title'    => __('Ne pas afficher le formulaire Newsletter sur mobile', 'kidzou'),
                            'subtitle' => __('L&apos;experience utilisateur peut-&ecirc;tre mauvaise avec le formulaire newsletter dans la boite de Notification sur mobile', 'kidzou'),
                            // 'desc'     => __('Le nom de la cat&eacute;gorie', 'kidzou'),
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

                /**
                 * Sous section de mise en forme des messages de notification
                 */
                $this->sections[] = array(
                    'icon'       => 'el-icon-website',
                    'title'      => __( 'Mise en forme', 'kidzou' ),
                    'subsection' => true,
                    'fields'     => array(
                        
                        array(
                            'id'       => 'notifications_icon_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS de l&apos;icone <code>&lt;i&gt;</code>'),
                        ),
                        array(
                            'id'       => 'notifications_icon_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style de l&apos;icone <code>&lt;i&gt;</code>'),
                        ),
                        
                    )
                );

                $this->sections[] = array(
                    'title'     => __('Newsletter', 'kidzou'),
                    'icon'      => 'fa fa-envelope-o',
                    'fields'    => array(

                        array(
                            'id'        => 'mailchimp_key',
                            'type'      => 'text',
                            'title'     => __('Cl&eacute; d&apos;API pour int&eacute;gration Mailchimp', 'kidzou'),
                            'validate_callback' => 'set_mailchimp_lists'
                        ),
                        array(
                            'id'       => 'mailchimp_list',
                            'type'     => 'select',
                            'title'    => __('Liste Mailchimp pour la souscription Newsletter', 'kidzou'), 
                            'subtitle' => __('Cette liste est utlis&eacute;e dans les notifications par exemple', 'kidzou'),
                            'desc'     => __('Cette liste se pr&eacute;-rempli automatiquement lorsque la cl&eacute; Mailchimp est renseign&eacute;e', 'kidzou'),
                            // Must provide key => value pairs for select options
                            'options'  => $mailchimp_lists
                        ),
                        array(
                            'id'       => 'newsletter_fields',
                            'type'     => 'checkbox',
                            'title'    => __('Champs du formulaire Newsletter', 'kidzou'), 
                         
                            //Must provide key => value pairs for multi checkbox options
                            'options'  => array(
                                'firstname' => 'Pr&eacute;nom',
                                'lastname' => 'Nom',
                                'zipcode' => 'Code Postal',
                            ),
                         
                            //See how default has changed? you also don't need to specify opts that are 0.
                            'default' => array(
                                'firstname' => '0', 
                                'lastname' => '0', 
                                'zipcode' => '1'
                            )
                        
                        )
                    )
                );
                
                /**
                 * Sous section de mise en forme des messages de notification
                 */
                $this->sections[] = array(
                    'icon'       => 'el-icon-website',
                    'title'      => __( 'Mise en forme', 'kidzou' ),
                    'subsection' => true,
                    'fields'     => array(

                        array(
                            'id'        => 'newsletter_header',
                            'type'      => 'ace_editor',
                            'title'     => __('Header du formulaire de Newsletter', 'kidzou'),
                            'subtitle'  => __('Code HTML', 'kidzou'),
                            'mode'      => 'html',
                            'theme'     => 'monokai',
                            // 'desc'      => 'Un javascript est attendu',
                            'default'   => ''
                        ),

                        array(
                            'id'       => 'newsletter_form_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS de l&apos;&eacute;l&eacute;ment <code>&lt;form&gt;</code>'),
                        ),
                        array(
                            'id'       => 'newsletter_form_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style de l&apos;&eacute;l&eacute;ment <code>&lt;form&gt;</code>'),
                        ),
                        array(
                            'id'       => 'newsletter_labels_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS des <code>&lt;label&gt;</code> de formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_labels_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style des <code>&lt;label&gt;</code> de formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_input_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS des champs <code>&lt;input&gt;</code> du formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_input_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style des champs <code>&lt;input&gt;</code> du formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_button_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS du <code>&lt;button&gt;</code> de formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_button_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style du <code>&lt;button&gt;</code> de formulaire'),
                        ),
                        array(
                            'id'       => 'newsletter_error_class',
                            'type'     => 'text',
                            'title'    => __('Classe CSS du message d&apos;erreur'),
                        ),
                        array(
                            'id'       => 'newsletter_error_style',
                            'type'     => 'text',
                            'title'    => __('Inline Style du message d&apos;erreur'),
                        ),

                        array(
                            'id'        => 'newsletter_footer',
                            'type'      => 'ace_editor',
                            'title'     => __('Footer du formulaire de Newsletter', 'kidzou'),
                            'subtitle'  => __('Code HTML', 'kidzou'),
                            'mode'      => 'html',
                            'theme'     => 'monokai',
                            // 'desc'      => 'Un javascript est attendu',
                            'default'   => ''
                        ),
                    )
                );

                // ACTUAL DECLARATION OF SECTIONS
                $this->sections[] = array(
                    'title'     => __('Apache mod_pagespeed', 'kidzou'),
                    'icon'      => 'fa fa-bolt',
                    'fields'    => array(

                        array(
                            'id'=>'perf_js_no_async',
                            'type' => 'multi_text',
                            'title' => __('Prot&eacute;ger les scripts suivants de l&apos;optimisation defer/async', 'kidzou'),
                            'subtitle' => __('Cela pose le tag <em>data-pagespeed-no-defer</em> sur les scripts concern&eacute;s', 'kidzou'),
                        ),

                        array(
                            'id'        => 'perf_remove_css_id',
                            'type'      => 'checkbox',
                            'default'      => '0',
                            'title'     => __('Supprimer les ID des balises <em><link></em> de chargement CSS', 'kidzou'),
                            'subtitle'  => __('N&eacute;cessaire pour s&apos;int&eacute;grer avec mod_pagespeed', 'kidzou'),

                        ),
                        
                    )
                );

                    $this->sections[] = array(
                        'title'     => __('Notes de Livraisons', 'kidzou'),
                        'icon'      => 'fa fa-file-text-o',
                        'fields'    => array(

                            array( 
                                'id'       => 'release_notes',
                                'type'     => 'raw',
                                'title'    => __('Quoi de neuf ?', 'kidzou'),
                                'content'  => file_get_contents(dirname(__FILE__) . '../../../README.txt')
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
                'intro_text' => '<p>Nous avons rangé ici tous les petits réglages de Kidzou.</p><br/><em>Version : '.Kidzou::VERSION.'</em>',
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
                'dev_mode' => false
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
 * A la validation d'une Clé Mailchimp, récupère les listes
 **/
if (!function_exists('get_mailchimp_lists')):
 
    function get_mailchimp_lists($key) {

        if ($key=='' || $key==null)
            return array();

        $lists = array();

        $mailchimp = new MailChimp( $key );
        $retval = $mailchimp->call('lists/list');
        // Kidzou_Utils::log($retval);
        foreach ( $retval['data'] as $list ) {
            $lists[$list['id']] = $list['name'];
        }

        // Kidzou_Utils::log('MailChimp Lists : ');
        // Kidzou_Utils::log($lists);
        
        return $lists;
    }
 
endif;
            

/**
 * A la validation d'une Clé Mailchimp, récupère les listes
 **/
if (!function_exists('set_mailchimp_lists')):
 
    function set_mailchimp_lists($field, $value, $existing_value) {

        $return['value'] = $value;
        $transient = get_transient( 'kz_mailchimp_lists' ) ;

        //si la valeur change ,on récupère les listes
        if ( $value!=$existing_value || (false === $transient ) ) 
        {
            try {

                $lists = get_mailchimp_lists($value);

                delete_transient('kz_mailchimp_lists');
                set_transient( 'kz_mailchimp_lists', $lists, 0 ); //never expires ! 

            } catch ( Exception $exc ) {
                
                Kidzou_Utils::log($exc);
                $return['error'] = $field;
                $value = $existing_value;
                $field['msg'] = __('Les listes Mailchimp n&apos;ont pas pu &ecirc;tre r&eacute;cup&eacute;r&eacute;es avec cette cl&eacute;','kidzou');
            }
        }
        
        return $return;
    }
 
endif;

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

        $return['value'] = $value;
        if ($error == true) {
            $return['error'] = $field;
        }
        return $return;
    }
endif;





