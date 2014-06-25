<?php 
/**
 Ajout Microdata 
 Gestion des featured
*/

//todo : if (jQuery(this).find(".contact-given-name").text()!=kidzou_jsvars.connections_featured_label)

?>
<?php 
	$isFeatured = false;
	if ($entry->getContactFirstName()=='featured') 
		$isFeatured = true;
 ?>

<div id="entry-id-<?php echo $entry->getRuid(); ?>" class="cn-entry <?php if ($isFeatured) echo 'featured'; ?>" itemscope itemtype="http://schema.org/Organization">
	<table border="0px" bordercolor="#E3E3E3" cellspacing="0px" cellpadding="0px">
	    <tr>
	        <td align="left" width="55%" valign="top">
	        	<?php if ($isFeatured) echo '<span class="kz-bg-pink cn-featured">A la une !</span>'; ?>
				<?php $entry->getImage( array( 'image' => $template->cMap->image , 'height' => $template->cMap->imageHeight , 'width' => $template->cMap->imageWidth , 'fallback' => array( 'type' => $template->cMap->imageFallback , 'string' => $template->cMap->strImage ) ) ); ?>
	        </td>
	        <td align="right" valign="top" style="text-align: right;">
	        	
				<div style="clear:both; margin: 5px 5px;">
		        	<div style="margin-bottom: 5px;">
						<?php $entry->getNameBlock( array( 'format' => $template->cMap->nameFormat, /*'link' => $template->cMap->enableNamePermalink*/ ) ); ?>
						<?php $entry->getTitleBlock(); ?>
						<?php $entry->getOrgUnitBlock(); ?>
						<?php $entry->getContactNameBlock( array( 'format' => $template->cMap->contactNameFormat , 'label' => $template->cMap->strContactLabel ) ); ?>
					</div>
					
					<?php
						if ( $template->cMap->showAddresses ) $entry->getAddressBlock( array( 'format' => $template->cMap->addressFormat , 'type' => $template->cMap->addressTypes ) );
						
						if ( $template->cMap->showPhoneNumbers ) $entry->getPhoneNumberBlock( array( 'format' => $template->cMap->phoneFormat , 'type' => $template->cMap->phoneTypes ) );
						
						if ( $template->cMap->showEmail ) $entry->getEmailAddressBlock( array( 'format' => $template->cMap->emailFormat , 'type' => $template->cMap->emailTypes ) );
						
						if ( $template->cMap->showIM ) $entry->getImBlock();
						if ( $template->cMap->showSocialMedia ) $entry->getSocialMediaBlock();
						
						//$entry->getDateBlock();
						//$entry->getLinkBlock();
						
						if ( $template->cMap->showBirthday ) echo $entry->getBirthdayBlock('F jS');
						if ( $template->cMap->showAnniversary ) echo $entry->getAnniversaryBlock('F jS');
					?>
					
				</div>
	        </td>
	    </tr>
	    
	    <tr>
	        <td valign="bottom" style="text-align: left;">
				
				<?php
					
					if ( $template->cMap->enableNote && $entry->getNotes() != '' )
					{
						echo '<a class="cn-note-anchor toggle-div" id="note-anchor-' , $entry->getRuid() , '" href="#" data-uuid="' , $entry->getRuid() , '" data-div-id="note-block-' . $entry->getRuid() . '" data-str-show="' , $template->cMap->strNoteShow , '" data-str-hide="' , $template->cMap->strNoteHide , '">' , $template->cMap->strNoteShow , '</a>';
					}
					
					if ( ( $template->cMap->enableNote && $entry->getNotes() != '' ) && ( $template->cMap->enableBio && $entry->getBio() != '' ) )
					{
						echo ' | ';
					}
					
					if ( $template->cMap->enableBio && $entry->getBio() != '' )
					{
						echo '<a class="cn-bio-anchor toggle-div" id="bio-anchor-' , $entry->getRuid() , '" href="#" data-uuid="' , $entry->getRuid() , '" data-div-id="bio-block-' . $entry->getRuid() . '" data-str-show="' , $template->cMap->strBioShow , '" data-str-hide="' , $template->cMap->strBioHide , '">' , $template->cMap->strBioShow , '</a>';
					}
				?>
				
	        </td>
			
			<td align="right" valign="bottom"  style="text-align: right;">
				
				<?php
					/*
					 * @TODO
					 * Using the preferred link would be the best, but the previous was to use the intial website address.
					 * For backward compatiblity if there is no preferred link this should output the intial link.
					 */
					$links = $entry->getWebsites();
					
					if ( ! empty($links) && $template->cMap->enableWebsite )
					{
						$website = $entry->getWebsites();
						if ($website[0]->address != NULL) echo '<a class="url" itemprop="url" href="' , $website[0]->address , '" target="' , $website[0]->target , '"' , ( ( empty($website[0]->followString) ? '' : ' rel="' . $website[0]->followString . '"' ) ) , '>' , ( ( $template->cMap->strVisitWebsite != 'Visit Website' ) ? $template->cMap->strVisitWebsite : $website[0]->title ) , '</a>' , "\n";

						echo ' | ';
					}
					
					if ( $template->cMap->enableMap )
					{
						
						$gMap = $entry->getMapBlock( array( 
							'height' => $template->cMap->mapFrameHeight , 
							'width' => ( $template->cMap->mapFrameWidth ) ? $template->cMap->mapFrameWidth : NULL , 
							'return' => TRUE
							)
						);
							
						if ( ! empty($gMap) )
						{
							$mapDiv = '<div class="cn-gmap" id="map-container-' . $entry->getRuid() . '" style="display: none;">' . $gMap . '</div>';
							
							echo '<a class="cn-map-anchor toggle-map" id="map-anchor-' , $entry->getRuid() , '" href="#" data-uuid="' , $entry->getRuid() , '" data-str-show="' , $template->cMap->strMapShow , '" data-str-hide="' , $template->cMap->strMapHide , '">' , $template->cMap->strMapShow , '</a> | ';
						}
					}
				?>
				
				<span class="cn-return-to-top"><?php echo $entry->returnToTopAnchor() ?></span>
	        </td>
	    </tr>
		
		<tr>
			<td colspan="2">
				<?php
					
					if ( $template->cMap->enableNote && $entry->getNotes() != '' )
					{
						echo '<div class="cn-notes" id="note-block-' , $entry->getRuid() , '" style="display: none;">';
							if ( $template->cMap->enableNoteHead )
							{
								echo '<h4>' , $template->cMap->strNoteHead , '</h4>';
							}
							
							echo $entry->getNotesBlock();
						echo '</div>';
					}
					
					if ( $template->cMap->enableBio && $entry->getBio() != '' )
					{
						echo '<div class="cn-bio" id="bio-block-' , $entry->getRuid() , '" style="display: none;">';
							
							if ( $template->cMap->enableBioHead )
							{
								echo '<h4>' , $template->cMap->strBioHead , '</h4>';
							}
							
							$entry->getImage( array( 'image' => $template->cMap->trayImage , 'height' => $template->cMap->trayImageHeight , 'width' => $template->cMap->trayImageWidth , 'fallback' => array( 'type' => $template->cMap->trayImageFallback , 'string' => $template->cMap->strTrayImage ) ) );
							
							echo $entry->getBioBlock();
							
							echo '<div class="cn-clear"></div>';
							
						echo '</div>';
					}
				?>
			</td>
		</tr>
		
	</table>
	<?php if ( isset($mapDiv) ) echo $mapDiv;?>
</div>