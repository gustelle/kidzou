<?php

$parent_directory = get_template_directory();

if (!class_exists('ET_Builder_Module')) {
	require_once $parent_directory.'/includes/builder/functions.php';
	require_once $parent_directory.'/includes/builder/class-et-builder-element.php';
	require_once $parent_directory.'/includes/builder/class-et-global-settings.php';
}

if (!class_exists('ET_Builder_Module_Posts')) {
	require_once $parent_directory.'/includes/modules.php';
}


class ET_Builder_Module_Events extends ET_Builder_Module_Posts {

	function init() {
		$this->template_name = 'module-events';
		$this->name = esc_html__( 'Events', 'extra' );
		$this->slug = 'et_pb_events';
		$this->post_types = array( EXTRA_LAYOUT_POST_TYPE );

		$this->whitelisted_fields = array();
		foreach ( $this->get_fields() as $name => $field ) {
			$this->whitelisted_fields[] = $name;
		}

		$this->main_css_element = '%%order_class%%';

		$this->advanced_options = array(
			'fonts'                 => array(
				'header'     => $this->set_frequent_advanced_options( 'header' ),
				'subheader'  => $this->set_frequent_advanced_options( 'subheader' ),
				'main_title' => array(
					'label'          => esc_html__( 'Main Title', 'et_builder' ),
					'css'            => array(
						'main'      => "{$this->main_css_element} .main-post .hentry h2",
						'color'     => "{$this->main_css_element} .main-post .hentry h2 a",
						'important' => 'all',
					),
					'line_height'    => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 3,
							'step' => 0.1,
						),
					),
					'letter_spacing' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 30,
							'step' => 0.1,
						),
					),
				),
				'main_meta'  => array(
					'label' => esc_html__( 'Main Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .main-post .hentry .post-meta, {$this->main_css_element} .main-post .hentry .post-meta .comment-bubble:before, {$this->main_css_element} .main-post .hentry .post-meta .rating-star:before",
					),
				),
				'main_body'  => array(
					'label'       => esc_html__( 'Main Body', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .main-post .hentry .excerpt",
					),
					'line_height' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 3,
							'step' => 0.1,
						),
					),
				),
				'list_title' => array(
					'label'       => esc_html__( 'List Title', 'et_builder' ),
					'css'         => array(
						'main'      => "{$this->main_css_element} .posts-list .hentry h3",
						'color'     => "{$this->main_css_element} .posts-list .hentry h3 a",
						'important' => 'all',
					),
					'line_height' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 3,
							'step' => 0.1,
						),
					),
				),
				'list_meta'  => array(
					'label' => esc_html__( 'List Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .posts-list .hentry .post-meta, {$this->main_css_element} .posts-list .hentry .post-meta .comment-bubble:before, {$this->main_css_element} .posts-list .hentry .post-meta .rating-star:before",
					),
				),
			),
			'background'            => array(
				'css'      => array(
					'main' => "{$this->main_css_element}, {$this->main_css_element} .module-head",
				),
				'settings' => array(
					'color' => 'alpha',
				),
			),
			'border'                => array(
				'css' => array(
					'main'      => "{$this->main_css_element}",
					'important' => 'all',
				),
			),
			'custom_margin_padding' => array(),
		);

		$this->custom_css_options = array(
			'head'                   => array(
				'label'    => esc_html__( 'Module Head', 'et_builder' ),
				'selector' => '.module-head',
			),
			'header'                 => array(
				'label'    => esc_html__( 'Module Header', 'et_builder' ),
				'selector' => '.module-head h1',
			),
			'subheader'              => array(
				'label'    => esc_html__( 'Module Subheader', 'et_builder' ),
				'selector' => '.module-head .module-filter',
			),
			'main_post'              => array(
				'label'    => esc_html__( 'Main Post Area', 'et_builder' ),
				'selector' => '.main-post',
			),
			'main_post_hentry'       => array(
				'label'    => esc_html__( 'Main Post Entry', 'et_builder' ),
				'selector' => '.main-post .hentry',
			),
			'main_post_title'        => array(
				'label'    => esc_html__( 'Main Post Title', 'et_builder' ),
				'selector' => '.main-post .hentry h2 a',
			),
			'main_post_meta'         => array(
				'label'    => esc_html__( 'Main Post Meta', 'et_builder' ),
				'selector' => '.main-post .hentry .post-meta',
			),
			'main_post_overlay'      => array(
				'label'    => esc_html__( 'Post Overlay', 'et_builder' ),
				'selector' => '.main-post .hentry .et_pb_extra_overlay',
			),
			'main_post_overlay_icon' => array(
				'label'    => esc_html__( 'Post Overlay Icon', 'et_builder' ),
				'selector' => '.main-post .hentry .et_pb_extra_overlay:before',
			),
			'main_post_meta_icon'    => array(
				'label'    => esc_html__( 'Main Post Meta Icons (Rating &amp; Comment)', 'et_builder' ),
				'selector' => '.main-post .hentry .post-meta .post-meta-icon:before',
			),
			'main_post_excerpt'      => array(
				'label'    => esc_html__( 'Main Post Excerpt', 'et_builder' ),
				'selector' => '.main-post .hentry .excerpt',
			),
			'main_post_overlay'      => array(
				'label'    => esc_html__( 'Main Post Overlay', 'et_builder' ),
				'selector' => '.main-post .hentry .et_pb_extra_overlay',
			),
			'main_post_overlay_icon' => array(
				'label'    => esc_html__( 'Main Post Overlay Icon', 'et_builder' ),
				'selector' => '.main-post .hentry .et_pb_extra_overlay:before',
			),
			'posts_list'             => array(
				'label'    => esc_html__( 'Posts List Area', 'et_builder' ),
				'selector' => '.posts-list',
			),
			'posts_list_hentry'      => array(
				'label'    => esc_html__( 'Posts List Entry', 'et_builder' ),
				'selector' => '.posts-list li',
			),
			'posts_list_title'       => array(
				'label'    => esc_html__( 'Posts List Title', 'et_builder' ),
				'selector' => '.posts-list li h3 a',
			),
			'posts_list_meta'        => array(
				'label'    => esc_html__( 'Posts List Meta', 'et_builder' ),
				'selector' => '.posts-list li .post-meta',
			),
			'posts_list_meta_icon'   => array(
				'label'    => esc_html__( 'Posts List Meta Icon', 'et_builder' ),
				'selector' => '.posts-list li .post-meta .post-meta-icon:before',
			),
			'posts_list_thumbnail'   => array(
				'label'    => esc_html__( 'Posts List Thumbnail', 'et_builder' ),
				'selector' => '.posts-list .post-thumbnail img',
			),
		);
	}

	function get_fields() {
		$fields = array(
			'category_id'                 => array(
				'label'           => esc_html__( 'Categories', 'extra' ),
				'type'            => 'custom',
				'description'     => esc_html__( 'Choose categories.', 'extra' ),
				'renderer'        => array(
					$this,
					'category_field_renderer',
				),
				'priority'        => 1,
				'option_category' => 'configuration',
			),
			'display_featured_posts_only' => array(
				'label'           => esc_html__( 'Display Featured Posts Only', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'off' => esc_html__( 'No', 'extra' ),
					'on'  => esc_html__( 'Yes', 'extra' ),
				),
				'description'     => esc_html__( 'Only display featured posts.', 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'ignore_displayed_posts' => array(
				'label'           => esc_html__( 'Ignore Displayed Posts', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'off' => esc_html__( 'No', 'extra' ),
					'on'  => esc_html__( 'Yes', 'extra' ),
				),
				'description'     => esc_html__( 'Do not display posts that have been displayed on previous modules.', 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'heading_style'               => array(
				'label'           => esc_html__( 'Heading Style', 'extra' ),
				'type'            => 'select',
				'options'         => array(
					'category' => esc_html__( 'Primary Heading: Category Name, Sub Heading: Filter', 'extra' ),
					'filter'   => esc_html__( 'Primary Heading: Filter, Sub Heading: Category Name', 'extra' ),
					'custom'   => esc_html__( 'Custom Title', 'extra' ),
				),
				'description'     => esc_html__( 'Choose a heading style.', 'extra' ),
				'affects'         => array(
					'#et_pb_heading_primary',
					'#et_pb_heading_sub',
				),
				'option_category' => 'configuration',
			),
			'heading_primary'             => array(
				'label'           => esc_html__( 'Primary Heading', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'The primary heading.', 'extra' ),
				'depends_show_if' => 'custom',
				'option_category' => 'configuration',
			),
			'heading_sub'                 => array(
				'label'           => esc_html__( 'Sub Heading', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'The sub heading.', 'extra' ),
				'depends_show_if' => 'custom',
				'option_category' => 'configuration',
			),
			'posts_per_page'              => array(
				'label'           => esc_html__( 'Posts Limit', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'The number of posts shown.', 'extra' ),
				'priority'        => 3,
				'option_category' => 'configuration',
			),
			// 'orderby'                     => array(
			// 	'label'           => esc_html__( 'Sort Method', 'extra' ),
			// 	'type'            => 'select',
			// 	'options'         => array(
			// 		'date'          => esc_html__( 'Most Recent', 'extra' ),
			// 		'comment_count' => esc_html__( 'Most Popular', 'extra' ),
			// 		'rating'        => esc_html__( 'Highest Rated', 'extra' ),
			// 	),
			// 	'description'     => esc_html__( 'Choose a sort method.', 'extra' ),
			// 	'option_category' => 'configuration',
			// ),
			// 'order'                       => array(
			// 	'label'           => esc_html__( 'Sort Order', 'extra' ),
			// 	'type'            => 'select',
			// 	'options'         => array(
			// 		'desc' => esc_html__( 'Descending', 'extra' ),
			// 		'asc'  => esc_html__( 'Ascending', 'extra' ),
			// 	),
			// 	'description'     => esc_html__( 'Choose a sort order.', 'extra' ),
			// 	'option_category' => 'configuration',
			// ),
			'show_thumbnails'             => array(
				'label'           => esc_html__( 'Show Featured Image', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's featured image on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_author'                 => array(
				'label'           => esc_html__( 'Show Author', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's author on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_categories'             => array(
				'label'           => esc_html__( 'Show Categories', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's categories on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_comments'               => array(
				'label'           => esc_html__( 'Show Comments', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's ccomments on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_rating'                 => array(
				'label'           => esc_html__( 'Show Rating', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's rating on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_date'                   => array(
				'label'           => esc_html__( 'Show Date', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'affects'         => array( '#et_pb_date_format' ),
				'description'     => esc_html__( "Turn the dispay of each post's date on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'date_format'                 => array(
				'label'               => esc_html__( 'Date Format', 'extra' ),
				'type'                => 'input',
				'depends_show_if_not' => "off",
				'description'         => esc_html__( 'The format for the date display in PHP date() format', 'extra' ),
				'option_category'     => 'configuration',
			),
			'hover_overlay_color'         => array(
				'label'        => esc_html__( 'Hover Overlay Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 26,
			),
			'hover_overlay_icon_color'    => array(
				'label'        => esc_html__( 'Hover Overlay Icon Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 26,
			),
			'hover_overlay_icon'          => array(
				'label'               => esc_html__( 'Hover Overlay Icon Picker', 'et_builder' ),
				'type'                => 'text',
				'option_category'     => 'configuration',
				'class'               => array( 'et-pb-font-icon' ),
				'renderer'            => 'et_pb_get_font_icon_list',
				'renderer_with_field' => true,
				'tab_slug'            => 'advanced',
				'priority'            => 26,
			),
			'admin_label'                 => array(
				'label'       => esc_html__( 'Admin Label', 'extra' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'extra' ),
			),
			'module_id'                   => array(
				'label'           => esc_html__( 'CSS ID', 'extra' ),
				'type'            => 'text',
				'description'     => esc_html__( 'Enter an optional CSS ID to be used for this module. An ID can be used to create custom CSS styling, or to create links to particular sections of your page.', 'extra' ),
				'option_category' => 'configuration',
			),
			'module_class'                => array(
				'label'           => esc_html__( 'CSS Class', 'extra' ),
				'type'            => 'text',
				'description'     => esc_html__( 'Enter optional CSS classes to be used for this module. A CSS class can be used to create custom CSS styling. You can add multiple classes, separated with a space.', 'extra' ),
				'option_category' => 'configuration',
			),
			'max_width'                   => array(
				'label'           => esc_html__( 'Max Width', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'layout',
				'tab_slug'        => 'advanced',
				'validate_unit'   => true,
			),
		);

		$advanced_design_fields = array(
			'post_format_icon_bg_color' => array(
				'label'        => esc_html__( 'Post Format Icon Background Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 25,
			),
			'remove_drop_shadow'        => array(
				'label'           => esc_html__( 'Remove Drop Shadow', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'layout',
				'options'         => array(
					'off' => esc_html__( 'No', 'et_builder' ),
					'on'  => esc_html__( 'Yes', 'et_builder' ),
				),
				'tab_slug'        => 'advanced',
				'priority'        => 26,
			),
			'border_radius'             => array(
				'label'           => esc_html__( 'Border Radius', 'et_builder' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'tab_slug'        => 'advanced',
				'priority'        => 27,
				'range_settings'  => array(
					'min'  => '0',
					'max'  => '200',
					'step' => '1',
				),
			),
		);

		return array_merge( $fields, $advanced_design_fields );
	}

	function shortcode_callback( $atts, $content = null, $function_name ) {
		global $extra_displayed_post_ids;

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => isset( $this->shortcode_atts['posts_per_page'] ) && is_numeric( $this->shortcode_atts['posts_per_page'] ) ? $this->shortcode_atts['posts_per_page'] : 5,
			// 'order'          => $this->shortcode_atts['order'],
			// 'orderby'        => $this->shortcode_atts['orderby'],
			'ignore_displayed_posts' => isset( $this->shortcode_atts['ignore_displayed_posts'] ) ? $this->shortcode_atts['ignore_displayed_posts'] : false,
		);

		// if ( 'rating' == $this->shortcode_atts['orderby'] ) {
		// 	$args['orderby'] = 'meta_value_num';
		// 	$args['meta_key'] = '_extra_rating_average';
		// }

		if ( ! $extra_displayed_post_ids ) {
			$extra_displayed_post_ids = array();
		}

		if ( $args['ignore_displayed_posts'] ) {
			$args['post__not_in'] = $extra_displayed_post_ids;
		}

		$args = $this->_pre_wp_query( $args );

		// need to hook into pre_get_posts to set is_home = true, then unhook afterwards
		add_action( 'pre_get_posts', array( $this, 'make_is_home' ) );

		$this->shortcode_atts['module_posts'] = new Event_Query( $args );

		// $post_titles = wp_list_pluck( $this->shortcode_atts['module_posts']->posts, 'post_title' );
		// Kidzou_Utils::log($post_titles,true);

		// unhook afterwards
		remove_action( 'pre_get_posts', array( $this, 'make_is_home' ) );

		$posts_per_page = $this->shortcode_atts['module_posts']->get( 'posts_per_page' );

		// only slice if there is a limit that where trying to enforce respect upon and if it's disrespecting the limit
		if ( $posts_per_page > 0 && $this->shortcode_atts['module_posts']->post_count > $posts_per_page ) {
			$sticky_posts = get_option( 'sticky_posts' );
			if ( is_array( $sticky_posts ) && !empty( $sticky_posts ) ) {
				// make wp_query respect posts_per_page even when sticky posts are involved
				$module_posts = $this->shortcode_atts['module_posts'];
				$module_posts->posts = array_slice( $module_posts->posts, 0, $posts_per_page );
				$module_posts->post_count = $posts_per_page;
				$this->shortcode_atts['module_posts'] = $module_posts;
			}
		}

		if ( ! empty( $this->shortcode_atts['terms_names'] ) ) {
			$category_name = $this->shortcode_atts['terms_names'];
		} else if ( ! empty( $this->shortcode_atts['term_name'] ) ) {
			$category_name = $this->shortcode_atts['term_name'];
		} else {
			$category_name = esc_html__( 'All', 'extra' );
		}

		$this->shortcode_atts['is_all_categories'] = (bool) empty( $this->shortcode_atts['term_name'] );

		// switch ( $this->shortcode_atts['orderby'] ) {
		// 	case 'comment_count':
		// 		$filter_title = esc_html__( 'Popular', 'extra' );
		// 		break;
		// 	case 'rating':
		// 		$filter_title = esc_html__( 'Top Rated', 'extra' );
		// 		break;
		// 	case 'date':
		// 	default:
		// 		$filter_title = esc_html__( 'Latest', 'extra' );
		// 		break;
		// }

		if ( !empty( $this->shortcode_atts['heading_style'] ) ) {
			switch ( $this->shortcode_atts['heading_style'] ) {
				case 'filter':
					$this->shortcode_atts['title'] = $filter_title;
					$this->shortcode_atts['sub_title'] = $category_name;
					break;
				case 'custom':
					$this->shortcode_atts['title'] = !empty( $this->shortcode_atts['heading_primary'] ) ? esc_html( $this->shortcode_atts['heading_primary'] ) : '';
					$this->shortcode_atts['sub_title'] = !empty( $this->shortcode_atts['heading_sub'] ) ? esc_html( $this->shortcode_atts['heading_sub'] ) : '';
					break;
				case 'category':
				default:
					$this->shortcode_atts['title'] = $category_name;
					$this->shortcode_atts['sub_title'] = $filter_title;
					break;
			}
		}

		if ( !empty( $this->shortcode_atts['term_color'] ) ) {
			$this->shortcode_atts['border_top_color'] = $this->shortcode_atts['term_color'];
			$this->shortcode_atts['module_title_color'] = $this->shortcode_atts['term_color'];
		} else {

			$color = et_builder_accent_color();
			$module_posts = $this->shortcode_atts['module_posts'];

			if ( isset( $module_posts->posts[0] ) ) {
				$featured_post = $module_posts->posts[0];
				$categories = wp_get_post_categories( $featured_post->ID );

				if ( !empty( $categories ) ) {
					$first_category_id = $categories[0];
					if ( function_exists( 'et_get_childmost_taxonomy_meta' ) ) {
						$color = et_get_childmost_taxonomy_meta( $first_category_id, 'color', true, et_builder_accent_color() );
					}
				}
			}

			$this->shortcode_atts['term_color'] = $color;
			$this->shortcode_atts['border_top_color'] = $color;
			$this->shortcode_atts['module_title_color'] = $color;
		}

		if ( isset( $this->shortcode_atts['module_class'] ) ) {
			$this->shortcode_atts['module_class'] = ET_Builder_Element::add_module_order_class( $this->shortcode_atts['module_class'], $this->slug );
		}

		// Adding styling classes to module
		if ( !empty( $this->shortcode_atts['remove_drop_shadow'] ) && 'on' === $this->shortcode_atts['remove_drop_shadow'] ) {
			$this->shortcode_atts['module_class'] = $this->shortcode_atts['module_class'] . ' et_pb_no_drop_shadow';
		}

		// Print styling for general options
		if ( isset( $this->shortcode_atts['border_radius'] ) && '' !== $this->shortcode_atts['border_radius'] ) {
			ET_Builder_Module::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.et_pb_extra_module',
				'declaration' => sprintf(
					'-moz-border-radius: %1$s;
					-webkit-border-radius: %1$s;
					border-radius: %1$s;',
					esc_html( $this->shortcode_atts['border_radius'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['max_width'] ) && '' !== $this->shortcode_atts['max_width'] ) {
			ET_Builder_Module::set_style( $this->slug, array(
				'selector'    => '%%order_class%%',
				'declaration' => sprintf(
					'max-width: %1$s;',
					esc_html( et_builder_process_range_value( $this->shortcode_atts['max_width'] ) )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['post_format_icon_bg_color'] ) && '' !== $this->shortcode_atts['post_format_icon_bg_color'] ) {
			ET_Builder_Module::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .post-thumbnail img',
				'declaration' => sprintf(
					'background-color: %1$s !important;',
					esc_html( $this->shortcode_atts['post_format_icon_bg_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['hover_overlay_color'] ) && '' !== $this->shortcode_atts['hover_overlay_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .et_pb_extra_overlay',
				'declaration' => sprintf(
					'background-color: %1$s;
					border-color: %1$s;',
					esc_html( $this->shortcode_atts['hover_overlay_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['hover_overlay_icon_color'] ) && '' !== $this->shortcode_atts['hover_overlay_icon_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .et_pb_extra_overlay:before',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['hover_overlay_icon_color'] )
				),
			) );
		}

		// Overwrite border_color_top attribute if border color is defined by advanced design settings
		if ( isset( $this->shortcode_atts['border_color'] ) && isset( $this->shortcode_atts['use_border_color'] ) && 'on' === $this->shortcode_atts['use_border_color'] ) {
			$this->shortcode_atts['border_top_color'] = $this->shortcode_atts['border_color'];
		}

		if ( is_customize_preview() && $this->shortcode_atts['term_color'] === extra_global_accent_color() ) {
			$this->shortcode_atts['module_class'] = $this->shortcode_atts['module_class'] . ' no-term-color-module';
		}

		if ( isset( $this->shortcode_atts['module_posts']->found_posts ) && 0 < $this->shortcode_atts['module_posts']->found_posts ) {
			$post_ids = wp_list_pluck( $this->shortcode_atts['module_posts']->posts, 'ID' );

			$extra_displayed_post_ids = array_unique( array_merge( $extra_displayed_post_ids, $post_ids ) );
		}
	}

	function _pre_wp_query( $args ) {
		global $wp_query;

		$this->_process_shortcode_atts_category_id();

		if ( !empty( $this->shortcode_atts['category_id'] ) ) {
			$categories = array_map( 'absint', explode( ',', $this->shortcode_atts['category_id'] ) );

			$args['ignore_sticky_posts'] = 1;

			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $categories,
					'operator' => 'IN',
				),
			);

			if ( count( $categories ) > 1 ) {
				$terms_names = array();
				foreach ( $categories as $category_id ) {
					$terms_names[] = get_term( $category_id, 'category' )->name;
				}
				$terms_names = implode( ', ', $terms_names );
				$this->shortcode_atts['terms_names'] = $terms_names;
			}

			$term = get_term( $categories[0], 'category' );
			if ( !empty( $term ) ) {
				$this->shortcode_atts['term_name'] = $term->name;
				$this->shortcode_atts['term_color'] = extra_get_category_color( $term->term_id );
			} else {
				unset( $args['tax_query'] );
			}
		}

		if ( isset( $wp_query->tax_query->queries ) && $this->shortcode_atts['use_tax_query'] ) {
			wp_localize_script( 'extra-scripts', 'EXTRA_TAX_QUERY', $wp_query->tax_query->queries );

			$taxonomies = $wp_query->tax_query->queries;

			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $taxonomy['taxonomy'] ) && 'category' === $taxonomy['taxonomy'] && ! empty( $this->shortcode_atts['category_id'] ) ) {
					continue;
				}

				$args['tax_query'][] = $taxonomy;
			}

			if ( isset( $args['tax_query'] ) && 1 < count( $args['tax_query'] ) ) {
				$args['tax_query']['relation'] = 'AND';
			}

			if ( ! is_home() ) {
				$args['ignore_sticky_posts'] = 1;
			}
		}

		if ( $this->shortcode_atts['display_featured_posts_only'] ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_extra_featured_post',
					'value' => '1',
				),
			);
		}

		return $args;
	}

}
new ET_Builder_Module_Events;

class ET_Builder_Module_Featured_Events_Slider extends ET_Builder_Module_Featured_Posts_Slider {

	function init() {
		$this->template_name = 'module-featured-events-slider';
		$this->name = esc_html__( 'Featured Events Slider', 'extra' );
		$this->slug = 'et_pb_featured_events_slider';
		$this->post_types = array( EXTRA_LAYOUT_POST_TYPE );

		$this->whitelisted_fields = array();
		foreach ( $this->get_fields() as $name => $field ) {
			$this->whitelisted_fields[] = $name;
		}

		$this->main_css_element = '%%order_class%%';

		$this->advanced_options = array(
			'fonts'                 => array(
				'title' => array(
					'label'          => esc_html__( 'Title', 'et_builder' ),
					'css'            => array(
						'main'      => "{$this->main_css_element} .hentry h3",
						'color'     => "{$this->main_css_element} .hentry h3 a",
						'important' => 'all',
					),
					'letter_spacing' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 30,
							'step' => 0.1,
						),
					),
				),
				'meta'  => array(
					'label' => esc_html__( 'Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .post-meta, {$this->main_css_element} .hentry .post-meta .comment-bubble:before, {$this->main_css_element} .hentry .post-meta .rating-star:before",
					),
				),
			),
			'custom_margin_padding' => array(),
		);

		$this->custom_css_options = array(
			'post_hentry'            => array(
				'label'    => esc_html__( 'Post Entry', 'et_builder' ),
				'selector' => '.hentry',
			),
			'post_caption'           => array(
				'label'    => esc_html__( 'Post Caption', 'et_builder' ),
				'selector' => '.hentry .post-content-box',
			),
			'post_title'             => array(
				'label'    => esc_html__( 'Post Title', 'et_builder' ),
				'selector' => '.hentry h3 a',
			),
			'post_meta'              => array(
				'label'    => esc_html__( 'Post Meta', 'et_builder' ),
				'selector' => '.hentry .post-meta',
			),
			'post_meta_icon'         => array(
				'label'    => esc_html__( 'Post Meta Icons (Rating &amp; Comment)', 'et_builder' ),
				'selector' => '.hentry .post-meta .post-meta-icon:before',
			),
			'pagination_item'        => array(
				'label'    => esc_html__( 'Pagination Item', 'et_builder' ),
				'selector' => '.slick-dots li button',
			),
			'pagination_item_active' => array(
				'label'    => esc_html__( 'Pagination Item Active', 'et_builder' ),
				'selector' => '.slick-dots li.slick-active button',
			),
			'nav'                    => array(
				'label'    => esc_html__( 'Nav', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a',
			),
			'nav_hover'              => array(
				'label'    => esc_html__( 'Nav Hover', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:hover',
			),
			'nav_icon'               => array(
				'label'    => esc_html__( 'Nav Icon', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:before',
			),
			'nav_icon_hover'         => array(
				'label'    => esc_html__( 'Nav Icon Hover', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:hover:before',
			),
		);
	}

}
new ET_Builder_Module_Featured_Events_Slider;

class ET_Builder_Module_Events_Blog_Feed extends ET_Builder_Module_Events {

	function init() {
		$this->template_name = 'module-events-blog-feed';
		$this->name = esc_html__( 'Events Feed Standard', 'extra' );
		$this->slug = 'et_pb_events_blog_feed_standard';
		$this->post_types = array( EXTRA_LAYOUT_POST_TYPE );

		$this->whitelisted_fields = array();
		foreach ( $this->get_fields() as $name => $field ) {
			$this->whitelisted_fields[] = $name;
		}

		$this->main_css_element = '%%order_class%%';

		$this->advanced_options = array(
			'fonts'                 => array(
				'header' => $this->set_frequent_advanced_options( 'header' ),
				'title'  => array(
					'label'          => esc_html__( 'Title', 'et_builder' ),
					'css'            => array(
						'main'      => "{$this->main_css_element} .hentry h2",
						'color'     => "{$this->main_css_element} .hentry h2 a",
						'important' => 'all',
					),
					'letter_spacing' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 30,
							'step' => 0.1,
						),
					),
				),
				'meta'   => array(
					'label' => esc_html__( 'Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .post-meta, {$this->main_css_element} .hentry .post-meta .comment-bubble:before, {$this->main_css_element} .hentry .post-meta .rating-star:before",
					),
				),
				'body'   => array(
					'label' => esc_html__( 'Body', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .excerpt p",
					),
				),
			),
			'button'                => array(
				'read_more' => array(
					'label' => esc_html__( 'Read More Button', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .read-more-button",
					),
				),
			),
			'background'            => array(
				'css'      => array(
					'main' => "{$this->main_css_element}, {$this->main_css_element} .module-head",
				),
				'settings' => array(
					'color' => 'alpha',
				),
			),
			'custom_margin_padding' => array(),
		);

		$this->custom_css_options = array(
			'head'                               => array(
				'label'    => esc_html__( 'Module Head', 'et_builder' ),
				'selector' => '.module-head',
			),
			'header'                             => array(
				'label'    => esc_html__( 'Module Header', 'et_builder' ),
				'selector' => '.module-head h1',
			),
			'post_hentry'                        => array(
				'label'    => esc_html__( 'Post Entry', 'et_builder' ),
				'selector' => '.hentry',
			),
			'post_title'                         => array(
				'label'    => esc_html__( 'Post Title', 'et_builder' ),
				'selector' => '.hentry h2 a',
			),
			'post_meta'                          => array(
				'label'    => esc_html__( 'Post Meta', 'et_builder' ),
				'selector' => '.hentry .post-meta',
			),
			'post_meta_icon'                     => array(
				'label'    => esc_html__( 'Post Meta Icons (Rating &amp; Comment)', 'et_builder' ),
				'selector' => '.hentry .post-meta .post-meta-icon:before',
			),
			'post_excerpt'                       => array(
				'label'    => esc_html__( 'Post Excerpt', 'et_builder' ),
				'selector' => '.hentry .excerpt',
			),
			'post_read_more'                     => array(
				'label'    => esc_html__( 'Post Read More', 'et_builder' ),
				'selector' => '.hentry .read-more-button',
			),
			'post_read_more_icon'                => array(
				'label'    => esc_html__( 'Post Read More Icon', 'et_builder' ),
				'selector' => '.hentry .read-more-button:after',
			),
			'post_featured_image'                => array(
				'label'    => esc_html__( 'Post Featured Image', 'et_builder' ),
				'selector' => '.hentry .featured-image img',
			),
			'post_overlay'                       => array(
				'label'    => esc_html__( 'Post Overlay', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay',
			),
			'post_overlay_icon'                  => array(
				'label'    => esc_html__( 'Post Overlay Icon', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay:before',
			),
			'post_review_score_bar'              => array(
				'label'    => esc_html__( 'Post Review Score Bar', 'et_builder' ),
				'selector' => '.hentry .score-bar',
			),
			'post_format_gallery_nav'            => array(
				'label'    => esc_html__( 'Post Format Gallery Nav', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a',
			),
			'post_format_gallery_nav_icon'       => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Icon', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:before',
			),
			'post_format_gallery_nav_hover'      => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Hover', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:hover',
			),
			'post_format_gallery_nav_hover_icon' => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Icon Hover', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:hover:before',
			),
			'post_format_audio_wrapper'          => array(
				'label'    => esc_html__( 'Post Format Audio Wrapper', 'et_builder' ),
				'selector' => '.hentry .audio-wrapper',
			),
			'post_format_audio_player'           => array(
				'label'    => esc_html__( 'Post Format Audio Player', 'et_builder' ),
				'selector' => '.hentry .mejs-container',
			),
			'post_format_link_background'        => array(
				'label'    => esc_html__( 'Post Format Link Background', 'et_builder' ),
				'selector' => '.hentry .link-format',
			),
			'post_format_quote_background'       => array(
				'label'    => esc_html__( 'Post Format Quote Background', 'et_builder' ),
				'selector' => '.hentry .quote-format',
			),
			'pagination_background'              => array(
				'label'    => esc_html__( 'Pagination Background', 'et_builder' ),
				'selector' => '.pagination',
			),
			'pagination_item'                    => array(
				'label'    => esc_html__( 'Pagination Item', 'et_builder' ),
				'selector' => '.pagination li',
			),
			'pagination_item_active'             => array(
				'label'    => esc_html__( 'Pagination Item Active', 'et_builder' ),
				'selector' => '.pagination li.active',
			),
		);
	}


	function set_additional_fields() {
		return array(
			'date_format' => array(
				'M j, Y',
				'add_default_setting',
			),
		);
	}

	function get_fields() {
		$fields = parent::get_fields();

		$new_fields = array(
			'feed_title'                 => array(
				'label'           => esc_html__( 'Blog Feed Title', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'This is an optional title to display for this module.', 'extra' ),
				'priority'        => 2,
				'option_category' => 'configuration',
			),
			'posts_per_page'             => array(
				'label'           => esc_html__( 'Posts Per Page', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'The number of posts shown per page.', 'extra' ),
				'priority'        => 3,
				'option_category' => 'configuration',
			),
			'show_pagination'            => array(
				'label'           => esc_html__( 'Show Pagination', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( 'Turn pagination on or off.', 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_author'                => array(
				'label'           => esc_html__( 'Show Author', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's author on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_categories'            => array(
				'label'           => esc_html__( 'Show Categories', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's categories on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'show_featured_image'        => array(
				'label'           => esc_html__( 'Show Featured Image', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'description'     => esc_html__( "Turn the display of each post's featured image on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'content_length'             => array(
				'label'           => esc_html__( 'Content', 'extra' ),
				'type'            => 'select',
				'options'         => array(
					'excerpt' => esc_html__( 'Show Excerpt', 'extra' ),
					'full'    => esc_html__( "Show Full Content", 'extra' ),
				),
				'affects'         => array(
					'#et_pb_show_more',
				),
				'description'     => esc_html__( "Display the post's exceprt or full content. If full content, then it will truncate to the more tag if used.", 'extra' ),
				'option_category' => 'configuration',
			),
			'show_more'                  => array(
				'label'           => esc_html__( 'Show Read More Button', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'depends_show_if' => 'excerpt',
				'description'     => esc_html__( 'Here you can define whether to show "read more" link after the excerpts or not.', 'extra' ),
				'option_category' => 'configuration',
			),
			'show_date'                  => array(
				'label'           => esc_html__( 'Show Date', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'extra' ),
					'off' => esc_html__( 'No', 'extra' ),
				),
				'affects'         => array( '#et_pb_date_format' ),
				'description'     => esc_html__( "Turn the dispay of each post's date on or off.", 'extra' ),
				'priority'        => 5,
				'option_category' => 'configuration',
			),
			'date_format'                => array(
				'label'               => esc_html__( 'Date Format', 'extra' ),
				'type'                => 'input',
				'depends_show_if_not' => "off",
				'description'         => esc_html__( 'The format for the date display in PHP date() format', 'extra' ),
				'option_category'     => 'configuration',
			),
			'pagination_color'           => array(
				'label'        => esc_html__( 'Pagination Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 28,
			),
			'pagination_bg_color'        => array(
				'label'        => esc_html__( 'Pagination Background Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 29,
			),
			'pagination_active_color'    => array(
				'label'        => esc_html__( 'Pagination Active Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 30,
			),
			'pagination_active_bg_color' => array(
				'label'        => esc_html__( 'Pagination Active Background Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
				'priority'     => 31,
			),
		);

		// unset parent version of this field in favor of local in $new_fields
		unset( $fields['posts_per_page'] );
		unset( $fields['show_thumbnails'] );
		unset( $fields['post_format_icon_bg_color'] );
		unset( $fields['heading_style'] );
		unset( $fields['heading_primary'] );
		unset( $fields['heading_sub'] );

		$fields = array_merge( $new_fields, $fields );

		return $fields;
	}

	function enqueue_scripts() {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
		et_extra_enqueue_google_maps_api();
	}

	function shortcode_atts() {
		global $et_column_type;

		parent::shortcode_atts();

		$this->enqueue_scripts();

		$this->shortcode_atts['_et_column_type'] = $et_column_type;
		$this->shortcode_atts['blog_feed_module_type'] = 'standard';

		if ( '' === $this->shortcode_atts['posts_per_page'] ) {
			$this->shortcode_atts['posts_per_page'] = 5;
		}
	}

	function pre_shortcode_content() {
		if ( isset( $this->shortcode_atts['read_more_background'] ) && '' !== $this->shortcode_atts['read_more_background'] ) {
			ET_Builder_Module::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .read-more-button',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['read_more_background'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_color'] ) && '' !== $this->shortcode_atts['pagination_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li, %%order_class%%.paginated .pagination li a, %%order_class%%.paginated .pagination li a:before',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_bg_color'] ) && '' !== $this->shortcode_atts['pagination_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li, %%order_class%%.paginated .pagination li a',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_bg_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_active_color'] ) && '' !== $this->shortcode_atts['pagination_active_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li.active a',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_active_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_active_bg_color'] ) && '' !== $this->shortcode_atts['pagination_active_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li.active a',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_active_bg_color'] )
				),
			) );
		}
	}

	function _pre_wp_query( $args ) {
		$args = parent::_pre_wp_query( $args );

		$args['posts_per_page'] = is_numeric( $this->shortcode_atts['posts_per_page'] ) ? $this->shortcode_atts['posts_per_page'] : 5;

		return $args;
	}

}
new ET_Builder_Module_Events_Blog_Feed;

class ET_Builder_Module_Events_Blog_Feed_Masonry extends ET_Builder_Module_Events_Blog_Feed {

	function init() {
		$this->template_name = 'module-events-blog-feed';
		$this->name = esc_html__( 'Events Feed Masonry', 'extra' );
		$this->slug = 'et_pb_events_blog_feed_masonry';
		$this->post_types = array( EXTRA_LAYOUT_POST_TYPE );

		$this->whitelisted_fields = array();
		foreach ( $this->get_fields() as $name => $field ) {
			$this->whitelisted_fields[] = $name;
		}

		$this->main_css_element = '%%order_class%%';

		$this->advanced_options = array(
			'fonts'                 => array(
				'title' => array(
					'label'          => esc_html__( 'Title', 'et_builder' ),
					'css'            => array(
						'main'      => "{$this->main_css_element} .hentry h2",
						'color'     => "{$this->main_css_element} .hentry h2 a",
						'important' => 'all',
					),
					'letter_spacing' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 30,
							'step' => 0.1,
						),
					),
				),
				'meta'  => array(
					'label' => esc_html__( 'Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .post-meta, {$this->main_css_element} .hentry .post-meta .comment-bubble:before, {$this->main_css_element} .hentry .post-meta .rating-star:before",
					),
				),
				'body'  => array(
					'label' => esc_html__( 'Body', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry p",
					),
				),
			),
			'border'                => array(
				'css' => array(
					'main' => ".posts-blog-feed-module.masonry{$this->main_css_element} .hentry",
				),
			),
			'button'                => array(
				'read_more' => array(
					'label' => esc_html__( 'Read More Button', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .hentry .read-more-button",
					),
				),
			),
			'custom_margin_padding' => array(),
		);

		$this->custom_css_options = array(
			'post_hentry'                        => array(
				'label'    => esc_html__( 'Post Entry', 'et_builder' ),
				'selector' => '.hentry',
			),
			'post_title'                         => array(
				'label'    => esc_html__( 'Post Title', 'et_builder' ),
				'selector' => '.hentry h2 a',
			),
			'post_meta'                          => array(
				'label'    => esc_html__( 'Post Meta', 'et_builder' ),
				'selector' => '.hentry .post-meta',
			),
			'post_meta_icon'                     => array(
				'label'    => esc_html__( 'Post Meta Icons (Rating &amp; Comment)', 'et_builder' ),
				'selector' => '.hentry .post-meta .post-meta-icon:before',
			),
			'post_excerpt'                       => array(
				'label'    => esc_html__( 'Post Excerpt', 'et_builder' ),
				'selector' => '.hentry .excerpt',
			),
			'post_read_more'                     => array(
				'label'    => esc_html__( 'Post Read More', 'et_builder' ),
				'selector' => '.hentry .read-more-button',
			),
			'post_read_more_icon'                => array(
				'label'    => esc_html__( 'Post Read More Icon', 'et_builder' ),
				'selector' => '.hentry .read-more-button:after',
			),
			'post_featured_image'                => array(
				'label'    => esc_html__( 'Post Featured Image', 'et_builder' ),
				'selector' => '.hentry .featured-image img',
			),
			'post_overlay'                       => array(
				'label'    => esc_html__( 'Post Overlay', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay',
			),
			'post_overlay_icon'                  => array(
				'label'    => esc_html__( 'Post Overlay Icon', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay:before',
			),
			'post_review_score_bar'              => array(
				'label'    => esc_html__( 'Post Review Score Bar', 'et_builder' ),
				'selector' => '.hentry .score-bar',
			),
			'post_format_gallery_nav'            => array(
				'label'    => esc_html__( 'Post Format Gallery Nav', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a',
			),
			'post_format_gallery_nav_icon'       => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Icon', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:before',
			),
			'post_format_gallery_nav_hover'      => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Hover', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:hover',
			),
			'post_format_gallery_nav_hover_icon' => array(
				'label'    => esc_html__( 'Post Format Gallery Nav Icon Hover', 'et_builder' ),
				'selector' => '.hentry .et-pb-slider-arrows a:hover:before',
			),
			'post_format_audio_wrapper'          => array(
				'label'    => esc_html__( 'Post Format Audio Wrapper', 'et_builder' ),
				'selector' => '.hentry .audio-wrapper',
			),
			'post_format_audio_player'           => array(
				'label'    => esc_html__( 'Post Format Audio Player', 'et_builder' ),
				'selector' => '.hentry .mejs-container',
			),
			'post_format_link_background'        => array(
				'label'    => esc_html__( 'Post Format Link Background', 'et_builder' ),
				'selector' => '.hentry .link-format',
			),
			'post_format_quote_background'       => array(
				'label'    => esc_html__( 'Post Format Quote Background', 'et_builder' ),
				'selector' => '.hentry .quote-format',
			),
			'pagination_item'                    => array(
				'label'    => esc_html__( 'Pagination Item', 'et_builder' ),
				'selector' => '.pagination li',
			),
			'pagination_item_active'             => array(
				'label'    => esc_html__( 'Pagination Item Active', 'et_builder' ),
				'selector' => '.pagination li.active',
			),
		);
	}

	function shortcode_atts() {
		global $et_column_type;

		parent::shortcode_atts();

		$this->enqueue_scripts();

		$this->shortcode_atts['_et_column_type'] = $et_column_type;
		$this->shortcode_atts['blog_feed_module_type'] = 'masonry';
	}

	function pre_shortcode_content() {
		wp_enqueue_script( 'salvattore' );

		if ( '' !== $this->shortcode_atts['border_radius'] ) {
			ET_Builder_Module::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.posts-blog-feed-module.masonry .hentry, %%order_class%%.posts-blog-feed-module.masonry .et-format-link .header div, %%order_class%%.posts-blog-feed-module.masonry .et-format-quote .header div',
				'declaration' => sprintf(
					'-moz-border-radius: %1$s;
					-webkit-border-radius: %1$s;
					border-radius: %1$s;',
					esc_html( $this->shortcode_atts['border_radius'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['post_bg_color'] ) && '' !== $this->shortcode_atts['post_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.masonry .hentry',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['post_bg_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_color'] ) && '' !== $this->shortcode_atts['pagination_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li, %%order_class%%.paginated .pagination li a, %%order_class%%.paginated .pagination li a:before',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_bg_color'] ) && '' !== $this->shortcode_atts['pagination_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li, %%order_class%%.paginated .pagination li a',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_bg_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_active_color'] ) && '' !== $this->shortcode_atts['pagination_active_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li.active a',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_active_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['pagination_active_bg_color'] ) && '' !== $this->shortcode_atts['pagination_active_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%%.paginated .pagination li.active a',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['pagination_active_bg_color'] )
				),
			) );
		}
	}

}
new ET_Builder_Module_Events_Blog_Feed_Masonry;

class ET_Builder_Module_Events_Carousel extends ET_Builder_Module_Events {

	function init() {
		$this->template_name = 'module-events-carousel';
		$this->name = esc_html__( 'Events Carousel', 'extra' );
		$this->slug = 'et_pb_events_carousel';
		$this->post_types = array( EXTRA_LAYOUT_POST_TYPE );

		$this->whitelisted_fields = array();
		foreach ( $this->get_fields() as $name => $field ) {
			$this->whitelisted_fields[] = $name;
		}

		$this->main_css_element = '%%order_class%%';

		$this->advanced_options = array(
			'fonts'                 => array(
				'header'    => $this->set_frequent_advanced_options( 'header' ),
				'subheader' => $this->set_frequent_advanced_options( 'subheader' ),
				'title'     => array(
					'label'          => esc_html__( 'Title', 'et_builder' ),
					'css'            => array(
						'main'      => "{$this->main_css_element} .hentry h3",
						'color'     => "{$this->main_css_element} .hentry h3 a",
						'important' => 'all',
					),
					'letter_spacing' => array(
						'range_settings' => array(
							'min'  => 0,
							'max'  => 30,
							'step' => 0.1,
						),
					),
				),
				'meta'      => array(
					'label' => esc_html__( 'Meta', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .post-meta",
					),
				),
			),
			'background'            => array(
				'css'      => array(
					'main' => "{$this->main_css_element}",
				),
				'settings' => array(
					'color' => 'alpha',
				),
			),
			'border'                => array(
				'css' => array(
					'main'      => "{$this->main_css_element}",
					'important' => 'all',
				),
			),
			'custom_margin_padding' => array(),
		);

		$this->custom_css_options = array(
			'head'              => array(
				'label'    => esc_html__( 'Module Head', 'et_builder' ),
				'selector' => '.module-head',
			),
			'header'            => array(
				'label'    => esc_html__( 'Module Header', 'et_builder' ),
				'selector' => '.module-head h1',
			),
			'subheader'         => array(
				'label'    => esc_html__( 'Module Subheader', 'et_builder' ),
				'selector' => '.module-head .module-filter',
			),
			'post_hentry'       => array(
				'label'    => esc_html__( 'Post Entry', 'et_builder' ),
				'selector' => '.hentry',
			),
			'post_title'        => array(
				'label'    => esc_html__( 'Post Title', 'et_builder' ),
				'selector' => '.hentry h3 a',
			),
			'post_meta'         => array(
				'label'    => esc_html__( 'Post Meta', 'et_builder' ),
				'selector' => '.hentry .post-meta',
			),
			'post_overlay'      => array(
				'label'    => esc_html__( 'Post Overlay', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay',
			),
			'post_overlay_icon' => array(
				'label'    => esc_html__( 'Post Overlay Icon', 'et_builder' ),
				'selector' => '.hentry .et_pb_extra_overlay:before',
			),
			'nav'               => array(
				'label'    => esc_html__( 'Nav', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a',
			),
			'nav_hover'         => array(
				'label'    => esc_html__( 'Nav Hover', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:hover',
			),
			'nav_icon'          => array(
				'label'    => esc_html__( 'Nav Icon', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:before',
			),
			'nav_icon_hover'    => array(
				'label'    => esc_html__( 'Nav Icon Hover', 'et_builder' ),
				'selector' => '.et-pb-slider-arrows a:hover:before',
			),
		);
	}

		function set_additional_fields() {
		return array(
			'enable_autoplay' => array(
				'off',
				'add_default_setting',
			),
			'autoplay_speed'  => array(
				'5',
				'add_default_setting',
			),
			'max_title_characters'  => array(
				40,
				'add_default_setting',
			),
		);
	}

	function get_fields() {
		$fields = parent::get_fields();

		$_fields = array();

		$new_fields = array(
			'enable_autoplay'    => array(
				'label'           => esc_html__( 'Enable Autoplay', 'extra' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'off' => esc_html__( 'No', 'extra' ),
					'on'  => esc_html__( 'Yes', 'extra' ),
				),
				'affects'         => array( '#et_pb_autoplay_speed' ),
				'description'     => esc_html__( 'Turn the autoplay feature on or off.', 'extra' ),
				'priority'        => 6,
				'option_category' => 'configuration',
			),
			'autoplay_speed'     => array(
				'label'           => esc_html__( 'Autoplay Speed', 'extra' ),
				'type'            => 'input',
				'depends_show_if' => "on",
				'description'     => esc_html__( 'The speed, in seconds, in which it will auto rotate to the next slide.', 'extra' ),
				'priority'        => 6,
				'option_category' => 'configuration',
			),
			'max_title_characters'=> array(
				'label'           => esc_html__( 'Max. Title Characters', 'extra' ),
				'type'            => 'input',
				'description'     => esc_html__( 'Length of the title need to be limited to prevent inappropriate caption height in narrow column', 'extra' ),
				'option_category' => 'configuration',
			),
			'nav_arrow_color'    => array(
				'label'        => esc_html__( 'Nav Arrow Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
			),
			'nav_arrow_bg_color' => array(
				'label'        => esc_html__( 'Nav Arrow Background Color', 'et_builder' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'tab_slug'     => 'advanced',
			),
		);

		foreach ( $fields as $field_key => $field ) {
			$_fields[ $field_key ] = $field;

			if ( 'order' == $field_key ) {
				$_fields[ 'enable_autoplay' ] = $new_fields['enable_autoplay'];
				$_fields[ 'autoplay_speed' ] = $new_fields['autoplay_speed'];
			}

			$_fields[ 'max_title_characters' ] = $new_fields['max_title_characters'];
			$_fields[ 'nav_arrow_color' ]    = $new_fields['nav_arrow_color'];
			$_fields[ 'nav_arrow_bg_color' ] = $new_fields['nav_arrow_bg_color'];
		}

		$fields = $_fields;

		$fields = $this->unset_fields( $fields );

		return $fields;
	}

	function pre_shortcode_content() {
		if ( isset( $this->shortcode_atts['nav_arrow_color'] ) && '' !== $this->shortcode_atts['nav_arrow_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .et-pb-slider-arrows a:before',
				'declaration' => sprintf(
					'color: %1$s;',
					esc_html( $this->shortcode_atts['nav_arrow_color'] )
				),
			) );
		}

		if ( isset( $this->shortcode_atts['nav_arrow_bg_color'] ) && '' !== $this->shortcode_atts['nav_arrow_bg_color'] ) {
			ET_Builder_Element::set_style( $this->slug, array(
				'selector'    => '%%order_class%% .et-pb-slider-arrows a',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $this->shortcode_atts['nav_arrow_bg_color'] )
				),
			) );
		}
	}

	function unset_fields( $fields ) {
		unset( $fields['show_thumbnails'] );
		unset( $fields['show_author'] );
		unset( $fields['show_categories'] );
		unset( $fields['show_rating'] );
		unset( $fields['show_comments'] );
		unset( $fields['post_format_icon_bg_color'] );
		return $fields;
	}

	function _pre_wp_query( $args ) {
		$args = parent::_pre_wp_query( $args );

		$thumbnail_meta_query = array(
			'key'     => '_thumbnail_id',
			'compare' => 'EXISTS',
		);

		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		$args['meta_query'][] = $thumbnail_meta_query;

		$args['posts_per_page'] = !empty( $this->shortcode_atts['posts_per_page'] ) && is_numeric( $this->shortcode_atts['posts_per_page'] ) ? $this->shortcode_atts['posts_per_page'] : -1;

		return $args;
	}

}
new ET_Builder_Module_Events_Carousel;




?>