<?php
/*
Plugin Name: Social Media with privacy
Plugin URI: https://tas2580.net
Description: Adds Social Media Bottons to each post
Version: 1.0
Author: tas2580
Author URI: https://tas2580.net
License: GPLv2
*/

add_filter('the_content', 'tas2580_social_buttons');
wp_enqueue_style('social_media', plugins_url('css/style.css',__FILE__), array(), 1.0, 'screen');

if(!function_exists('tas2580_social_buttons'))
{
	function tas2580_social_buttons($content)
	{
		$url = get_permalink($post->ID);
		$title = get_the_title($post->ID);
		$shares = tas2580_get_share_count($url);

		$buttons = '<strong>Social Media</strong><br /><script>function open_socialmedia(url){window.open(url,\'\',\'width=500, height=600\');return false;}</script>';
		$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://www.facebook.com/sharer/sharer.php?u=' . $url . '\');" title="Auf Facebook teilen" class="socialmedia1">&nbsp;</a><span class="social_count">' . $shares['facebook'] . '</span>';
		$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://twitter.com/intent/tweet?text=' . $title . '&amp;url=' . $url . '\');" title="Auf Twitter teilen" class="socialmedia2">&nbsp;</a><span class="social_count">' . $shares['twitter'] . '</span>';
		$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://plus.google.com/share?url=' . $url . '&amp;title=' . $title . '\');" title="Auf Google+ teilen" class="socialmedia3">&nbsp;</a><span class="social_count">' . $shares['google'] . '</span>';
		$buttons .= '<a href="#" onclick="return open_socialmedia(\'http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $url . '&amp;title=' . $title . '\');" title="Auf Linkedin teilen" class="socialmedia4">&nbsp;</a><span class="social_count">' . $shares['linkedin'] . '</span>';

		$content .= $buttons;
		return $content;
	}
}

if(!function_exists('tas2580_get_share_count'))
{
	function tas2580_get_share_count($url)
	{
		$shares = array();
		$cachetime = 3600;
		$cache_file = plugin_dir_path(__FILE__) . 'cache/' . md5($url) . '.json';
		$filetime = file_exists($cache_file) ? filemtime($cache_file) : 0;

		if(($filetime == 0) || ($filetime < (time() - $cachetime)))
		{
			if($pageinfo = json_decode(@file_get_contents("https://graph.facebook.com/" . $url), true))
			{
				$shares['facebook'] = (int) $pageinfo['shares'];
			}
			if($pageinfo = json_decode(@file_get_contents("https://cdn.api.twitter.com/1/urls/count.json?url=" . $url), true))
			{
				$shares['twitter'] = (int) $pageinfo['count'];
			}
			if($data = @file_get_contents("https://plusone.google.com/_/+1/fastbutton?url=" . $url))
			{
				preg_match('#<div id="aggregateCount" class="Oy">([0-9]+)</div>#s', $data, $matches);
				$shares['google'] = (int) $matches[1];
			}
			if($pageinfo = json_decode(@file_get_contents('http://www.linkedin.com/countserv/count/share?url=' . $url . '&format=json'), true))
			{
				$shares['linkedin'] = (int) $pageinfo['count'];
			}
			$json = json_encode($shares);
			$handle = fopen($cache_file, 'w');
			fwrite($handle, $json);
			fclose($handle);
		}
		else
		{
			$json = file_get_contents($cache_file);
			$shares = json_decode($json, true);
		}
		return $shares;
	}
}
