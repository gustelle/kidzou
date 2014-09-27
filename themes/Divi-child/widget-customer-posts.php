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
		$show_excerpt = isset( $instance['show_excerpt'] ) ? esc_attr( $instance['show_excerpt'] ) : '';
		$show_author = isset( $instance['show_author'] ) ? esc_attr( $instance['show_author'] ) : '';
		$show_date = isset( $instance['show_date'] ) ? esc_attr( $instance['show_date'] ) : '';
		$post_thumb_op = isset( $instance['post_thumb_op'] ) ? esc_attr( $instance['post_thumb_op'] ) : '';
		$thumb_height = isset( $instance['thumb_height'] ) ? esc_attr( $instance['thumb_height'] ) : '';
		$thumb_width = isset( $instance['thumb_width'] ) ? esc_attr( $instance['thumb_width'] ) : '';
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
		<p>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>">
			<input id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php if ( $show_excerpt ) echo 'checked="checked"' ?> /> <?php _e( ' Show excerpt?', CRP_LOCAL_NAME ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_author' ); ?>">
			<input id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" type="checkbox" <?php if ( $show_author ) echo 'checked="checked"' ?> /> <?php _e( ' Show author?', CRP_LOCAL_NAME ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>">
			<input id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" <?php if ( $show_date ) echo 'checked="checked"' ?> /> <?php _e( ' Show date?', CRP_LOCAL_NAME ); ?>
			</label>
		</p>
		<p>
			<?php _e( 'Thumbnail options', 'Divi' ); ?>: <br />
			<select class="widefat" id="<?php echo $this->get_field_id( 'post_thumb_op' ); ?>" name="<?php echo $this->get_field_name( 'post_thumb_op' ); ?>">
			  <option value="inline" <?php if ( 'inline' == $post_thumb_op ) echo 'selected="selected"' ?>><?php _e( 'Thumbnails inline, before title', 'Divi' ); ?></option>
			  <option value="after" <?php if ( 'after' == $post_thumb_op ) echo 'selected="selected"' ?>><?php _e( 'Thumbnails inline, after title', 'Divi' ); ?></option>
			  <option value="thumbs_only" <?php if ( 'thumbs_only' == $post_thumb_op ) echo 'selected="selected"' ?>><?php _e( 'Only thumbnails, no text', 'Divi' ); ?></option>
			  <option value="text_only" <?php if ( 'text_only' == $post_thumb_op ) echo 'selected="selected"' ?>><?php _e( 'No thumbnails, only text.', 'Divi' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'thumb_height' ); ?>">
			<?php _e( 'Thumbnail height', 'Divi' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'thumb_height' ); ?>" name="<?php echo $this->get_field_name( 'thumb_height' ); ?>" type="text" value="<?php echo esc_attr( $thumb_height ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'thumb_width' ); ?>">
			<?php _e( 'Thumbnail width', 'Divi' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'thumb_width' ); ?>" name="<?php echo $this->get_field_name( 'thumb_width' ); ?>" type="text" value="<?php echo esc_attr( $thumb_width ); ?>" />
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
		$instance['show_excerpt'] = $new_instance['show_excerpt'];
		$instance['show_author'] = $new_instance['show_author'];
		$instance['show_date'] = $new_instance['show_date'];
		$instance['post_thumb_op'] = $new_instance['post_thumb_op'];
		$instance['thumb_height'] = $new_instance['thumb_height'];
		$instance['thumb_width'] = $new_instance['thumb_width'];
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

		// global $crp_settings;

		// parse_str( $crp_settings['exclude_on_post_types'], $exclude_on_post_types );	// Save post types in $exclude_on_post_types variable
		// if ( in_array( $post->post_type, $exclude_on_post_types ) ) return 0;	// Exit without adding related posts

		// $exclude_on_post_ids = explode( ',', $crp_settings['exclude_on_post_ids'] );

		if ( is_single()  ) {

			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? strip_tags( "Contenus associ&eacute;" ) : $instance['title'] ); //str_replace( "%postname%", $post->post_title, $crp_settings['title'] )
			$limit = $instance['limit'];
			if ( empty( $limit ) ) $limit = 5;//$crp_settings['limit'];

			$output = $before_widget;
			$output .= $before_title . $title . $after_title;
			// $output .= ald_crp( array(
			// 	'is_widget' => 1,
			// 	'limit' => $limit,
			// 	'show_excerpt' => $instance['show_excerpt'],
			// 	'show_author' => $instance['show_author'],
			// 	'show_date' => $instance['show_date'],
			// 	'post_thumb_op' => $instance['post_thumb_op'],
			// 	'thumb_height' => $instance['thumb_height'],
			// 	'thumb_width' => $instance['thumb_width'],
			// ) );

			$posts = Kidzou_Customer::getPostsByCustomerID();

			if (count($posts)>0) {
				$output .= "<div class='et_pb_widget woocommerce widget_products sidebar_posts_list'><ul class='product_list_widget'>";
			}

			foreach ($posts as $post) {
				$thumbnail = get_thumbnail( 157, 157, 'attachment-shop_thumbnail wp-post-image', $post->post_title, $post->post_title, false, 'thumbnail' );
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