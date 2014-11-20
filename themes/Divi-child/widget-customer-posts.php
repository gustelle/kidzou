<?php class CustomerPostsWidget extends WP_Widget
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
	 * @see WP_Widget::widget()
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

			$output = '';

			if (count($posts)>0) {
				$output = $before_widget;
				$output .= $before_title . $title . $after_title;
				$output .= "<div class='et_pb_widget woocommerce widget_products sidebar_posts_list'><ul class='product_list_widget'>";
			}

			foreach ($posts as $post) {
				$thumbnail = get_thumbnail( 50, 50, 'attachment-shop_thumbnail wp-post-image', $post->post_title, $post->post_title, false, 'thumbnail' );
				$thumb = $thumbnail["thumb"];
				$output .= "<li class='sidebar_post_item'><a href='".get_permalink()."'>";
				$output .= print_thumbnail( $thumb, $thumbnail["use_timthumb"], $post->post_title, $instance['thumb_width'], $instance['thumb_height'], '', false); 
				$output .= $post->post_title."</a></li>";
			}

			if (count($posts)>0) {
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