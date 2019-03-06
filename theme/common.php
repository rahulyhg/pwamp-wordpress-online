<?php
if ( !defined('ABSPATH') )
{
	exit;
}

class PWAMPP_TranscodingCommon
{
	protected $style;
	protected $home_url;
	protected $page_type;
	protected $themes_url;
	protected $plugins_url;
	protected $viewport_width;
	protected $permalink;
	protected $page_url;
	protected $canonical;


	public function __construct($home_url, $data)
	{
		$this->style = '';

		$this->home_url = $home_url;

		if ( !empty($data['page_type']) && is_string($data['page_type']) )
		{
			$this->page_type = $data['page_type'];
		}
		else
		{
			$this->page_type = '';
		}

		if ( !empty($data['themes_url']) && is_string($data['themes_url']) )
		{
			$this->themes_url = $data['themes_url'];
		}
		else
		{
			$this->themes_url = '';
		}

		if ( !empty($data['plugins_url']) && is_string($data['plugins_url']) )
		{
			$this->plugins_url = $data['plugins_url'];
		}
		else
		{
			$this->plugins_url = '';
		}

		if ( !empty($data['viewport_width']) && is_string($data['viewport_width']) )
		{
			$this->viewport_width = (int)$data['viewport_width'];
		}
		else
		{
			$this->viewport_width = 0;
		}

		if ( !empty($data['permalink']) && is_string($data['permalink']) )
		{
			$this->permalink = $data['permalink'];
		}
		else
		{
			$this->permalink = 'pretty';
		}

		if ( !empty($data['page_url']) && is_string($data['page_url']) )
		{
			$this->page_url = $data['page_url'];
		}
		else
		{
			$this->page_url = $home_url . '/';
		}

		if ( !empty($data['canonical']) && is_string($data['canonical']) )
		{
			$this->canonical = $data['canonical'];
		}
		else
		{
			$canonical = preg_replace('/((\?)|(&(amp;)?))(amp|desktop)$/im', '', $this->page_url);
			$canonical .= ( strpos($canonical, '?') !== false ) ? '&desktop' : '?desktop';
			$this->canonical = htmlspecialchars($canonical);
		}
	}

	public function __destruct()
	{
	}


	protected function get_style()
	{
		if ( !empty($this->style_list[$this->page_type]) )
		{
			$this->style = $this->style_list[$this->page_type];
		}
		elseif ( !empty($this->style_list['default']) )
		{
			$this->style = $this->style_list['default'];
		}
		else
		{
			$this->style = '';
		}
	}

	protected function minify_media()
	{
		if ( $this->viewport_width == 0 )
		{
			return;
		}

		preg_match_all('/@media\b([^{]+)\{([\s\S]+?\})\s*\}/', $this->style, $matches);
		foreach ( $matches[1] as $key => $value )
		{
			unset($min_width);
			if ( preg_match('/min-width:\s?(\d+)px/i', $value, $matches2) )
			{
				$min_width = (int)$matches2[1];
			}
			elseif ( preg_match('/min-width:\s?(\d+(\.\d+)?)em/i', $value, $matches2) )
			{
				$min_width = (int)$matches2[1] * 16;
			}

			unset($max_width);
			if ( preg_match('/max-width:\s?(\d+)px/i', $value, $matches2) )
			{
				$max_width = (int)$matches2[1];
			}
			elseif ( preg_match('/max-width:\s?(\d+(\.\d+)?)em/i', $value, $matches2) )
			{
				$max_width = (int)$matches2[1] * 16;
			}

			$value = str_replace(array('(', ')', '.'), array('\(', '\)', '\.'), $value);
			if ( isset($min_width) && isset($max_width) )
			{
				if ( $this->viewport_width < $min_width || $this->viewport_width > $max_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			elseif ( isset($min_width) )
			{
				if ( $this->viewport_width < $min_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			elseif ( isset($max_width) )
			{
				if ( $this->viewport_width > $max_width )
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
				}
				else
				{
					$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', $matches[2][$key], $this->style, 1);
				}
			}
			else
			{
				$this->style = preg_replace('/@media\b' . $value . '\{([\s\S]+?\})\s*\}/', '', $this->style, 1);
			}
		}
	}

	protected function update_font_face()
	{
		$pattern = '/(@font-face{.*src:url\(data:application\/font-.+;charset=utf-8;base64,.+\).*})/iU';
		if ( preg_match_all($pattern, $this->style, $matches) )
		{
			$font_face = '';

			foreach ( $matches[1] as $value )
			{
				$font_face .= $value;
				$this->style = preg_replace($pattern, '', $this->style, 1);
			}

			$this->style .= $font_face;
		}
	}

	protected function minify_css($css, $id = '')
	{
		$css = !empty($id) ? $id . '{' . $css . '}' : $css;

		$css = preg_replace('/\bamp-(audio|iframe|img|video)\b/i', '${1}', $css);
		$css = preg_replace('/\b(audio|iframe|img|video)\b/i', 'amp-${1}', $css);

		$css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $css);
		$css = preg_replace('/\s*!important/i', '', $css);
		$css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '   ', '    '), '', $css);
		$css = str_replace(array(' {', '{ ', ': ', ', ', '; ', ' }', ';}'), array('{', '{', ':', ',', ';', '}', '}'), $css);

		if ( preg_match('/{}$/im', $css) )
		{
			return;
		}

		$this->style .= $css;
	}


