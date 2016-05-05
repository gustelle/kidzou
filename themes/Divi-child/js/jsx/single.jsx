
/**
 * Rendu d'un single au sens single.php de WP
 *
 */
var Post = React.createClass({
  
  getInitialState: function() {
  	var self = this;

  	var isTypeEvent   = self.props.is_event;
    var noEndDate     = (typeof self.props.dates.end_date == 'undefined' || self.props.dates.end_date=='');

    return {
      startDate   : isTypeEvent ? moment(self.props.dates.start_date, 'YYYY-MM-DD HH:mm:ss') : '',
      endDate     : isTypeEvent ? moment(self.props.dates.end_date, 'YYYY-MM-DD HH:mm:ss') : '',
      singleDay   : (isTypeEvent && noEndDate) || (isTypeEvent && self.props.dates.start_date.split(' ')[0]===self.props.dates.end_date.split(' ')[0]),
    };
  },

  render: function () {
    
    var self = this;

    post_id    = "post-" + self.props.ID;

    var addressDisplay 	= (self.props.has_location && self.props.location.location_address!='' ? 'block' : 'none');
    var phoneDisplay 	= (self.props.has_location && self.props.location.location_phone_number!='' ? 'block' : 'none');
    var websiteDisplay 	= (self.props.has_location && self.props.location.location_website!='' ? 'block' : 'none');

    return (

    	<div>
    		
    		<div dangerouslySetInnerHTML={{__html: self.props.single_top}}></div>
    		<article id={post_id} className={self.props.post_class} >

    			<div className="entry-content">
    				<div className="et_pb_section et_section_specialty">
						<div className="et_pb_row">
							<div className="et_pb_column et_pb_column_3_4">
								<div className="et_pb_row_inner">
									<div className="et_pb_column et_pb_column_3_8 et_pb_column_inner" dangerouslySetInnerHTML={{__html: self.props.html_thumb}}></div>
									<div className="et_pb_column et_pb_column_3_8 et_pb_column_inner">
										<div className="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
											
											<div id="voteComponent"></div>

						                    <h1 dangerouslySetInnerHTML={{__html: self.props.title}}></h1>
						                    <span dangerouslySetInnerHTML={{__html: self.props.meta}}></span>
										</div>
										{
											self.props.has_location &&
											<div>
												<div className="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
													<p className="location" style={{display:addressDisplay}}><i className="fa fa-map-marker"></i>{self.props.location.location_address}</p>
													<p className="location" style={{display:phoneDisplay}}><i className="fa fa-phone"></i>{self.props.location.location_phone_number}</p>
													<p className="location" style={{display:websiteDisplay}}><i className="fa fa-tablet"></i><a target="_blank" href={self.props.location.location_website}>Visiter le site web</a></p>
												</div>
												<hr className="et_pb_space" />
											</div>
										}
										{
											self.props.is_event && 
											<div className="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
												<p className="location font-2x">
													<i className="fa fa-calendar"></i>
													{ self.state.singleDay && 
														<span>Le {moment(self.state.startDate).format('DD MMM')}</span>
													}
													{ !self.state.singleDay && 
														<span>Du {moment(self.state.startDate).format('DD MMM')} au {moment(self.state.endDate).format('DD MMM')}</span>
													}
												</p>
											</div> 
										}
										<div dangerouslySetInnerHTML={{__html: self.props.social_sharing}}></div>
									</div>
								</div>

								<div className="et_pb_row_inner">
									<div className="et_pb_column et_pb_column_4_4 et_pb_column_inner">
										<div className="et_pb_text et_pb_bg_layout_light et_pb_text_align_justify">
											{
												self.props.pub!=='' &&
												<div className="post_ad ad" data-content="Publicite">
													<span dangerouslySetInnerHTML={{__html: self.props.pub}}></span>
												</div>
											}
											<div dangerouslySetInnerHTML={{__html: self.props.content}}></div>
											<div dangerouslySetInnerHTML={{__html: self.props.post_format_content}} className="post_format_block" ></div> 
											<div dangerouslySetInnerHTML={{__html: self.props.link_pages}}></div> 

										</div>
									</div>
								</div>

								<div className="et_pb_row_inner post_inner_content">
									<div className="et_pb_column et_pb_column_4_4 et_pb_column_inner">

										{
											self.props.has_location && 
											<div className="et_pb_tabs">
												<ul className="et_pb_tabs_controls clearfix">
													<li className="et_pb_tab_active"><strong>{self.props.location.location_name}</strong></li>
												</ul>
												<div className="et_pb_all_tabs">
													<div className="et_pb_tab clearfix et_pb_active_content">
														
														<div dangerouslySetInnerHTML={{__html: self.props.map}}></div> 
														
													</div> 
												</div> 
											</div>
										}
										{
											self.props.is_ad &&
											<div className="et-single-post-ad">
												{
													self.props.adsense!=='' &&
													<div dangerouslySetInnerHTML={{__html: self.props.adsense}}></div> 
												}
												{
													self.props.adimg_img!=='' &&
													<a href={self.props.adimg_url}><img src={self.props.adimg_img} alt="468 ad" className="foursixeight" /></a>
												}
											</div>
										}
										<div dangerouslySetInnerHTML={{__html: self.props.comments}}></div>
										<div dangerouslySetInnerHTML={{__html: self.props.single_bottom}}></div>

									</div>
								</div>

							</div>

							<div className="et_pb_column et_pb_column_1_4">
								<div className="et_pb_widget_area et_pb_widget_area_right clearfix et_pb_bg_layout_light">
									<div dangerouslySetInnerHTML={{__html: self.props.sidebar}}></div>
								</div>
							</div> 

						</div>
					</div>

					<div dangerouslySetInnerHTML={{__html: self.props.footer}}></div>

    			</div>

    		</article>

    	</div>
      
    );

  },

});



											