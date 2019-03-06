<?php
/*
Plugin Name: WordPress PWAMP Online
Plugin URI:  https://flexplat.com/wordpress-pwampo/
Description: Transcodes WordPress into both first load cache-enabled of PWA and lightning fast load time of AMP style.
Version:     2.3.0
Author:      Rickey Gu
Author URI:  https://flexplat.com
Text Domain: pwampo
Domain Path: /languages
*/

if ( !defined('ABSPATH') )
{
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

require_once plugin_dir_path(__FILE__) . 'lib/detection.php';
require_once plugin_dir_path(__FILE__) . 'theme/transcoding.php';


class PWAMPO
{
	private $page = '';


	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	public function init()
	{
		$home_url = home_url();
		$parts = parse_url($home_url);
		$page_url = $parts['scheme'] . '://' . $parts['host'] . add_query_arg();

		$base = str_replace(array('/', '.'), array('\/', '\.'), $home_url);
		if ( preg_match('/^' . $base . '\/\??manifest\.webmanifest$/im', $page_url) )
		{
			$plugin_dir_url = plugin_dir_url(__FILE__);

			header('Content-Type: application/x-web-app-manifest+json', true);
			echo '{
	"name": "' . get_bloginfo('name') . ' &#8211; ' . get_bloginfo('description') . '",
	"short_name": "' . get_bloginfo('name') . '",
	"start_url": "' . $home_url . '",
	"display": "standalone",
	"theme_color": "#ffffff",
	"background_color": "#ffffff",
	"icons": [{
		"src": ".' . str_replace($home_url, '', $plugin_dir_url) . 'lib/manifest/pwamp-logo-512.png",
		"sizes": "512x512",
		"type": "image/png"
	}]
}';

			exit();
		}
		elseif ( preg_match('/^' . $base . '\/\??pwamp-sw-html$/im', $page_url) )
		{
			$permalink = !empty(get_option('permalink_structure')) ? 'pretty' : 'ugly';

			header('Content-Type: text/html; charset=utf-8', true);
			echo '<!doctype html>
<html>
<head>
<title>Installing service worker...</title>
<script type=\'text/javascript\'>
	var swsource = \'' . $home_url . '/' . ( $permalink != 'ugly' ? 'pwamp-sw-js' : '?pwamp-sw-js' ) . '\';
	if ( \'serviceWorker\' in navigator ) {
		navigator.serviceWorker.register(swsource).then(function(reg) {
			console.log(\'ServiceWorker scope: \', reg.scope);
		}).catch(function(err) {
			console.log(\'ServiceWorker registration failed: \', err);
		});
	};
</script>
</head>
<body>
</body>
</html>';

			exit();
		}
		elseif ( preg_match('/^' . $base . '\/\??pwamp-sw-js$/im', $page_url) )
		{
			$plugin_dir_url = plugin_dir_url(__FILE__);

			header('Content-Type: application/javascript', true);
			echo 'importScripts(\'.' . str_replace($home_url, '', $plugin_dir_url) . 'lib/sw-toolbox/sw-toolbox.js\');
toolbox.router.default = toolbox.cacheFirst;
self.addEventListener(\'install\', function(event) {
	console.log(\'SW: Installing service worker\');
});';

			exit();
		}
		elseif ( preg_match('/^' . $base . '\/\?pwamp-viewport-width=(\d+)$/im', $page_url, $matches) )
		{
			$viewport_width = $matches[1];

			setcookie('pwamp-viewport-width', $viewport_width, time()+60*60*24*365, COOKIEPATH, COOKIE_DOMAIN);

			if ( is_plugin_active('adaptive-images/adaptive-images.php') )
			{
				setcookie('resolution', $viewport_width . ',1', 0, '/');
			}

			exit();
		}
	}


	public function get_amphtml()
	{
		$home_url = home_url();
		$parts = parse_url($home_url);

		$args = array('desktop' => false, 'amp' => '');
		$amphtml = $parts['scheme'] . '://' . $parts['host'] . add_query_arg($args);
		$amphtml = htmlspecialchars($amphtml);

		echo '<link rel="amphtml" href="' . $amphtml . '" />' . "\n";
	}

	private function get_canonical()
	{
		$home_url = home_url();
		$parts = parse_url($home_url);

		$args = array('amp' => false, 'desktop' => '');
		$canonical = $parts['scheme'] . '://' . $parts['host'] . add_query_arg($args);
		$canonical = htmlspecialchars($canonical);

		return $canonical;
	}


	private function get_page_type()
	{
		global $wp_query;

		$page_type = '';
		if ( $wp_query->is_page )
		{
			$page_type = is_front_page() ? 'front' : 'page';
		}
		elseif ( $wp_query->is_home )
		{
			$page_type = 'home';
		}
		elseif ( $wp_query->is_single )
		{
			$page_type = ( $wp_query->is_attachment ) ? 'attachment' : 'single';
		}
		elseif ( $wp_query->is_category )
		{
			$page_type = 'category';
		}
		elseif ( $wp_query->is_tag )
		{
			$page_type = 'tag';
		}
		elseif ( $wp_query->is_tax )
		{
			$page_type = 'tax';
		}
		elseif ( $wp_query->is_archive )
		{
			if ( $wp_query->is_day )
			{
				$page_type = 'day';
			}
			elseif ( $wp_query->is_month )
			{
				$page_type = 'month';
			}
			elseif ( $wp_query->is_year )
			{
				$page_type = 'year';
			}
			elseif ( $wp_query->is_author )
			{
				$page_type = 'author';
			}
			else
			{
				$page_type = 'archive';
			}
		}
		elseif ( $wp_query->is_search )
		{
			$page_type = 'search';
		}
		elseif ( $wp_query->is_404 )
		{
			$page_type = 'notfound';
		}

		return $page_type;
	}


	private function get_device()
	{
		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$accept = !empty($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
		$profile = !empty($_SERVER['HTTP_PROFILE']) ? $_SERVER['HTTP_PROFILE'] : '';

		$detection = new MO_Detection();

		$device = $detection->get_device($user_agent, $accept, $profile);

		return $device;
	}

	private function transcode_page()
	{
		$page = preg_replace('/^[\s\t]*<style type="[^"]+" id="[^"]+"><\/style>$/im', '', $this->page);
		$home_url = home_url();
		$data = array(
			'page_type' => $this->get_page_type(),
			'themes_url' => get_template_directory_uri(),
			'plugins_url' => plugins_url(),
			'viewport_width' => !empty($_COOKIE['pwamp-viewport-width']) ? $_COOKIE['pwamp-viewport-width'] : '',
			'permalink' => !empty(get_option('permalink_structure')) ? 'pretty' : 'ugly',
			'page_url' => ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI],
			'canonical' => $this->get_canonical()
		);

		$transcoding = new PWAMPO_Transcoding($home_url, $data);

		$transcoding->transcode($page);

		return $page;
	}


	private function catch_page_callback($page)
	{
		$this->page .= $page;
	}

	public function after_setup_theme()
	{
		if ( empty($_COOKIE['pwamp-message']) )
		{
			ob_start(array($this, 'catch_page_callback'));

			return;
		}


		$time = time();

		$message = $_COOKIE['pwamp-message'];
		setcookie('pwamp-message', '', $time-1, COOKIEPATH, COOKIE_DOMAIN);

		$title = '';
		if ( !empty($_COOKIE['pwamp-title']) )
		{
			$title = $_COOKIE['pwamp-title'];
			setcookie('pwamp-title', '', $time-1, COOKIEPATH, COOKIE_DOMAIN);
		}

		$args = array();
		if ( !empty($_COOKIE['pwamp-args']) )
		{
			$args = json_decode(stripslashes($_COOKIE['pwamp-args']));
			setcookie('pwamp-args', '', $time-1, COOKIEPATH, COOKIE_DOMAIN);
		}

		_default_wp_die_handler($message, $title, $args);
	}

	public function shutdown()
	{
		$page = $this->transcode_page();
		if ( empty($page) )
		{
			echo $this->page;

			return;
		}

		echo $page;
	}


	private function json_redirect($redirection)
	{
		$home_url = home_url();
		$parts = parse_url($home_url);
		$host_url = $parts['scheme'] . '://' . $parts['host'];

		header('Content-type: application/json');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: *.ampproject.org');
		header('Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin');
		header('AMP-Access-Control-Allow-Source-Origin: ' . $host_url);
		header('AMP-Redirect-To: ' . $redirection);

		$output = [];
		echo json_encode($output);

		exit();
	}

	public function comment_post_redirect($location, $comment)
	{
		$status = 302;

		$location = wp_sanitize_redirect($location);
		$location = wp_validate_redirect($location, apply_filters('wp_safe_redirect_fallback', admin_url(), $status));

		$location = apply_filters('wp_redirect', $location, $status);
		$status = apply_filters('wp_redirect_status', $status, $location);

		$this->json_redirect($location);
	}

	public function die_handler($message, $title = '', $args = array())
	{
		if ( $title !== 'Comment Submission Failure' )
		{
			_default_wp_die_handler($message, $title, $args);

			return;
		}


		$time = time();

		setcookie('pwamp-message', $message, $time+60, COOKIEPATH, COOKIE_DOMAIN);

		if ( !empty($title) )
		{
			setcookie('pwamp-title', $title, $time+60, COOKIEPATH, COOKIE_DOMAIN);
		}
		else
		{
			setcookie('pwamp-title', '', $time-1, COOKIEPATH, COOKIE_DOMAIN);
		}

		if ( !empty($args) )
		{
			setcookie('pwamp-args', json_encode($args), $time+60, COOKIEPATH, COOKIE_DOMAIN);
		}
		else
		{
			setcookie('pwamp-args', '', $time-1, COOKIEPATH, COOKIE_DOMAIN);
		}

		$redirection = home_url();
		$this->json_redirect($redirection);
	}

	public function wp_die_handler($function)
	{
		$function = array($this, 'die_handler');

		return $function;
	}


	public function main()
	{
		if ( is_admin() || $GLOBALS['pagenow'] === 'wp-login.php' )
		{
			return;
		}

		if ( is_plugin_active('pwampp/pwampp.php') )
		{
			return;
		}


		add_action('init', array($this, 'init'));


		if ( isset($_GET['amp']) || isset($_GET['desktop']) )
		{
			$device = !isset($_GET['amp']) ? 'desktop' : 'mobile';
		}
		elseif ( !empty($_COOKIE['pwamp-style']) )
		{
			$device = $_COOKIE['pwamp-style'] != 'mobile' ? 'desktop' : 'mobile';
		}
		else
		{
			$device = $this->get_device();
			if ( empty($device) )
			{
				return;
			}

			$device = ( $device == 'desktop' || $device == 'bot' ) ? 'desktop' : 'mobile';
		}

		setcookie('pwamp-style', $device, time()+60*60*24*365, COOKIEPATH, COOKIE_DOMAIN);

		if ( $device != 'mobile' )
		{
			add_action('wp_head', array($this, 'get_amphtml'), 0);

			return;
		}


		if ( is_plugin_active('adaptive-images/adaptive-images.php') )
		{
			if ( !empty($_COOKIE['pwamp-viewport-width']) )
			{
				$viewport_width = $_COOKIE['pwamp-viewport-width'];
				setcookie('resolution', $viewport_width . ',1', 0, '/');
			}
		}


		add_action('after_setup_theme', array($this, 'after_setup_theme'));
		add_action('shutdown', array($this, 'shutdown'));


		add_filter('comment_post_redirect', array($this, 'comment_post_redirect'), 10, 2);
		add_filter('wp_die_handler', array($this, 'wp_die_handler'), 10, 1);


		add_filter('show_admin_bar', '__return_false');
	}
}


function pwampo_main()
{
	$pwamp = new PWAMPO();

	$pwamp->main();
}
add_action('plugins_loaded', 'pwampo_main', 1);
