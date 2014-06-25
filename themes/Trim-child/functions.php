<?php

// require_once (get_stylesheet_directory() . '/includes/kidzou-megadropdown.php');
require_once (get_stylesheet_directory()  . '/includes/kidzou-shortcodes.php');

function kz_top_panel()
{
    include( get_stylesheet_directory()  . '/includes/top_panel.php');
}

function kz_entry_content()
{
    if (is_home()) {
        include( get_stylesheet_directory() . '/includes/compact-article.php');
    } else {
        include( get_stylesheet_directory() . '/includes/article.php');
    }
}

function kz_home_content()
{
    include( get_stylesheet_directory() . '/includes/compact-home-content.php');
}

function kz_postinfo_cats( )
{
    global $post;

    $postinfo_meta = '';

    $postinfo_meta .= get_the_category_list('&nbsp;') ; //pour parsing des microdata "keywords"
    $postinfo_meta .= get_the_term_list( $post->ID, 'ville', '<span class="ville">&nbsp;', ' ', '</span>' );
    $postinfo_meta .= get_the_term_list( $post->ID, 'age', '<span class="age">&nbsp;', ' ', '</span>' );
    $postinfo_meta .= get_the_term_list( $post->ID, 'divers', '<span class="divers">&nbsp;', ' ', '</span>' ) ;

    echo $postinfo_meta;

}

function kz_post_comment_block ($index_postinfo) {

    echo '<i class="icon-comment"></i>';

    return et_postinfo_meta( $index_postinfo, get_option('trim_date_format'), esc_html__('0 commentaires','Trim'), esc_html__('1 commentaire','Trim'), '% ' . esc_html__('commentaires','Trim') );
}


add_action('init','kz_activate_ptemplates', 0);
/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_activate_ptemplates(){

    /* activate page templates */
    require_once(get_stylesheet_directory() . '/epanel/page_templates/page_templates.php');
}


add_action( 'after_setup_theme', 'remove_parent_theme_assets', 11 );

/**
 * supprimer les chargements inutiles, executé après le after_setup_theme du parent
 *
 * @see http://www.themelab.com/2010/07/11/remove-code-wordpress-header/
 * @return void
 * @author 
 **/ 
function remove_parent_theme_assets() 
{
    // our code here
    // remove_action('et_head_meta','et_add_google_fonts'); //load it last

    //remove unecessary tags
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'start_post_rel_link');
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'adjacent_posts_rel_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');

    remove_action( 'wp_head', 'et_set_font_properties' );
    remove_action( 'wp_head', 'et_set_bg_properties' );
    
    remove_action( 'et_header_menu', 'et_add_mobile_navigation' );
    add_action('et_header_menu', 'et_child_add_mobile_navigation' );

    remove_action( 'wp_enqueue_scripts', 'et_load_trim_scripts' );
    add_action('wp_enqueue_scripts', 'et_child_load_trim_scripts' );

    remove_action( 'wp_enqueue_scripts', 'et_add_responsive_shortcodes_css', 11 );
    add_action('wp_enqueue_scripts', 'et_child_add_responsive_shortcodes_css',11 );

    add_action('wp_print_styles','et_child_ptemplates_css', 0);
    add_action('wp_print_scripts','et_child_ptemplates_footer_js', 0);

    remove_action('wp_head','head_addons',7);
    add_action('wp_head','child_head_addons');

    //surcharge des tailles de thumb
    require_once(get_stylesheet_directory() . '/epanel/post_thumbnails_trim.php');

    // load_child_theme_textdomain( 'Kidzou', get_stylesheet_directory() . '/lang' );

    add_action( 'widgets_init', 'kz_unregister_sidebars', 99 );

    //ne pas charger les font google, elles sont chargées en css
    remove_action('et_head_meta','et_add_google_fonts');

    //print stylesheet
    add_action('wp_print_styles','et_child_print_css');
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function child_head_addons()
{
    global $shortname, $default_colorscheme;

    if ( apply_filters('et_get_additional_color_scheme',et_get_option($shortname.'_color_scheme')) <> $default_colorscheme ) { ?>
        <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/style-' . et_get_option($shortname.'_color_scheme') . '.css' ); ?>" type="text/css" media="screen" />
    <?php };

    if ( et_get_option($shortname.'_child_css') == 'on' && et_get_option($shortname.'_child_cssurl') <> '' ) { //Enable child stylesheet  ?>
        <link rel="stylesheet" href="<?php echo esc_url( et_get_option($shortname.'_child_cssurl') ); ?>" type="text/css" media="screen" />
    <?php };

    if ( et_get_option( $shortname . '_custom_colors' ) == 'on' ) et_epanel_custom_colors_css();

}

