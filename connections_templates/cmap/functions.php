<?php
if ( ! class_exists('cMap') )
{
	/**
	 * @todo add the contact first/last name to the template and add the options to display or not.
	 */
	class cMap
	{
		/**
		 * Load the template filters.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 */
		public function __construct()
		{
			//Update the permitted shortcode attribute the user may use and override the template defaults as needed.
			add_filter( 'cn_list_atts_permitted-cmap' , array(&$this, 'initShortcodeAtts') );
			add_filter( 'cn_list_atts-cmap' , array(&$this, 'initTemplateOptions') );
			
			// Print the $.goMap() jQuery Google Map plugin in the footer.
			//$printgoMap = create_function( '' , 'wp_print_scripts("jquery-gomap-min");' );
			add_action( 'wp_footer', create_function( '' , 'wp_print_scripts("jquery-gomap-min");' ) );
			
			// Print the Chosen jQuery plugin in the footer.
			//$printChosen = create_function( '' , 'wp_print_scripts("jquery-chosen-min");' );
			add_action( 'wp_footer', create_function( '' , 'wp_print_scripts("jquery-chosen-min");' ) );
		}
		
		/**
		 * Initiate the permitted template shortcode options and load the default values.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 */
		public function initShortcodeAtts( $permittedAtts = array() )
		{
			$permittedAtts['enable_search'] = TRUE;
			
			$permittedAtts['enable_pagination'] = TRUE;
			$permittedAtts['page_limit'] = 20;
			$permittedAtts['pagination_position'] = 'before';
			
			$permittedAtts['enable_category_select'] = TRUE;
			$permittedAtts['show_empty_categories'] = TRUE;
			$permittedAtts['show_category_count'] = FALSE;
			$permittedAtts['category_select_position'] = 'before';
			$permittedAtts['enable_category_by_root_parent'] = FALSE;
			$permittedAtts['enable_category_multi_select'] = FALSE;
			$permittedAtts['enable_category_group_by_parent'] = FALSE;
			
			//$permittedAtts['enable_name_permalink'] = TRUE;
			
			$permittedAtts['enable_map'] = TRUE;
			$permittedAtts['enable_bio'] = TRUE;
			$permittedAtts['enable_bio_head'] = TRUE;
			$permittedAtts['enable_note'] = TRUE;
			$permittedAtts['enable_note_head'] = TRUE;
			$permittedAtts['enable_website_link'] = TRUE;
			
			$permittedAtts['show_addresses'] = TRUE;
			$permittedAtts['show_phone_numbers'] = TRUE;
			$permittedAtts['show_email'] = TRUE;
			$permittedAtts['show_im'] = TRUE;
			$permittedAtts['show_social_media'] = TRUE;
			$permittedAtts['show_birthday'] = TRUE;
			$permittedAtts['show_anniversary'] = TRUE;
			
			$permittedAtts['address_types'] = NULL;
			$permittedAtts['phone_types'] = NULL;
			$permittedAtts['email_types'] = NULL;
			
			$permittedAtts['image'] = 'logo';
			$permittedAtts['image_width'] = NULL;
			$permittedAtts['image_height'] = NULL;
			$permittedAtts['image_fallback'] = 'block';
			$permittedAtts['tray_image'] = 'photo';
			$permittedAtts['tray_image_width'] = NULL;
			$permittedAtts['tray_image_height'] = NULL;
			$permittedAtts['tray_image_fallback'] = 'none';
			
			$permittedAtts['map_type'] = 'm';
			$permittedAtts['map_zoom'] = 13;
			$permittedAtts['map_frame_height'] = 400;
			
			$permittedAtts['str_select'] = 'Select Category';
			$permittedAtts['str_select_all'] = 'Show All Categories';
			$permittedAtts['str_image'] = 'No Logo Available';
			$permittedAtts['str_tray_image'] = 'No Photo Available';
			$permittedAtts['str_map_show'] = 'Show Map';
			$permittedAtts['str_map_hide'] = 'Close Map';
			$permittedAtts['str_bio_head'] = 'Biography';
			$permittedAtts['str_bio_show'] = 'Show Bio';
			$permittedAtts['str_bio_hide'] = 'Close Bio';
			$permittedAtts['str_note_head'] = 'Notes';
			$permittedAtts['str_note_show'] = 'Show Notes';
			$permittedAtts['str_note_hide'] = 'Close Notes';
			$permittedAtts['str_contact'] = 'Contact';
			$permittedAtts['str_home_addr'] = 'Home';
			$permittedAtts['str_work_addr'] = 'Work';
			$permittedAtts['str_school_addr'] = 'School';
			$permittedAtts['str_other_addr'] = 'Other';
			$permittedAtts['str_home_phone'] = 'Home Phone';
			$permittedAtts['str_home_fax'] = 'Home Fax';
			$permittedAtts['str_cell_phone'] = 'Cell Phone';
			$permittedAtts['str_work_phone'] = 'Work Phone';
			$permittedAtts['str_work_fax'] = 'Work Fax';
			$permittedAtts['str_personal_email'] = 'Personal Email';
			$permittedAtts['str_work_email'] = 'Work Email';
			$permittedAtts['str_visit_website'] = 'Visit Website';
			
			$permittedAtts['name_format'] = '%prefix% %first% %middle% %last% %suffix%';
			$permittedAtts['contact_name_format'] = '%label%: %first% %last%';
			$permittedAtts['addr_format'] = '%label% %line1% %line2% %line3% %city% %state%  %zipcode% %country%';
			$permittedAtts['email_format'] = '%label%%separator% %address%';
			$permittedAtts['phone_format'] = '%label%%separator% %number%';
			$permittedAtts['link_format'] = '%label%%separator% %title%';
			$permittedAtts['date_format'] = '%label%%separator% %date%';
			
			return $permittedAtts;
		}
		
		/**
		 * Initiate the template options using the user supplied shortcode option values.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 */
		public function initTemplateOptions($atts)
		{
			$convert = new cnFormatting();
			
			$this->enableSearch = $atts['enable_search'];
			
			$this->enablePagination = $atts['enable_pagination'];
			( empty($atts['limit']) ) ? $this->pageLimit = $atts['page_limit'] : $this->pageLimit = $atts['limit'];
			$this->paginationPosition = $atts['pagination_position'];
			
			$this->enableCategorySelect = $atts['enable_category_select'];
			$this->showEmptyCategories = $atts['show_empty_categories'];
			$this->showCategoryCount = $atts['show_category_count'];
			$this->categorySelectPosition = $atts['category_select_position'];
			$this->enableCategoryByRootParent = $atts['enable_category_by_root_parent'];
			$this->categoryRootParents = $atts['category']; // The category IDs to limit the category select to.
			$this->enableMultiSelect = $atts['enable_category_multi_select'];
			$this->enableGroupByCategoryParent = $atts['enable_category_group_by_parent'];
			
			//$this->enableNamePermalink = $atts['enable_name_permalink'];
			
			$this->enableMap = $atts['enable_map'];
			$this->enableBio = $atts['enable_bio'];
			$this->enableBioHead = $atts['enable_bio_head'];
			$this->enableNote = $atts['enable_note'];
			$this->enableNoteHead = $atts['enable_note_head'];
			$this->enableWebsite = $atts['enable_website_link'];
			
			$this->showAddresses = $atts['show_addresses'];
			$this->showPhoneNumbers = $atts['show_phone_numbers'];
			$this->showEmail = $atts['show_email'];
			$this->showIM = $atts['show_im'];
			$this->showSocialMedia = $atts['show_social_media'];
			$this->showBirthday = $atts['show_birthday'];
			$this->showAnniversary = $atts['show_anniversary'];
			
			$this->addressTypes = $atts['address_types'];
			$this->phoneTypes = $atts['phone_types'];
			$this->emailTypes = $atts['email_types'];
			
			$this->image = $atts['image'];
			$this->imageHeight = $atts['image_height'];
			$this->imageWidth = $atts['image_width'];
			$this->imageFallback = $atts['image_fallback'];
			$this->trayImage = $atts['tray_image'];
			$this->trayImageHeight = $atts['tray_image_height'];
			$this->trayImageWidth = $atts['tray_image_width'];
			$this->trayImageFallback = $atts['tray_image_fallback'];
			
			$this->mapType = $atts['map_type'];
			$this->mapZoom = $atts['map_zoom'];
			$this->mapFrameHeight = $atts['map_frame_height'];
			
			$this->strSelect = $atts['str_select'];
			$this->strSelectAll = $atts['str_select_all'];
			$this->strImage = $atts['str_image'];
			$this->strTrayImage = $atts['str_tray_image'];
			$this->strMapShow = $atts['str_map_show'];
			$this->strMapHide = $atts['str_map_hide'];
			$this->strBioHead = $atts['str_bio_head'];
			$this->strBioShow = $atts['str_bio_show'];
			$this->strBioHide = $atts['str_bio_hide'];
			$this->strNoteHead = $atts['str_note_head'];
			$this->strNoteShow = $atts['str_note_show'];
			$this->strNoteHide = $atts['str_note_hide'];
			$this->strContactLabel = $atts['str_contact'];
			$this->strHomeAddress = $atts['str_home_addr'];
			$this->strWorkAddress = $atts['str_work_addr'];
			$this->strSchoolAddress = $atts['str_school_addr'];
			$this->strOtherAddress = $atts['str_other_addr'];
			$this->strHomePhone = $atts['str_home_phone'];
			$this->strHomeFax = $atts['str_home_fax'];
			$this->strCellPhone = $atts['str_cell_phone'];
			$this->strWorkPhone = $atts['str_work_phone'];
			$this->strWorkFax = $atts['str_work_fax'];
			$this->strPersonalEmail = $atts['str_personal_email'];
			$this->strWorkEmail = $atts['str_work_email'];
			$this->strVisitWebsite = $atts['str_visit_website'];
			
			$this->nameFormat = $atts['name_format'];
			$this->contactNameFormat = $atts['contact_name_format'];
			$this->addressFormat = $atts['addr_format'];
			$this->emailFormat = $atts['email_format'];
			$this->phoneFormat = $atts['phone_format'];
			$this->linkFormat = $atts['link_format'];
			$this->dateFormat = $atts['date_format'];
			
			// Set the entry card width and map iframe width defaults
			if ( empty($atts['width']) )
			{
				$this->mapFrameWidth = NULL;
				//$this->mapFrameWidth = 560;
				//$atts['width'] = 590;
			}
			else
			{
				$this->mapFrameWidth = $atts['width'] - 30;
			}
			
			// Because the shortcode option values are treated as strings some of the values have to converted to boolean.
			$convert->toBoolean( $this->enableSearch );
			$convert->toBoolean( $this->enablePagination );
			$convert->toBoolean( $this->enableCategorySelect );
			$convert->toBoolean( $this->showEmptyCategories );
			$convert->toBoolean( $this->showCategoryCount );
			$convert->toBoolean( $this->enableCategoryByRootParent );
			$convert->toBoolean( $this->enableMultiSelect );
			$convert->toBoolean( $this->enableNamePermalink );
			$convert->toBoolean( $this->enableMap );
			$convert->toBoolean( $this->enableBio );
			$convert->toBoolean( $this->enableBioHead );
			$convert->toBoolean( $this->enableNote );
			$convert->toBoolean( $this->enableNoteHead );
			$convert->toBoolean( $this->enableWebsite );
			$convert->toBoolean( $this->showAddresses );
			$convert->toBoolean( $this->showPhoneNumbers );
			$convert->toBoolean( $this->showEmail );
			$convert->toBoolean( $this->showIM );
			$convert->toBoolean( $this->showSocialMedia );
			$convert->toBoolean( $this->showBirthday );
			$convert->toBoolean( $this->showAnniversary );
			$convert->toBoolean( $this->enableGroupByCategoryParent );
			
			add_filter( 'cn_list_index-cmap' , array(&$this, 'listIndex'), 10, 2 );
			add_filter( 'cn_phone_number' , array(&$this, 'phoneLabels') );
			add_filter( 'cn_email_address' , array(&$this, 'emailLabels') );
			add_filter( 'cn_address' , array(&$this, 'addressLabels') );
			
			// Start the form.
			add_action( 'cn_action_list_before-cmap' , array($this, 'formOpen'), -1 );
			
			// If search is enabled, add the appropiate filters.
			if ( $this->enableSearch )
			{
				add_filter( 'cn_list_retrieve_atts-cmap' , array(&$this, 'limitList'), 10 );
				add_action( 'cn_action_list_before-cmap' , array(&$this, 'searchForm') , 1 );
			}
			
			// If pagination is enabled add the appropiate filters.
			if ( $this->enablePagination )
			{
				add_filter( 'cn_list_retrieve_atts-cmap' , array(&$this, 'limitList'), 10 );
				add_action( 'cn_action_list_' . $this->paginationPosition . '-cmap' , array(&$this, 'listPages') );
			}
			
			// If the category select/filter feature is enabled, add the appropiate filters.
			if ( $this->enableCategorySelect )
			{
				add_filter( 'cn_list_retrieve_atts-cmap' , array(&$this, 'setCategory') );
				add_action( 'cn_action_list_' . $this->categorySelectPosition . '-cmap' , array(&$this, 'categorySelect') , 5 );
			}
			
			// Close the form
			add_action( 'cn_action_list_after-cmap' , array($this, 'formClose'), 11 );
			
			return $atts;
		}
		
		/**
		 * Alter the Address Labels.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param object $data
		 * @return object
		 */
		public function addressLabels($data)
		{
			switch ($data->type)
			{
				case 'home':
					$data->name = $this->strHomeAddress;
					break;
				case 'work':
					$data->name = $this->strWorkAddress;
					break;
				case 'school':
					$data->name = $this->strSchoolAddress;
					break;
				case 'other':
					$data->name = $this->strOtherAddress;
					break;
			}
			
			return $data;
		}
		
		/**
		 * Alter the Phone Labels.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param object $data
		 * @return object
		 */
		public function phoneLabels($data)
		{
			switch ($data->type)
			{
				case 'homephone':
					$data->name = $this->strHomePhone;
					break;
				case 'homefax':
					$data->name = $this->strHomeFax;
					break;
				case 'cellphone':
					$data->name = $this->strCellPhone;
					break;
				case 'workphone':
					$data->name = $this->strWorkPhone;
					break;
				case 'workfax':
					$data->name = $this->strWorkFax;
					break;
			}
			
			return $data;
		}
		
		/**
		 * Alter the Email Labels.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param object $data
		 * @return object
		 */
		public function emailLabels($data)
		{
			switch ($data->type)
			{
				case 'personal':
					$data->name = $this->strPersonalEmail;
					break;
				case 'work':
					$data->name = $this->strWorkEmail;
					break;
				
				default:
					$data->name = 'Email';
				break;
			}
			
			return $data;
		}
		
		/**
		 * Limit the returned results.
		 * 
		 * @access private
		 * @since 2.0
		 * @author Steven A. Zahm
		 * @version 2.0
		 * @param array $results
		 * @return array
		 */
		public function limitList($atts)
		{
			$atts['limit'] = $this->pageLimit; // Page Limit
			
			return $atts;
		}
		
		public function formOpen()
		{
		    global $wp_rewrite;
			
			$permalink = get_permalink();
			
			if ( $wp_rewrite->using_permalinks() )
			{
				echo '<form class="cn-form" action="' . $permalink . '" method="get">';
			}
			else
			{
				global $post;
				
				echo '<form class="cn-form" method="get">';
				echo '<input type="hidden" name="p" value="' . $post->ID .'">';
			}
		}

		public function formClose()
		{
		    echo '</form>';
		}
		
		public function searchForm()
		{
			global $connections;
			
			$connections->template->search();
		}
		
		/**
		 * Limits the returned results.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.1
		 * @param string
		 * @param array $results
		 * @return string
		 */
		public function listPages()
		{
			global $connections;
			
			$connections->template->pagination( array( 'limit' => $this->pageLimit ) );
		}
		
		/**
		 * Returns the form for the category select list.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param string $out
		 * @param array $results
		 * @return string
		 */
		public function categorySelect()
		{
			global $connections;
			
			$atts = array(
				'default' => $this->strSelect,
				'select_all' => $this->strSelectAll,
				'type' => $this->enableMultiSelect ? 'multiselect' : 'select',
				'group' => $this->enableGroupByCategoryParent,
				'show_count' => $this->showCategoryCount,
				'show_empty' => $this->showEmptyCategories,
				'parent_id' => ( $this->enableCategoryByRootParent ) ? $this->categoryRootParents : array(),
				);
			
			$connections->template->category( $atts );
		}
		
		/**
		 * Alters the shortcode attribute values before the query is processed.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param array $atts
		 * @return array
		 */
		public function setCategory($atts)
		{
			global $connections;
			
			if ( $this->enableMultiSelect )
			{
				if ( get_query_var('cn-cat') ) $atts['category_in'] = get_query_var('cn-cat');
			}
			else
			{
				if ( get_query_var('cn-cat') ) $atts['category'] = get_query_var('cn-cat');
			}
			//var_dump($atts);die;
			
			return $atts;
		}
		
		/**
		 * Dynamically builds the alpha index based on the available entries.
		 * 
		 * @author Steven A. Zahm
		 * @version 1.0
		 * @param string $index
		 * @param array $results
		 * @return string
		 */
		public function listIndex($index, $results = NULL)
		{
			$previousLetter = NULL;
			$setAnchor = NULL;
			
			foreach ( (array) $results as $row)
			{
				$entry = new cnEntry($row);
				$currentLetter = strtoupper(mb_substr($entry->getFullLastFirstName(), 0, 1));
				if ($currentLetter != $previousLetter)
				{
					$setAnchor .= '<a href="#' . $currentLetter . '">' . $currentLetter . '</a> ';
					$previousLetter = $currentLetter;
				}
			}
			
			return '<div class="cn-alphaindex">' . $setAnchor . '</div>';
		}
	}
	//print_r($this);
	$this->cMap = new cMap();
}
?>