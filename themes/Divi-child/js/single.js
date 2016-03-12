'use strict';

/**
 * Rendu d'un single au sens single.php de WP
 *
 */
var Post = React.createClass({
	displayName: 'Post',

	getInitialState: function getInitialState() {
		var self = this;

		var isTypeEvent = self.props.is_event;
		var noEndDate = typeof self.props.dates.end_date == 'undefined' || self.props.dates.end_date == '';

		return {
			startDate: isTypeEvent ? moment(self.props.dates.start_date, 'YYYY-MM-DD HH:mm:ss') : '',
			endDate: isTypeEvent ? moment(self.props.dates.end_date, 'YYYY-MM-DD HH:mm:ss') : '',
			singleDay: isTypeEvent && noEndDate || isTypeEvent && self.props.dates.start_date.split(' ')[0] === self.props.dates.end_date.split(' ')[0]
		};
	},

	render: function render() {

		var self = this;

		post_id = "post-" + self.props.ID;

		return React.createElement(
			'div',
			null,
			React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.single_top } }),
			React.createElement(
				'article',
				{ id: post_id, className: self.props.post_class },
				React.createElement(
					'div',
					{ className: 'entry-content' },
					React.createElement(
						'div',
						{ className: 'et_pb_section et_section_specialty' },
						React.createElement(
							'div',
							{ className: 'et_pb_row' },
							React.createElement(
								'div',
								{ className: 'et_pb_column et_pb_column_3_4' },
								React.createElement(
									'div',
									{ className: 'et_pb_row_inner' },
									React.createElement('div', { className: 'et_pb_column et_pb_column_3_8 et_pb_column_inner', dangerouslySetInnerHTML: { __html: self.props.html_thumb } }),
									React.createElement(
										'div',
										{ className: 'et_pb_column et_pb_column_3_8 et_pb_column_inner' },
										React.createElement(
											'div',
											{ className: 'et_pb_text et_pb_bg_layout_light et_pb_text_align_left' },
											React.createElement('div', { id: 'voteComponent' }),
											React.createElement('h1', { dangerouslySetInnerHTML: { __html: self.props.title } }),
											React.createElement('span', { dangerouslySetInnerHTML: { __html: self.props.meta } })
										),
										self.props.has_location && React.createElement(
											'div',
											null,
											React.createElement(
												'div',
												{ className: 'et_pb_text et_pb_bg_layout_light et_pb_text_align_left' },
												React.createElement(
													'p',
													{ className: 'location' },
													React.createElement('i', { className: 'fa fa-map-marker' }),
													self.props.location.location_address
												),
												React.createElement(
													'p',
													{ className: 'location' },
													React.createElement('i', { className: 'fa fa-phone' }),
													self.props.location.location_phone_number
												),
												React.createElement(
													'p',
													{ className: 'location' },
													React.createElement('i', { className: 'fa fa-tablet' }),
													React.createElement(
														'a',
														{ target: '_blank', href: self.props.location.location_website },
														'Visiter le site web'
													)
												)
											),
											React.createElement('hr', { className: 'et_pb_space' })
										),
										self.props.is_event && React.createElement(
											'div',
											{ className: 'et_pb_text et_pb_bg_layout_light et_pb_text_align_left' },
											React.createElement(
												'p',
												{ className: 'location font-2x' },
												React.createElement('i', { className: 'fa fa-calendar' }),
												self.state.singleDay && React.createElement(
													'span',
													null,
													'Le ',
													moment(self.state.startDate).format('DD MMM')
												),
												!self.state.singleDay && React.createElement(
													'span',
													null,
													'Du ',
													moment(self.state.startDate).format('DD MMM'),
													' au ',
													moment(self.state.endDate).format('DD MMM')
												)
											)
										),
										React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.social_sharing } })
									)
								),
								React.createElement(
									'div',
									{ className: 'et_pb_row_inner' },
									React.createElement(
										'div',
										{ className: 'et_pb_column et_pb_column_4_4 et_pb_column_inner' },
										React.createElement(
											'div',
											{ className: 'et_pb_text et_pb_bg_layout_light et_pb_text_align_justify' },
											self.props.pub !== '' && React.createElement(
												'div',
												{ className: 'post_ad ad', 'data-content': 'Publicite' },
												React.createElement('span', { dangerouslySetInnerHTML: { __html: self.props.pub } })
											),
											React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.content } }),
											React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.post_format_content }, className: 'post_format_block' }),
											React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.link_pages } })
										)
									)
								),
								React.createElement(
									'div',
									{ className: 'et_pb_row_inner post_inner_content' },
									React.createElement(
										'div',
										{ className: 'et_pb_column et_pb_column_4_4 et_pb_column_inner' },
										self.props.has_location && React.createElement(
											'div',
											{ className: 'et_pb_tabs' },
											React.createElement(
												'ul',
												{ className: 'et_pb_tabs_controls clearfix' },
												React.createElement(
													'li',
													{ className: 'et_pb_tab_active' },
													React.createElement(
														'strong',
														null,
														self.props.location.location_name
													)
												)
											),
											React.createElement(
												'div',
												{ className: 'et_pb_all_tabs' },
												React.createElement(
													'div',
													{ className: 'et_pb_tab clearfix et_pb_active_content' },
													React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.map } })
												)
											)
										),
										self.props.is_ad && React.createElement(
											'div',
											{ className: 'et-single-post-ad' },
											self.props.adsense !== '' && React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.adsense } }),
											self.props.adimg_img !== '' && React.createElement(
												'a',
												{ href: self.props.adimg_url },
												React.createElement('img', { src: self.props.adimg_img, alt: '468 ad', className: 'foursixeight' })
											)
										),
										React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.comments } }),
										React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.single_bottom } })
									)
								)
							),
							React.createElement(
								'div',
								{ className: 'et_pb_column et_pb_column_1_4' },
								React.createElement(
									'div',
									{ className: 'et_pb_widget_area et_pb_widget_area_right clearfix et_pb_bg_layout_light' },
									React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.sidebar } })
								)
							)
						)
					),
					React.createElement('div', { dangerouslySetInnerHTML: { __html: self.props.footer } })
				)
			)
		);
	}

});