	protected function update_image(&$page)
	{
		foreach ( $this->image_list as $key => $value )
		{
			if ( preg_match('/^https?:\/\//im', $key) )
			{
				$pattern = str_replace(array('?', '(', ')', 'https://www.', 'https://', 'http://', '/', '.'), array('\?', '\(', '\)', 'https://', 'http://', 'https?://(www.)?', '\/', '\.'), $key);
				$page = preg_replace('/<img\b([^>]*)( (data-)?src\s*=\s*("|\')' . $pattern . '("|\'))([^>]*)\s*?\/?>/iU', '<img${1}${2}' . $value . '${7} />', $page);
			}
			else
			{
				$pattern = str_replace(array('?', '(', ')', '/', '.'), array('\?', '\(', '\)', '\/', '\.'), $key);
				$page = preg_replace('/<img\b([^>]*)( (data-)?src\s*=\s*("|\')' . $pattern . '("|\'))([^>]*)\s*?\/?>/iU', '<img${1}${2}' . $value . '${6} />', $page);

				$pattern = str_replace(array('?', '(', ')', 'https://www.', 'https://', 'http://', '/', '.'), array('\?', '\(', '\)', 'https://', 'http://', 'https?://(www.)?', '\/', '\.'), $this->home_url . $key);
				$page = preg_replace('/<img\b([^>]*)( (data-)?src\s*=\s*("|\')' . $pattern . '("|\'))([^>]*)\s*?\/?>/iU', '<img${1}${2}' . $value . '${7} />', $page);
			}
		}
	}


	protected function transcode_html(&$page)
	{
		$page = preg_replace('/(<img class="align(left|right) size-thumbnail wp-image-827" title="Camera" src="[^"]+" alt="" width="160")( srcset="[^"]+" sizes="[^"]+" \/>)/i', '${1} height="120"${3}', $page);


		$page = preg_replace('/<!--.*-->/isU', '', $page);

		$page = preg_replace('/<!DOCTYPE\b[^>]*>/i', '<!doctype html>', $page, 1);

		/*
			<a></a>
		*/
		// Invalid URL protocol 'hhttps:' for attribute 'href' in tag 'a'.
		$page = preg_replace('/<a\b([^>]*) href="hhttps:\/\/([^"]*)"([^>]*)>/i', '<a${1} href="https://${2}"${3}>', $page);

		// The attribute 'alt' may not appear in tag 'a'.
		$page = preg_replace('/<a\b([^>]*) alt=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*?>/iU', '<a${1}${5}>', $page);


		/*
			<area />
		*/
		// The tag 'area' is disallowed.
		$page = preg_replace('/<area\b([^>]*)\s?\/?>/iU', '', $page);


		// The tag 'audio' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-audio'?
		$page = preg_replace('/<audio\b/i', '<amp-audio', $page);


		/*
			Service Workers
		*/
		$serviceworker = '<amp-install-serviceworker
	src="' . $this->home_url . '/' . ( $this->permalink != 'ugly' ? 'pwamp-sw-js' : '?pwamp-sw-js' ) . '"
	data-iframe-src="' . $this->home_url . '/' . ( $this->permalink != 'ugly' ? 'pwamp-sw-html' : '?pwamp-sw-html' ) . '"
	layout="nodisplay">
</amp-install-serviceworker>' . "\n";
		$serviceworker .= empty($this->viewport_width) ? '<amp-pixel src="' . $this->home_url . '/?pwamp-viewport-width=VIEWPORT_WIDTH" layout="nodisplay"></amp-pixel>' . "\n" : '';
		$serviceworker .= '</body>';
		$page = preg_replace('/<\/body>/i', $serviceworker, $page, 1);

		$page = preg_replace('/<icon class="([^"]+)"><\/icon>/i', '<div class="${1}"></div>', $page);


		/*
			<embed />
		*/
		// The tag 'embed' is disallowed.
		$page = preg_replace('/<embed\b([^>]*)\s?\/?>/iU', '', $page);


		/*
			Facebook Pixel Code
		*/
		$pattern = '/<noscript><img height="\d*" width="\d*" style="display:none".+(async=\'\' )?src="https:\/\/www\.facebook\.com\/tr\?id=(\d+)&ev=PageView&noscript=1".+\/><\/noscript>/isU';
		if ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace('/<\/body>/i', '<amp-pixel src="https://www.facebook.com/tr?id=' . $match[2] . '&ev=PageView&noscript=1" layout="nodisplay"></amp-pixel>' . "\n" . '</body>', $page, 1);
			$page = preg_replace($pattern, '', $page);
		}


