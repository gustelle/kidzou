<?php

	function get_excerpt_by_id($post_id){

			$the_post = get_post($post_id); //Gets post ID
			$the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
			$excerpt_length = 35; //Sets excerpt length by word count
			$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
			$words = explode(' ', $the_excerpt, $excerpt_length + 1);
			if(count($words) > $excerpt_length) :
			array_pop($words);
			array_push($words, '...');
			$the_excerpt = implode(' ', $words);
			endif;
			$the_excerpt = '<p>' . $the_excerpt . '</p>';
			return $the_excerpt;
	}

	function kz_megadropdown_featured_post( $post ){

			//attention, seul l'ID du post est dispo
			//passer par l'ID pour recuperer le reste

			//amelioration : passer par le $post global

			$postid = $post->ID;

			$aperma = '<a href="'.get_permalink($postid).'">';

			$category = get_the_category($postid);

			$parents_id  = get_category_parents($category[0], FALSE, ',', TRUE);
			$parents_name  = get_category_parents($category[0], FALSE, ',', FALSE);

			$parentsIDArray=explode(",",$parents_id);
			$parentsNameArray=explode(",",$parents_name);

			$html  = '<article class="'.$parentsIDArray[0].'"><div class="entry-thumbnail">'.$aperma;
			$html .=  get_the_post_thumbnail($post->ID, array(120,120), array('title'	=> trim(strip_tags( $post->post_title ))) );
			$html .= '</a></div><div class="entry-header"><div class="entry-title">'.$aperma.get_the_title($post->ID).'</a></div></div><div class="entry-content">';
			$html .= $aperma.get_excerpt_by_id($post->ID).'</a></div></article>';
			// echo $html;
			return $html;

	}

	function kz_megadropdown() {
		
		$args = array(
			'theme_location'  => 'primary-menu',
			'menu'            => '',
			'container'       => '',
			'container_class' => '',
			'container_id'    => '',
			'menu_class'      => 'nav',
			'menu_id'         => 'megadropdown',
			'echo'            => true,
			'fallback_cb'     => '',
			'before'          => '',
			'after'           => '',
			'link_before'     => '',
			'link_after'      => '',
			'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			'depth'           => 0,
			'walker'          => new kz_walker_nav()
		);

		return wp_nav_menu( $args );

	}

	//retourne la liste non formattée des termes (array)
	//associés à des posts catégorisés dans une autre taxonomie
	//ex: lister les "Villes" associés à des "Evenements" (Category, $other_taxo_id)
	// function kz_get_terms_associated_with($taxo,$other_taxo,$other_taxo_id)
	// {
		// $args = array(
		//     'orderby'       => 'count', 
		//     'order'         => 'DESC',
		//     'hide_empty'    => true,  
		//     'hierarchical'  => true, 
		// ); 
		// $categories = get_terms( $taxo, $args );

		// $args = array(
		// 	'post_type' => 'post',
		// 	'tax_query' => array(
		// 		'relation' => 'AND',
		// 		array(
		// 			'taxonomy' => $taxo,
		// 			'field' => 'id',
		// 			'terms' => $categories
		// 			),
		// 		array(
		// 			'taxonomy' => $other_taxo,
		// 			'field' => 'id',
		// 			'terms' => array($other_taxo_id)
		// 			)
		// 		)
		// );
		// $query = new WP_Query( $args );

		// foreach ($categories as $key => $value) {
		// 	if ()
		// }
	// }


	class kz_walker_nav extends Walker_Nav_Menu
	{
		  function start_el(&$output, $item, $depth, $args)
		  {
			   global $wp_query;global $post;
			   $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

			   $class_names = $value = '';

			   $classes = empty( $item->classes ) ? array() : (array) $item->classes;

			   $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
			   $class_names = ' class="'. esc_attr( $class_names ) . '"';

			   $output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

			   $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
			   $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
			   $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
			   $attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';

			   $mega = '';

			   if(in_array('menu-item-type-taxonomy',$classes)){

			   		$taxo = ($item->xfn!='') ? $item->xfn : 'category';
			   		$cat = get_term_by('id', $item->object_id, $taxo);

			   		$post_args = array(
					      'numberposts' => 1,
					      'category' => $item->object_id
					    );

	    			$my_posts = get_posts($post_args);

					 $mega .= '<div class="dropdown_5columns shadow-medium '.$cat->slug.'"><div><ul class="col_3 '.$cat->slug.'">';
					 $mega .= wp_list_categories(array('taxonomy'=>$taxo,'orderby'=>'count','order' => 'DESC','child_of'=>$item->object_id,'title_li'=>'','show_count'=> 0, 'echo'=>0,'hierarchical' => 0,'hide_empty' => 1));
					 $mega .= '</ul><ul class="col_2">';
					 //$mega .= kz_get_terms_associated_with('ville', $taxo,$item->object_id);
					 $mega .= '</ul></div><div class="col_5">';
					 $mega .= kz_megadropdown_featured_post($my_posts[0]);
					 $mega .= '</div></div>';

					 //todo : remplacer le walker de wp_list_categories pour ajouter les classes aux <a href='...'>
					 //afin de regrouper tous les styles equivalents dans une classe css
					 //ex: voir egalement les [p.meta a] dans les listes d'articles [category archive]
					 //$attributes .= ' class="kz-tag '.$cat->slug.'"';

				}

			   if($depth != 0)
			   {
						 $description = $append = $prepend = "";
			   }

				$item_output = $args->before;
				$item_output .= '<a '. $attributes .'>';
				$item_output .= $args->link_before .$prepend.apply_filters( 'the_title', $item->title, $item->ID ).$append;
				$item_output .= $description.$args->link_after;
				$item_output .= '</a>';
				$item_output .= $mega;
				$item_output .= $args->after;

				$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			}

	}

?>