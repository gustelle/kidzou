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
			if (!$post)
				return "";

			$postid = $post->ID;

			$aperma = '<a href="'.get_permalink($postid).'">';

			$category = get_the_category($postid);  

			//gestion des posts qui n'ont pas de categorie ?!
			if (!is_array($category) || empty($category)) {

				$cssClass = "";

			} else {

				$cat_id_obj = $category[0];
				$cat_id = $cat_id_obj->term_id;
				
				$parents_id  = get_category_parents($cat_id, FALSE, ',', TRUE);
				
				if (!is_wp_error( $parents_id )) {
					$parentsIDArray=explode(",",$parents_id);
					$cssClass = $parentsIDArray[0];
				} else {
					$cssClass = "";
				}
			}
				
			$html  = '<article class="'.$cssClass.'"><div class="entry-thumbnail">'.$aperma;
			$html .=  get_the_post_thumbnail($post->ID, array(120,120), array('title'	=> trim(strip_tags( $post->post_title ))) );
			$html .= '</a></div><div class="entry-header"><div class="entry-title">'.$aperma.get_the_title($post->ID).'</a></div></div><div class="entry-content">';
			$html .= $aperma.get_excerpt_by_id($post->ID).'</a></div></article>';
			
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



	class kz_walker_nav extends Walker_Nav_Menu
	{
		  function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0 ) 
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

			   if(in_array('menu-item-type-taxonomy',$classes) || in_array('menu-item-type-post_type_archive', $classes)) {

			   		$taxo = ($item->xfn!='') ? $item->xfn : 'category';
			   		
			   		$cat = get_term_by('id', $item->object_id, $taxo);

			   		if (function_exists('kz_get_request_metropole'))
			   			$metropole = kz_get_request_metropole();
			   		else
			   			$metropole = '';

			   		//recuperer le post type dans la classe 'menu-item-object-xxx'
			   		// ex : menu-item-object-category
		   			$post_type = 'post';
		   			
		   			//generaliser cela...
		   			if (in_array('menu-item-object-offres',$classes)) {
		   				$post_type = 'offres';
		   			}
		   			else if (in_array('menu-item-object-concours',$classes)) {
		   				$post_type = 'concours';
		   			}
		   			// $exploded = explode(" ", $pizza);

		   			$cat_id = $item->object_id;
		   			$dropdown_class = $cat->slug;

		   			//si l'item de nav n'est pas une categorie, on prend toutes les cats
			   		if ($cat==null || $cat->slug=='') {
			   			$cat_id = 0; 
			   			$dropdown_class = $post_type;
			   		}
			 		
			 		if ($metropole!='')
			   			$post_args = array(
					      'numberposts' => 1,
					      'post_type' 	=> $post_type,
					      'category' => $cat_id,
					      'tax_query' => array(
						        array(
						              'taxonomy' => 'ville',
						              'field' => 'slug',
						              'terms' => $metropole,
						              )
						        )
					    );
			   		else 
			   			$post_args = array(
					      'numberposts' => 1,
					      'post_type' 	=> $post_type,
					      'category' => $cat_id
					    );

		   			$list ='';

		   			if ($cat_id>0)
			   			$list = wp_list_categories(array('taxonomy'=>$taxo,'orderby'=>'count','order' => 'DESC','child_of'=>$cat_id,'title_li'=>'','show_count'=> 0, 'echo'=>0,'hierarchical' => 0,'hide_empty' => 1));	

			   		$my_posts = get_posts($post_args);

			   		 $mega .= '<div class="dropdown_5columns shadow-medium '.$dropdown_class.'"><div><ul class="col_3 '.$dropdown_class.'">';
					 $mega .= $list;
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

			   // if($depth != 0)
			   // {
						 $description = $append = $prepend = "";
			   // }

				$item_output = $args->before;
				$item_output .= '<a '. $attributes .'>';
				$item_output .= $args->link_before .$prepend.apply_filters( 'the_title', $item->title, $item->ID ).$append;
				$item_output .= $description.$args->link_after;
				$item_output .= '</a>';
				$item_output .= $mega;
				$item_output .= $args->after;

				$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args,  $id );
			}

	}

?>