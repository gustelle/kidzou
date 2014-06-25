<?php


/**
* insere une URL dans un kz_author
**/
add_shortcode('author_url', 'kz_author_url');
function kz_author_url($atts, $content = null) {

	extract(shortcode_atts(array(
		"label" => '',
		"url" => ''
	), $atts));


	$content = et_content_helper($content);
	$prefix  = ($label <> '') ? $label." : " : "";
	$url    = ($url <> '') ? $url : "#";

	$output = "
		<span>
			{$prefix}<a href='{$url}' target='_blank'>{$content}</a>
		</span><br/>
		<!-- /author_url -->";

	return $output;
}

/**
* insere une desc dans un author
**/
add_shortcode('author_p', 'kz_author_p');
function kz_author_p($atts, $content = null) {

	$content = et_content_helper($content);

	$output = "<span>{$content}</span><br/><!-- /author_p -->";

	return $output;
}

?>