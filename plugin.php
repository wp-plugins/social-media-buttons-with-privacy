<?php
/*
Plugin Name: Social Media with privacy
Plugin URI: https://tas2580.net/downloads/download-1.html
Description: Adds Social Media Bottons to each post
Version: 1.1.0
Author: tas2580
Author URI: https://tas2580.net
License: GPLv2
*/


/*
*
* Output backend
*
*/


add_action('admin_menu', 'tas2580_smb_add_admin_menu');
add_action('admin_init', 'tas2580_smb_settings_init');


function tas2580_smb_add_admin_menu()
{ 
	add_options_page('Social Media Buttons', 'Social Media Buttons', 'manage_options', 'social_media_buttons', 'tas2580_smb_options_page');
}


function tas2580_smb_settings_init()
{ 
	register_setting('pluginPage', 'tas2580_smb_settings');
	add_settings_section('tas2580_smb_settings', __('Settings', 'wordpress'), 'tas2580_smb_settings_section_callback', 'pluginPage');
	add_settings_field('tas2580_smb_platforms', __('Chose platforms', 'wordpress'), 'tas2580_smb_platforms_render', 'pluginPage', 'tas2580_smb_settings');
	add_settings_field('tas2580_smb_cachetime', __('Cachetime', 'wordpress'), 'tas2580_smb_cachetime_render', 'pluginPage', 'tas2580_smb_settings');

}

function tas2580_smb_cachetime_render() 
{
	$options = get_option('tas2580_smb_settings');
	echo '<input type="number" min="0" class="small-text" name="tas2580_smb_settings[cachetime]" value="' . $options['cachetime'] . '" />';
	?>
	<select name="tas2580_smb_settings[cachemultiplicator]">
		<option value="1" <?php selected($options['cachemultiplicator'], 1); ?>>Seconds</option>
		<option value="60" <?php selected($options['cachemultiplicator'], 60); ?>>Minutes</option>
		<option value="3600" <?php selected($options['cachemultiplicator'], 3600); ?>>Hours</option>
	</select>
<?php
}

function tas2580_smb_platforms_render()
{ 
	$options = get_option('tas2580_smb_settings');
	echo '<p><label for="facebook"><input type="checkbox" name="tas2580_smb_settings[facebook]" value="1"';
 	checked($options['facebook'], 1);
	echo ' /> Facebook</label></p>';

	echo '<p><label for="twitter"><input type="checkbox" name="tas2580_smb_settings[twitter]" value="1"';
	checked($options['twitter'], 1);
	echo ' /> Twitter</label></p>';

	echo '<p><label for="google"><input type="checkbox" name="tas2580_smb_settings[google]" value="1"';
	checked($options['google'], 1);
	echo ' /> Google Plus</label></p>';

	echo '<p><label for="linkedin"><input type="checkbox" name="tas2580_smb_settings[linkedin]" value="1"';
	checked($options['linkedin'], 1);
	echo ' /> Linkedin</label></p>';
}


function tas2580_smb_settings_section_callback()
{ 
	echo __('Select the platforms you want to use and set the cache time.', 'wordpress');
}



function tas2580_smb_options_page()
{
	echo'<form action="options.php" method="post"><h2>Social Media Buttons</h2>';
	settings_fields( 'pluginPage' );
	do_settings_sections( 'pluginPage' );
	submit_button();
	echo '</form>';
}

/*
*
* Output frontend
*
*/

add_filter('the_content', 'tas2580_social_buttons');
wp_enqueue_style('social_media', plugins_url('css/style.css',__FILE__), array(), 1.0, 'screen');

// Output Buttons to content
if(!function_exists('tas2580_social_buttons'))
{
	function tas2580_social_buttons($content)
	{
		$url = urlencode(get_permalink($post->ID));
		$title = get_the_title($post->ID);
		$shares = tas2580_get_share_count($url);
		$options = get_option('tas2580_smb_settings');

		$buttons = '<strong>Social Media</strong><br /><script>function open_socialmedia(url){window.open(url,\'\',\'width=500, height=600\');return false;}</script>';

		if(isset($options['facebook']) && ($options['facebook'] == 1))
		{
			$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://www.facebook.com/sharer/sharer.php?u=' . $url . '\');" title="Share on Facebook" class="socialmedia1">&nbsp;</a><span class="social_count">' . (int) $shares['facebook'] . '</span>';
		}
		if(isset($options['twitter']) && ($options['twitter'] == 1))
		{
			$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://twitter.com/intent/tweet?text=' . $title . '&amp;url=' . $url . '\');" title="Share on Twitter" class="socialmedia2">&nbsp;</a><span class="social_count">' . (int) $shares['twitter'] . '</span>';
		}
		if(isset($options['google']) && ($options['google'] == 1))
		{
			$buttons .= '<a href="#" onclick="return open_socialmedia(\'https://plus.google.com/share?url=' . $url . '&amp;title=' . $title . '\');" title="Share on Google+" class="socialmedia3">&nbsp;</a><span class="social_count">' . (int) $shares['google'] . '</span>';
		}
		if(isset($options['linkedin']) && ($options['linkedin'] == 1))
		{
			$buttons .= '<a href="#" onclick="return open_socialmedia(\'http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $url . '&amp;title=' . $title . '\');" title="Share on Linkedin" class="socialmedia4">&nbsp;</a><span class="social_count">' . (int) $shares['linkedin'] . '</span>';
		}

		$content = $content . $buttons;
		return $content;
	}
}

// Count shares on social media platforms
if(!function_exists('tas2580_get_share_count'))
{
	function tas2580_get_share_count($url)
	{

		$options = get_option('tas2580_smb_settings');
		$shares = array();
		$cachetime = ((int) $options['cachetime'] * (int) $options['cachemultiplicator']);
		$cache_file = plugin_dir_path(__FILE__) . 'cache/' . md5($url) . '.json';
		$filetime = file_exists($cache_file) ? filemtime($cache_file) : 0;
		
		if(($filetime == 0) || ($filetime < (time() - $cachetime)))
		{
			if(isset($options['facebook']) && ($options['facebook'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents("https://graph.facebook.com/" . $url), true))
				{
					$shares['facebook'] = (int) $pageinfo['shares'];
				}
			}
			if(isset($options['twitter']) && ($options['twitter'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents("https://cdn.api.twitter.com/1/urls/count.json?url=" . $url), true))
				{
					$shares['twitter'] = (int) $pageinfo['count'];
				}
			}
			if(isset($options['google']) && ($options['google'] == 1))
			{
				if($data = @file_get_contents("https://plusone.google.com/_/+1/fastbutton?url=" . $url))
				{
					preg_match('#<div id="aggregateCount" class="Oy">([0-9]+)</div>#s', $data, $matches);
					$shares['google'] = (int) $matches[1];
				}
			}
			if(isset($options['linkedin']) && ($options['linkedin'] == 1))
			{
				if($pageinfo = json_decode(@file_get_contents('http://www.linkedin.com/countserv/count/share?url=' . $url . '&format=json'), true))
				{
					$shares['linkedin'] = (int) $pageinfo['count'];
				}
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