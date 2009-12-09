<?php

// Minimalist HTML framework

if ( ! function_exists('html') ) :
function html($tag, $content = '', $indent = '') {
	list($closing) = explode(' ', $tag);

	return "{$indent}<{$tag}>{$content}{$indent}</{$closing}>";
}
endif;

if ( ! function_exists('html_link') ) :
function html_link($url, $title = '') {
	if ( empty($title) )
		$title = $url;

	return sprintf("<a href='%s'>%s</a>", $url, $title);
}
endif;