/**
 * les css responsive_shortcodes ne sont ajoutés que si is_singular() 
 *
 * @see http://codex.wordpress.org/Function_Reference/is_singular
 * @return void
 * @author 
 **/
function et_child_add_responsive_shortcodes_css()
{
    global $shortname;
    if ( 'on' == et_get_option( $shortname . '_responsive_shortcodes', 'on' ) && is_singular())
        wp_enqueue_style( 'et-shortcodes-responsive-css', ET_SHORTCODES_DIR . '/css/shortcodes_responsive.css', false, ET_SHORTCODES_VERSION, 'all' );

    //always disable et_shotcodes_frontend.js, car il y a des interactions je sais pas ou avec knockout...
    wp_deregister_script('et-shortcodes-js');
}

/**
 * optimisation des scripts chargés
 *
 * @see http://codex.wordpress.org/Function_Reference/get_template_directory_uri
 * @return void
 * @author 
 **/
function et_child_load_trim_scripts ()
{
    if ( !is_admin() ){
        $template_dir = get_template_directory_uri(); //the one of the parent

        //wp_enqueue_script('superfish', $template_dir . '/js/superfish.js', array('jquery'), '1.0', true);
        // wp_enqueue_script('easing', $template_dir . '/js/jquery.easing.1.3.js', array('jquery'), '1.0', true);
        if ( is_home() )
            wp_enqueue_script('flexslider', $template_dir . '/js/jquery.flexslider-min.js', array('jquery'), '1.0', true);

        // wp_enqueue_script('fitvids', $template_dir . '/js/jquery.fitvids.js', array('jquery'), '1.0', true);

        require_once (WP_PLUGIN_DIR . '/kidzou/kidzou.php'); 

        $js_path = (KIDZOU_VERSION!='dev' ? "dist/" : "" );
        wp_enqueue_script('custom_script', get_stylesheet_directory_uri() . '/js/'.$js_path.'custom.'.KIDZOU_VERSION.'.min.js', array('jquery'), '1.0', true);

        $admin_access = apply_filters( 'et_showcontrol_panel', current_user_can('switch_themes') );
        if ( $admin_access && get_option('trim_show_control_panel') == 'on' ) {
            wp_enqueue_script('et_colorpicker', $template_dir . '/epanel/js/colorpicker.js', array('jquery'), '1.0', true);
            wp_enqueue_script('et_eye', $template_dir . '/epanel/js/eye.js', array('jquery'), '1.0', true);
            wp_enqueue_script('et_cookie', $template_dir . '/js/jquery.cookie.js', array('jquery'), '1.0', true);
            wp_enqueue_script('et_control_panel', $template_dir . '/js/et_control_panel.js', array('jquery'), '1.0', true);
            wp_localize_script( 'et_control_panel', 'et_control_panel', apply_filters( 'et_control_panel_settings', array( 'theme_folder' => $template_dir ) ) );
        }
    }
    if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' );
}

/**
 * override pour respecter les normes W3C : <a> ne doit pas contenir de <a>
 * ajout également de la classe mobile_nav pour selecteur CSS 
 *
 * @return HTML de la Navigation mobile
 * @author 
 **/
function et_child_add_mobile_navigation()
{
    echo '<nav id="mobile_nav" class="mobile_nav closed" itemscope itemtype="http://schema.org/SiteNavigationElement">' . esc_html__( 'Les Rubriques', 'Trim' ) . '</nav>';
}

/**
 * undocumented function
 *
 * @return HTML des meta du post
 * @author 
 **/
