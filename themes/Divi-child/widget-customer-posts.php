<?php 
/**
 * Ce widget retrouve les posts associés à un même client et les rend sous forme de widget
 * 
 *
 * @see Kidzou_Customer::getPostsByCustomerID()
 * @see WP_Widget
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class CustomerPostsWidget extends WP_Widget
{
	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'widget_client_rp', // Base ID
			__( 'Contenus associ&eacute;s [Kidzou]', 'Divi' ), // Name
			array( 'description' => __( 'Si le contenu principal est associ&eacute; a un client, ce widget affiche les autres contenus du client', 'Divi' ), ) // Args
		);
	}

	/**
	 * Back-end widget form.
	 *
	 * @see	WP_Widget::form()
	 *
	 * @param	array	$instance	Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$limit = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : '';
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
			<?php _e( 'Title', 'Divi' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>">
			<?php _e( 'No. of posts', 'Divi' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" />
			</label>
		</p>
		
		<?php
	} //ending form creation

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param 	array	$new_instance Values just sent to be saved.
	 * @param 	array	$old_instance Previously saved values from database.
	 *
	 * @return 	array	Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['limit'] = $new_instance['limit'];
		
		delete_post_meta_by_key( 'kz_client_related_posts_widget' ); // Delete the cache
		return $instance;
	} //ending update

	/**
	 * Front-end display of widget.
	 * 
	 * Le widget applique quelques filtres : 
	 * * exclusion du post en cours d'affichage
	 * * exclusion des posts archivés 
	 *
	 * @see WP_Widget::widget()
	 * @uses array_filter()
	 *
	 * @param	array	$args	Widget arguments.
	 * @param	array	$instance	Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $wpdb, $post;

		extract( $args, EXTR_SKIP );


		if ( is_single()  ) {

			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? strip_tags( "Contenus associ&eacute;" ) : $instance['title'] ); //str_replace( "%postname%", $post->post_title, $crp_settings['title'] )
			$limit = $instance['limit'];
			if ( empty( $limit ) ) $limit = 5;//$crp_settings['limit'];

			

			$posts = Kidzou_Customer::getPostsByCustomerID();

			/**
			 * Les events inactifs sont filtrés de même que le post lui même
			 */
			$filtered =  array_filter($posts, function($entry) {
				global $post;
			    $is_active = Kidzou_Events::isTypeEvent($entry->ID) ? Kidzou_Events::isEventActive($entry->ID) : true;
			    $is_self = ($post->ID == $entry->ID);
			    // Kidzou_Utils::log(
			    // 	array('widget filter'=> 
			    // 			array('is_self'=> $is_self, 'is_active'=> $is_active, 'is_type_event'=>  Kidzou_Events::isTypeEvent($entry->ID), 'is_event_active'=>Kidzou_Events::isEventActive($entry->ID) )
			    // 		), true);
			    return !$is_self && $is_active;
			}); 

			$output = '';

			if (count($filtered)>0) {
				$output = $before_widget;
				$output .= $before_title . $title . $after_title;
				$output .= "<div class='et_pb_widget woocommerce widget_products sidebar_posts_list'><ul class='product_list_widget'>";
			}

			foreach ($filtered as $_post) {
				$thumbnail = get_thumbnail( 50, 50, 'attachment-shop_thumbnail wp-post-image', $_post->post_title, $_post->post_title, false, 'thumbnail' );
				$thumb = $thumbnail["thumb"];
				$output .= "<li class='sidebar_post_item'><a href='".get_permalink($_post->ID)."'>";
				$output .= print_thumbnail( $thumb, $thumbnail["use_timthumb"], $_post->post_title, '', '', '', false); 
				$output .= $_post->post_title."</a></li>";
			}

			if (count($filtered)>0) {
				$output .= "</ul></div>";
			}


			$output .= $after_widget;

			echo $output;
		}
	} //ending function widget
}// end AdsenseWidget class

function CustomerPostsWidgetInit() {
	register_widget('CustomerPostsWidget');
}

add_action('widgets_init', 'CustomerPostsWidgetInit'); ?>