		/*
			<font></font>
		*/
		// The tag 'font' is disallowed.
		$page = preg_replace('/<font[^>]*>/i', '', $page);
		$page = preg_replace('/<\/font>/i', '', $page);


		/*
			<form></form>
		*/
		// The mandatory attribute 'action' is missing in tag 'FORM [method=GET]'.
		$pattern = '/<form\b([^>]*)\s?>/i';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[1] as $value )
			{
				if ( !preg_match('/ action=(("([^"]*)")|(\'([^\']*)\'))/i', $value) )
				{
					$value .= ' action="' . $this->home_url . '"';
				}
				$page = preg_replace($pattern, '<amp-form' . $value . '>', $page, 1);
			}
		}

		$page = preg_replace('/<amp-form\b([^>]*)>/i', '<form${1}>', $page);

		// The attribute 'action' may not appear in tag 'FORM [method=POST]'.
		$page = preg_replace('/<form\b([^>]*) action=(("([^"]*)")|(\'([^\']*)\'))([^>]*) method=(("post")|(\'post\'))([^>]*)>/i', '<form${1} action-xhr="${4}${6}"${7} method="post"${11}>', $page);
		$page = preg_replace('/<form\b([^>]*) method=(("post")|(\'post\'))([^>]*) action=(("([^"]*)")|(\'([^\']*)\'))([^>]*)>/i', '<form${1} method="post"${5} action-xhr="${8}${10}"${11}>', $page);

		// The mandatory attribute 'target' is missing in tag 'FORM [method=GET]'.
		$page = preg_replace('/<form\b([^>]*)>/i', '<form${1} target="_top">', $page);


		/*
			<head></head>
		*/
		$page = preg_replace('/^[\s\t]*<\/head>/im', '</head>', $page, 1);


		/*
			html
		*/
		// The attribute 'xml:lang' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xml:lang=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<html${1}${5}>', $page);

		// The attribute 'xmlns' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<html${1}${5}>', $page);

		// The attribute 'xmlns:fb' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns:fb=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<html${1}${5}>', $page);

		// The attribute 'xmlns:og' may not appear in tag 'html'.
		$page = preg_replace('/<html\b([^>]*) xmlns:og=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<html${1}${5}>', $page);

		$page = preg_replace('/<html\b([^>]*)>/i', '<html amp${1}>', $page, 1);


		/*
			<iframe></iframe>
		*/
		// The tag 'iframe' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-iframe'?
		$page = preg_replace('/<iframe\b/i', '<amp-iframe', $page);

		// Invalid URL protocol 'http:' for attribute 'src' in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) src=(("http:\/\/([^"]*)")|(\'http:\/\/([^\']*)\'))([^>]*)>/i', '<amp-iframe${1} src="https://${4}${6}"${7}>', $page);

		// The attribute 'align' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) align=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<amp-iframe${1}${5}>', $page);

		// The attribute 'frameborder' in tag 'amp-iframe' is set to the invalid value 'no'.
		$page = preg_replace('/<amp-iframe\b([^>]*) frameborder=(("no")|(\'no\'))([^>]*)>/i', '<amp-iframe${1} frameborder="0"${5}>', $page);

		// The attribute 'name' may not appear in tag 'amp-iframe'.
		$page = preg_replace('/<amp-iframe\b([^>]*) name=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<amp-iframe${1}${5}>', $page);


		/*
			<img/>
		*/
		$page = preg_replace('/<img\b([^>]*) width="100%"([^>]*)\s?\/?>/iU', '<img${1}${2} />', $page);

		$page = preg_replace('/<img\b([^>]*) src=(("[^"]+(data:image\/gif;base64,[^"]+)")|(\'[^\']+(data:image\/gif;base64,[^\']+)\'))([^>]*)\s?\/?>/iU', '<img${1} src="${4}${6}"${7} />', $page);

		// The tag 'img' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-img'?
		$page = preg_replace('/<img\b([^>]*)\s*?\/?>/iU', '<div style="all:revert;display:inline"><amp-img${1}' . ( !preg_match('/ layout=(("[^"]*")|(\'[^\']*\'))/i', '${1}') ? ' layout="intrinsic"' : '' ) . ' /></div>', $page);

		// The attribute 'align' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) align=(("[^"]*")|(\'[^\']*\'))([^>]*)\s?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'async' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) async=(("[^"]*")|(\'[^\']*\'))([^>]*)\s?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'border' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) border=(("[^"]*")|(\'[^\']*\'))([^>]*)\s?\/?>/iU', '<amp-img${1}${5} />', $page);

		// The attribute 'usemap' may not appear in tag 'amp-img'.
		$page = preg_replace('/<amp-img\b([^>]*) usemap=(("[^"]*")|(\'[^\']*\'))([^>]*)\s?\/?>/iU', '<amp-img${1}${5} />', $page);


		/*
			<link>
		*/
		$page = preg_replace('/<link rel="amphtml" href="[^"]+"\s*\/?>/i', '', $page);

		// The tag 'link rel=canonical' appears more than once in the document.
		$page = preg_replace('/<link rel="canonical" href="[^"]+"\s*\/?>/i', '', $page);

		$page = preg_replace('/<link rel=\'dns-prefetch\' href=\'(\/\/[^\']+)\'\s*\/?>/i', '<link rel="dns-prefetch" href="https:${1}">', $page);

		$page = preg_replace('/<link\b([^>]*) rel=(("stylesheet")|(\'stylesheet\'))([^>]*)\s? href=(("(\/\/[^"]+)")|(\'(\/\/[^\']+)\'))([^>]*)\s*?\/?>/iU', '<link${1} rel="stylesheet"${5} href="https:${8}${10}"${11}>', $page);

		// The attribute 'href' in tag 'link rel=stylesheet for fonts' is set to the invalid value...
		$pattern = '/<link\b([^>]*) rel=(("stylesheet")|(\'stylesheet\'))([^>]*) href=(("([^"]+)")|(\'([^\']+)\'))([^>]*)\s*?\/?>/iU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[6] as $key => $value )
			{
				$value = !empty($matches[7][$key]) ? $matches[8][$key] : $matches[10][$key];
				if ( preg_match('/^https:\/\/cloud\.typography\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fast\.fonts\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fonts\.googleapis\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.typekit\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/maxcdn\.bootstrapcdn\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.fontawesome\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} rel="stylesheet"${5} amp-href="' . $value . '"${11}>', $page, 1);
				}
				else
				{
					$page = preg_replace($pattern, '', $page, 1);
				}
			}
		}

		$page = preg_replace('/<link\b([^>]*) rel="stylesheet"([^>]*) amp-href="([^"]+)"([^>]*)>/i', '<link${1} rel="stylesheet"${2} href="${3}"${4}>', $page);

		$pattern = '/<link\b([^>]*) href=(("([^"]+)")|(\'([^\']+)\'))([^>]*) rel=(("stylesheet")|(\'stylesheet\'))([^>]*)\s*?\/?>/iU';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				$value = !empty($matches[3][$key]) ? $matches[4][$key] : $matches[6][$key];
				if ( preg_match('/^https:\/\/cloud\.typography\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fast\.fonts\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/fonts\.googleapis\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.typekit\.net\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/maxcdn\.bootstrapcdn\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				elseif ( preg_match('/^https:\/\/use\.fontawesome\.com\//im', $value) )
				{
					$page = preg_replace($pattern, '<link${1} amp-href="' . $value . '"${7} rel="stylesheet"${11}>', $page, 1);
				}
				else
				{
					$page = preg_replace($pattern, '', $page, 1);
				}
			}
		}

		$page = preg_replace('/<link\b([^>]*) amp-href="([^"]+)"([^>]*) rel="stylesheet"([^>]*)>/i', '<link${1} href="${2}"${3} rel="stylesheet"${4}>', $page);

		// The parent tag of tag 'link rel=stylesheet for fonts' is 'body', but it can only be 'head'.
		$pattern = '/<body([^>]*)>(.*)(<link rel="stylesheet"[^>]*>)(.*)<\/body>/is';
		while ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace($pattern, '<body${1}>${2}${4}</body>', $page);
			$page = preg_replace('/<\/head>/i', $match[3] . "\n" . '</head>', $page);
		}

		$page = preg_replace('/^[\s\t]*<link\b([^>]*)>/im', '<link${1}>', $page);


		/*
			<map></map>
		*/
		// The tag 'map' is disallowed.
		$page = preg_replace('/<map\b[^>]*>.*<\/map>/isU', '', $page);


		/*
			<meta>
		*/
		// The tag 'meta http-equiv=Content-Type' may only appear as a descendant of tag 'head'.
		$pattern = '/<body([^>]*)>(.*)(<meta http-equiv="Content-Type"[^>]*>)(.*)<\/body>/is';
		if ( preg_match($pattern, $page, $match) )
		{
			$page = preg_replace($pattern, '<body${1}>${2}${4}</body>', $page);
			$page = preg_replace('/<head>/i', '<head>' . "\n" . $match[3], $page);
		}

		// The attribute 'content' in tag 'meta http-equiv=Content-Type' is set to the invalid value 'text/html;charset=utf-8'.
		// The mandatory tag 'meta charset=utf-8' is missing or incorrect.
		$pattern = '/<meta http-equiv="Content-Type" content="text\/html;\s?charset=[^"]*"\s*\/?>/i';
		if ( preg_match($pattern, $page) && !preg_match('/<meta charset="utf-8"\s*\/?>/i', $page) )
		{
			$page = preg_replace($pattern, '<meta charset="utf-8">', $page, 1);
		}
		
		if ( preg_match($pattern, $page) )
		{
			$page = preg_replace($pattern, '', $page);
		}

		// The attribute 'name' in tag 'meta name= and content=' is set to the invalid value 'revisit-after'.
		$page = preg_replace('/<meta\b[^>]* name=(("revisit-after")|(\'revisit-after\'))[^>]*\s*?\/?>/iU', '', $page);

		$page = preg_replace('/^[\s\t]*<meta\b([^>]*)>/im', '<meta${1}>', $page);


		/*
			<object></object>
		*/
		// The tag 'object' is disallowed.
		$page = preg_replace('/<object\b[^>]*>.*<\/object>/isU', '', $page);


		// Custom JavaScript is not allowed.
		$page = preg_replace('/<script\b[^>]*>.*<\/script>/isU', '', $page);


		/*
			<span></span>
		*/
		// The attribute 'active' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) active=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<span${1}${5}>', $page);

		// The attribute 'amount' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) amount=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<span${1}${5}>', $page);

		// The attribute 'override' may not appear in tag 'span'.
		$page = preg_replace('/<span\b([^>]*) override=(("[^"]*")|(\'[^\']*\'))([^>]*)>/i', '<span${1}${5}>', $page);

		/*
			<style></style>
		*/
		// The mandatory attribute 'amp-custom' is missing in tag 'style amp-custom'.
		$page = preg_replace('/<style\b[^>]*>.*<\/style>/isU', '', $page);
		$page = preg_replace('/<noscript><\/noscript>/i', '', $page);


		$page = preg_replace('/^[\s\t]*<title>(.*)<\/title>/im', '<title>${1}</title>', $page, 1);

		// The tag 'video' may only appear as a descendant of tag 'noscript'. Did you mean 'amp-video'?
		$page = preg_replace('/<video\b/i', '<amp-video', $page);

		$pattern = '/<(\w+\b[^>]*) on\w+=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*(\s?)(\/?)>/iU';
		while ( preg_match($pattern, $page) )
		{
			$page = preg_replace($pattern, '<${1}${5}${6}${7}>', $page);
		}

		$pattern = '/<(\w+\b[^>]*) style=(("[^"]*")|(\'[^\']*\'))([^>]*)\s*(\s?)(\/?)>/iU';
		$pattern2 = '/(("([^"]*)")|(\'([^\']*)\'))/i';
		if ( preg_match_all($pattern, $page, $matches) )
		{
			foreach ( $matches[2] as $key => $value )
			{
				if ( preg_match($pattern2, $value, $match) )
				{
					$css = !empty($match[2]) ? $match[3] : $match[5];
					$css = preg_replace('/\s*!important/i', '', $css);
					$value = '"' . $css . '"';
				}

				$page = preg_replace($pattern, '<${1} amp-style=' . $value . '${5}${6}${7}>', $page, 1);
			}
		}

		$page = preg_replace('/<(\w+\b[^>]*) amp-style="([^"]*)"([^>]*)\s*(\s?)(\/?)>/iU', '<${1} style="${2}"${3}${4}${5}>', $page);
	}

	protected function transcode_head(&$page)
	{
		// Remove blank lines.
		$page = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', "\n", $page);

		// Remove end line spaces.
		$page = preg_replace('/[\s\t]+[\r\n]/', "\n", $page);


		// The property 'minimum-scale' is missing from attribute 'content' in tag 'meta name=viewport'.
		$header = '<meta${1} name="viewport"${2} content="width=device-width, minimum-scale=1, initial-scale=1"${3} />';

		// The mandatory tag 'amphtml engine v0.js script' is missing or incorrect.
		$header .= "\n" . '<script async src="https://cdn.ampproject.org/v0.js"></script>';

		// The mandatory tag 'link rel=canonical' is missing or incorrect.
		$header .= "\n" . '<link rel="canonical" href="' . $this->canonical . '" />';

		// The mandatory tag 'noscript enclosure for boilerplate' is missing or incorrect.
		$header .= "\n" . '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>';

		// Import amp-audio component.
		if ( preg_match('/<amp-audio\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-audio" src="https://cdn.ampproject.org/v0/amp-audio-0.1.js"></script>';
		}

		// Import amp-fit-text component.
		if ( preg_match('/<amp-fit-text\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-fit-text" src="https://cdn.ampproject.org/v0/amp-fit-text-0.1.js"></script>';
		}

		// The tag 'FORM [method=POST]' requires including the 'amp-form' extension JavaScript.
		// The tag 'FORM [method=GET]' requires including the 'amp-form' extension JavaScript.
		if ( preg_match('/<form\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>';
		}

		// Import amp-iframe component.
		if ( preg_match('/<amp-iframe\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>';
		}

		// Import amp-install-serviceworker component.
		if ( preg_match('/<amp-install-serviceworker\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-install-serviceworker" src="https://cdn.ampproject.org/v0/amp-install-serviceworker-0.1.js"></script>';
		}

		// Import amp-sidebar component.
		if ( preg_match('/<amp-sidebar\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>';
		}

		// Import amp-video component.
		if ( preg_match('/<amp-video\b/i', $page) )
		{
			$header .= "\n" . '<script async custom-element="amp-video" src="https://cdn.ampproject.org/v0/amp-video-0.1.js"></script>';
		}

		// amp-custom style
		if ( !empty($this->style) )
		{
			$header .= "\n" . '<style amp-custom>';
			$header .= "\n" . $this->style;
			$header .= "\n" . '</style>';
		}

		$header .= "\n" . '<link rel="manifest" href="' . $this->home_url . '/' . ( $this->permalink != 'ugly' ? 'manifest.webmanifest' : '?manifest.webmanifest' ) . '" />';
		$header .= "\n" . '<meta name="theme-color" content="#ffffff" />';

		if ( !empty($this->page_type) )
		{
			$header .= "\n" . '<meta name="pwamp-page-type" content="' . $this->page_type . '" />';
		}

		$page = preg_replace('/<meta\b([^>]*) name="viewport"([^>]*) content="[^"]*width\s*=\s*device-width[^"]*"([^>]*)\s*?\/?>/iU', $header, $page, 1);
	}
}