function et_postinfo_meta( $postinfo, $date_format, $comment_zero, $comment_one, $comment_more )
{
    global $themename;

    $postinfo_meta = '';

    if ( in_array( 'author', $postinfo ) ){
        $postinfo_meta .= ' ' . esc_html__('by',$themename) . ' ' . et_get_the_author_posts_link();
    }

    if ( in_array( 'date', $postinfo ) )
        $postinfo_meta .= ' ' . esc_html__('on',$themename) . ' ' . get_the_time( $date_format );

    if ( in_array( 'categories', $postinfo ) )
        $postinfo_meta .= ' ' . esc_html__('in',$themename) . ' ' . get_the_category_list(', ');

    if ( in_array( 'comments', $postinfo ) )
         $postinfo_meta .=  et_get_comments_popup_link( $comment_zero, $comment_one, $comment_more );
        // $postinfo_meta .= ' | ' . et_get_comments_popup_link( $comment_zero, $comment_one, $comment_more );

    // if ( '' != $postinfo_meta ) $postinfo_meta = __('Posted',$themename) . ' ' . $postinfo_meta;

    echo $postinfo_meta;
}
    

/**
 * remove_action n'est pas executé dans le hook after_setup_theme
 * car celui-ci s'execute trop tot pour que page_templates.php ait été lu et que les actions soient référencées
 *
 * @return void
 * @author 
 **/
function et_child_ptemplates_css(){
    remove_action('wp_print_styles','et_ptemplates_css');
    if ( !is_admin() && !(strstr( $_SERVER['PHP_SELF'], 'wp-login.php')) ) { //&& is_page()
        // wp_enqueue_style( 'fancybox', ET_PT_PATH . '/js/fancybox/jquery.fancybox-2.1.5.css', array(), '2.1.5q', 'screen' );
        wp_enqueue_style( 'fancybox', ET_PT_PATH . '/js/fancybox/jquery.fancybox-1.3.4.css', array(), '1.3.4', 'screen' );
        wp_enqueue_style( 'et_page_templates', ET_PT_PATH . '/page_templates.css', array(), '1.8', 'screen' );
    }
}

/**
 * ajout d'une stylesheet dédiée au print 
 *
 * @return void
 * @author 
 **/
function et_child_print_css(){
    //ne fonctionne pas
    //les impressions sont rendues sans tenir compte de cette stylesheet...
    // wp_enqueue_style( 'print', get_stylesheet_directory_uri().'/print.css', array(), '1.1', 'print' );
}

/**
 * je voulais upgrader à FancyBox 2.1.5 mais la licence commerciale a changé
 * il faut payer pour l'utiliser
 *
 * @return void
 * @author 
 **/
function et_child_ptemplates_footer_js(){
    // remove_action('wp_print_scripts','et_ptemplates_footer_js');
    // global $themename;
    // if ( !is_admin() ) {
    //     wp_enqueue_script( 'fancybox', ET_PT_PATH . '/js/fancybox/jquery.fancybox-2.1.5.pack.js', array('jquery'), '2.1.5', true );
    //     wp_enqueue_script( 'et-ptemplates-frontend', ET_PT_PATH . '/js/et-ptemplates-frontend.js', array('jquery','fancybox'), '1.1', true );
    //     wp_localize_script( 'et-ptemplates-frontend', 'et_ptemplates_strings', array( 'captcha' => esc_html__( 'Captcha', $themename ), 'fill' => esc_html__( 'Fill', $themename ), 'field' => esc_html__( 'field', $themename ), 'invalid' => esc_html__( 'Invalid email', $themename ) ) );
    //     wp_enqueue_script( 'easing', ET_PT_PATH . '/js/fancybox/jquery.easing-1.3.pack.js', array('jquery'), '1.3.4', true );
    // }
}

/**
 * reecriture du footer pour une meilleure qualité HTML 
 * eviter les trous de titres (ne pas passer d'un h2 à un h4 par exemple), or le footer natif utilise des h4 sans avoir forcément 
 * un parent h3, ni h2 d'ailleurs
 * pour cela j'ai réécri en <ul><li>
 *
 * @return void
 * @author 
 **/
function kz_unregister_sidebars()
{
    unregister_sidebar( 'sidebar' );
    unregister_sidebar( 'footer-area-1' );
    unregister_sidebar( 'footer-area-2' );
    unregister_sidebar( 'footer-area-3' );

    register_sidebar( array(
        'name' => 'Sidebar',
        'id' => 'sidebar',
        'before_widget' => '<ul id="%1$s" class="widget %2$s">',
        'after_widget' => '</ul> <!-- end .widget -->',
        'before_title' => '<li class="widget_title">',
        'after_title' => '</li>',
    ) );

    register_sidebar( array(
        'name' => 'Footer Area #1',
        'id' => 'footer-area-1',
        'before_widget' => '<ul id="%1$s" class="f_widget %2$s">',
        'after_widget' => '</ul> <!-- end .f_widget -->',
        'before_title' => '<li class="widgettitle">',
        'after_title' => '</li>',
    ) );

    register_sidebar( array(
        'name' => 'Footer Area #2',
        'id' => 'footer-area-2',
        'before_widget' => '<ul id="%1$s" class="f_widget %2$s">',
        'after_widget' => '</ul> <!-- end .f_widget -->',
        'before_title' => '<li class="widgettitle">',
        'after_title' => '</li>',
    ) );

    register_sidebar( array(
        'name' => 'Footer Area #3',
        'id' => 'footer-area-3',
        'before_widget' => '<ul id="%1$s" class="f_widget %2$s">',
        'after_widget' => '</ul> <!-- end .f_widget -->',
        'before_title' => '<li class="widgettitle">',
        'after_title' => '</li>',
    ) );
}

/**
 * ajout de microdata dans les commentaires
 *
 * @return voidet_child_ptemplates_footer_js
 * @author 
 **/
function et_custom_comments_display($comment, $args, $depth) 
{
    $GLOBALS['comment'] = $comment; ?>
    <li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
        <article id="comment-<?php comment_ID(); ?>" class="comment-body" itemprop="comment" itemscope itemtype="http://schema.org/Comment">
            <div class="comment-meta commentmetadata clearfix">
                <div class="avatar-box">
                    <?php echo get_avatar($comment,$size='36'); ?>
                </div> <!-- end .avatar-box -->

                <?php printf('<span itemprop="author" itemscope itemtype="http://schema.org/Person"><span class="fn" itemprop="name">%s</span></span>', get_comment_author_link()) ?>
                <span class="comment_date" itemprop="dateCreated" datetime="<?php get_comment_date(); ?>">
                    <?php
                        /* translators: 1: date, 2: time */
                        printf( __( '%1$s', 'Trim' ), get_comment_date() );
                    ?>
                </span>
                <?php edit_comment_link( esc_html__( '(Edit)', 'Trim' ), ' ' ); ?>
            </div><!-- .comment-meta .commentmetadata -->

            <?php if ($comment->comment_approved == '0') : ?>
                <em class="moderation"><?php esc_html_e('Your comment is awaiting moderation.','Trim') ?></em>
                <br />
            <?php endif; ?>

            <div class="comment-content clearfix" itemprop="text">
                <?php comment_text() ?>

                <?php
                    $et_comment_reply_link = get_comment_reply_link( array_merge( $args, array('reply_text' => esc_attr__('Reply','Trim'),'depth' => $depth, 'max_depth' => $args['max_depth'])) );
                    if ( $et_comment_reply_link ) echo '<div class="reply-container">' . $et_comment_reply_link . '</div>';
                ?>
            </div> <!-- end comment-content-->
        </article> <!-- end comment-body -->
<?php 
}

add_filter('manage_posts_columns', 'kz_featured_table_head');
function kz_featured_table_head($columns) {
    $columns['featured'] = 'Featured';
    return $columns;
}

add_action( 'manage_posts_custom_column', 'kz_featured_table_content', 10, 2 );
function kz_featured_table_content( $column_name, $post_id ) {
    if ($column_name=='featured') {
        $settings      = get_post_meta($post_id, '_et_trim_settings', TRUE);
        if (isset($settings['et_is_featured']))
            echo ($settings['et_is_featured']==1 ? 'Featured' : '') ;
    }
}

add_filter( 'manage_edit-post_sortable_columns', 'kz_featured_table_sorting' );
function kz_featured_table_sorting( $columns ) {
    $columns['featured'] = 'featured';
    return $columns;
}

//todo
// add_filter( 'request', 'kz_featured_column_orderby' );
// function kz_featured_column_orderby( $vars ) {

//     //on ne peut pas trier par metropole, pq ce n'est pas une meta stockée mais calculée
//     if ( isset( $vars['orderby'] ) && 'featured' == $vars['orderby'] ) {
//         $vars = array_merge( $vars, array(
//             'meta_key' => 'kz_event_start_date',
//             'orderby' => 'meta_value'
//         ) );
//     }
//     return $vars;
// }

?>