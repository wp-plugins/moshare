<?php
/*
	Plugin Name: moShare 
	Plugin URI: http://www.moShare.com
	Description: Let users share your content via MMS using the Mogreet Messaging Platform
	Version: 1.2.7
	Author: Mogreet
	Author URI: http://www.moShare.com
	Contributors :
		Jonathan Perichon <jonathan.perichon@gmail.com>
		Benjamin Guillet <benjamin.guillet@gmail.com>
		Tim Rizzi <tim@mogreet.com>
	License: GPL2
 */

/*  Copyright 2011  Mogreet

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


if (!class_exists('Moshare') && 
	!class_exists('Moshare_Widget') && 
	!class_exists('Moshare_Button') && 
	!class_exists('Moshare_Facebook_Widget') && 
	!class_exists('Moshare_Linkedin_Widget') && 
	!class_exists('Moshare_Twitter_Widget') && 
	!class_exists('Moshare_Google_Plus_Widget')) {

	class Moshare {

		public static $version = "1.2.7";

		public static function init() {
			$current_version = get_option("moshare_version");

			if (version_compare($current_version, self::$version, "<")) {
				update_option('moshare_version', self::$version);
				update_option('moshare_icon', 'moshare-button');
				$services = get_option('moshare_services');
				if (empty($services)) {
					update_option('moshare_services', 'moshare');
				}
			}

			register_activation_hook(__FILE__, array(__CLASS__, 'set_options'));
			register_uninstall_hook(__FILE__, array(__CLASS__, 'unset_options'));
			add_action('admin_menu', array(__CLASS__, 'menu_items'));
			add_action('init', array(__CLASS__, 'request_handler'), 9999);
			add_filter('the_content', array(__CLASS__, 'add_widget'));
			add_action('wp_enqueue_scripts', array(__CLASS__, 'scripts'));
			add_filter('get_the_excerpt', array(__CLASS__, 'excerpt'));
		}

		public static function set_options() {
			add_option('moshare_version', self::$version);
			add_option('moshare_icon', 'moshare-button');
			add_option('moshare_cid', '');
			add_option('moshare_twitter_via', '');
			add_option('moshare_fb_app_id', '');
			add_option('moshare_counts', 'false');
			add_option('moshare_location', 'beginning');
			add_option('moshare_services', 'moshare');
			add_option('moshare_excerpt', 'no');
		}

		public static function unset_options() {
			delete_option('moshare_version');
			delete_option('moshare_icon');
			delete_option('moshare_cid');
			delete_option('moshare_twitter_via');
			delete_option('moshare_fb_app_id');
			delete_option('moshare_counts');
			delete_option('moshare_location');
			delete_option('moshare_services');
			delete_option('moshare_excerpt');
		}

		/**
		 * Adds the moShare embed code to each post/page
		 */
		public static function add_widget($content) {
			$services = explode(',', get_option('moshare_services'));
			$html = '';
			global $post;
			$url = get_permalink($post->ID);
			foreach ($services as $service) {
				switch ($service) {
				case 'moshare':
					$s = new Moshare_Button($url, $post);
					break;
				case 'twitter':
					$s = new Moshare_Twitter_Widget($url);
					break;
				case 'linkedin':
					$s = new Moshare_Linkedin_Widget($url);
					break;
				case 'facebook':
					$s = new Moshare_Facebook_Widget($url);
					break;
				case 'gplus':
					$s = new Moshare_Google_Plus_Widget($url);
					break;
				default:
					$s = NULL;
					break;
				}

				if (!$s) {
					continue;
				}

				if (get_option('moshare_counts') == 'true') {
					$html .= ' '.$s->get_with_count();
				} else {
					$html .= ' '.$s->get_without_count(); 
				}
			}
			$location = get_option('moshare_location');
			if ($location == 'beginning') {
				$content = $html . $content;
			} else {
				$content = $content . $html;
			}
			return $content;
		}

		/*
		 * moShare options form
		 * - customize the widget
		 * - set up the campaign ID
		 */
		public static function options_form() {
			$icon                      = get_option('moshare_icon');
			$cid             		   = get_option('moshare_cid');
			$location        		   = get_option('moshare_location');
			$twitter_via     		   = get_option('moshare_twitter_via');
			$fb_app_id       		   = get_option('moshare_fb_app_id');
			$services        		   = get_option('moshare_services');
			$counts          		   = (get_option('moshare_counts') == 'true') ? 'checked' : '';
			$moshare_excerpt 		   = get_option('moshare_excerpt');

			$classic    = ($icon == 'moshare-button' || $icon != 'moshare-button-mini' || $icon != 'moshare-ver-counter') ? 'checked' : "";
			$mini       = ($icon == 'moshare-button-mini') ? 'checked' : "";
			$vertical   = ($icon == 'moshare-ver-counter') ? 'checked' : "";
			$beginning  = ($location == 'beginning') ? 'checked' : "";
			$end        = ($location == 'end') ? 'checked' : "";
			$yes 	    = ($moshare_excerpt == 'yes') ? 'checked' : "";
			$no 	    = ($moshare_excerpt == 'no' || $moshare_excerpt != 'yes') ? 'checked' : "";

			?>
				<div class="wrap">
					<h2><?php _e('moShare Options', 'moshare'); ?></h2>
					<div style="padding:10px;border:1px solid #aaa;background-color:#9fde33;text-align:center;display:none;" id="moshare_updated">Your options were successfully updated</div>
					<?php @readfile("http://d2c.bandcon.mogreet.com/moshare/embed/news.html"); ?>
					<div id="poststuff" class="metabox-holder has-right-sidebar">
						<div id="side-info-column" class="inner-sidebar">
							<div class="meta-box-sortables">
								<div id="about" class="postbox">
									<h3 class="hndle" id="about-sidebar"><?php _e('About the Plugin:') ?></h3>
									<div class="inside">
										<p>Found a bug? Have a request? Talk to <a href="https://twitter.com/#!/moShareit">@moShare</a> on Twitter or use our <a href="http://wordpress.org/tags/moshare?forum_id=10">WordPress forum</a>!</p>
									</div>
								</div>
							</div>
						</div>

						<div id="post-body" class="has-sidebar">
							<div id="post-body-content" class="has-sidebar-content">
								<div id="normal-sortables" class="meta-box-sortables">
									<div id="about" class="postbox">
										<div class="inside">
											<form id="ak_moshare" name="ak_moshare" action="<?php echo(get_bloginfo('wpurl') .'/wp-admin/index.php'); ?>" >
											<fieldset class="options">
											<h4>Pick up your moShare style</h4>
											<input type="radio" name="moshare_icon" value="moshare-button" <?php echo($classic); ?>/> <img src="http://d2c.bandcon.mogreet.com/moshare/embed/moshare_new.png"/>
											<input type="radio" name="moshare_icon" value="moshare-button-mini" <?php echo($mini); ?> /> <img src="http://d2c.bandcon.mogreet.com/moshare/embed/moshare_chicklet_new.png"/>
											<h4>Place your buttons</h4>
											<input type="radio" name="moshare_location" value="beginning"  <?php echo($beginning); ?> /> Beginning of the post
											<input type="radio" name="moshare_location" value="end"  <?php echo($end); ?> /> End of the post
											<h4>Choose and order the sharing services you want</h4>
											Example with all the services available: facebook,moshare,linkedin,twitter,gplus<br /><br />
											<input type="text" name="moshare_services" value="<?php _e($services); ?>" size="30" /><br />
											<h4>Do you want the service buttons on your excerpts?</h4>
											<input type="radio" name="moshare_excerpt" value="yes"  <?php echo($yes); ?> /> Yes
											<input type="radio" name="moshare_excerpt" value="no"  <?php echo($no); ?> /> No
											<h4>Advanced</h4>
											Display counts <input type="checkbox" name="moshare_counts" value="moshare_counts"  <?php echo($counts); ?>><br />
											Twitter-via user: <input type="text" name="moshare_twitter_via" value="<?php echo($twitter_via); ?>"/><br />
											Facebook App Id: <input type="text" name="moshare_fb_app_id" value="<?php echo($fb_app_id); ?>" /><br />
											</fieldset>
											<br/>
											<input type="submit" class="button-primary" name="submit_button" value="<?php _e('Update moShare Options', 'moshare'); ?>" />
											<input type="hidden" name="moshare_action" value="moshare_update_settings" />
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php	
		}

		/**
		 * Updates moShare options
		 */
		public static function request_handler() {
			if(isset($_REQUEST['moshare_action'])) {
				$action = $_REQUEST['moshare_action'];
			}

			if (isset($action) && $action == 'moshare_update_settings') {
				update_option('moshare_icon', $_REQUEST['moshare_icon']);
				update_option('moshare_cid', urlencode($_REQUEST['moshare_cid']));
				update_option('moshare_location', $_REQUEST['moshare_location']);
				update_option('moshare_fb_app_id', urlencode($_REQUEST['moshare_fb_app_id']));
				update_option('moshare_twitter_via', urlencode($_REQUEST['moshare_twitter_via']));
				update_option('moshare_excerpt', $_REQUEST['moshare_excerpt']);

				//adds moShare on the left if not set
				$services = explode(',', $_REQUEST['moshare_services']);
				foreach ($services as &$service) {
					$service = trim($service);
				}
				if (!in_array('moshare', $services)) {
					array_unshift($services, 'moshare');
				}
				update_option('moshare_services', implode(',', $services));

				if (isset($_REQUEST['moshare_counts'])) {
					update_option('moshare_counts', 'true');
				} else {
					update_option('moshare_counts', 'false');
				}

				header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=moshare.php&updated=true');
				die();
			}
		}

		/**
		 * Adds moShare to the Menu
		 */
		public static function menu_items() {
			add_options_page(
				__('moShare Options', 'moshare')
				, __('moShare', 'moshare')
				, manage_options
				, basename(__FILE__)
				, array(__CLASS__, 'options_form')
			);
		}

		/**
		 * Includes the moShare JavaScript once per page
		 */
		public static function scripts() {
			$services = explode(',', get_option('moshare_services'));
			wp_enqueue_script('moshare', 'http://d2c.bandcon.mogreet.com/moshare/embed/moshare_wordpress.js', array(), '1.4', true);
			foreach ($services as $service) {
				if ($service == 'linkedin') {
					wp_enqueue_script('linkedin', 'http://platform.linkedin.com/in.js', array(), '1.0', true);
				} else if ($service == 'gplus') {
					wp_enqueue_script('gplus', 'https://apis.google.com/js/plusone.js', array(), '1.0', true);
				} else if ($service == 'twitter') {
					wp_enqueue_script('twitter', 'http://platform.twitter.com/widgets.js', array(), '1.0', false);
				}
			}
		}

		public static function excerpt($content) {
		   $excerpt_option = get_option('moshare_excerpt');	   
		   if ($excerpt_option == 'yes') {
			   $text = get_the_content('');
			   $excerpt_length = apply_filters('excerpt_length', 55);
			   $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
			   $text = strip_shortcodes($text);
			   $text = strip_tags($text);
			   $words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
			   if (count($words) > $excerpt_length) {
				   array_pop($words);
				   $text = implode(' ', $words);
				   $text = $text . $excerpt_more;
			   } else {
				   $text = implode(' ', $words);
			   }
			   $text = apply_filters('the_content', $text);
			   return $text;
		   }
	   }
	}

	abstract class Moshare_Widget {
		protected $url;

		public function __construct($url) {
			$this->url = $url;
		}

		abstract public function get_without_count();
		abstract public function get_with_count();
	}

	class Moshare_Button extends Moshare_Widget {
		const MAX_LENGTH_DESCRIPTION = 1000;

		private $cid;
		private $icon;
		private $title;
		private $message;
		private $image;
		private $description;

		public function __construct($url, $post) {
			parent::__construct($url);
			$this->cid   			 = get_option('moshare_cid');
			$this->icon  		     = get_option('moshare_icon');
			$this->title 			 = get_the_title();

			if (function_exists('has_excerpt') && has_excerpt($post->ID)) {
				$this->set_description($post->post_excerpt);
			} else {
				$this->set_description($post->post_content);
			}


			$this->image = "";
			if (current_theme_supports('post-thumbnails') && has_post_thumbnail($post->ID)) {
				$img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
				$this->image = $img[0];

			} else if ($post->post_content != "" && class_exists('DOMDocument')) {
				libxml_use_internal_errors(true); // disable libxml warnings
				$doc = DOMDocument::loadHTML($post->post_content);
				$images = $doc->getElementsByTagName("img");

				// grabs the biggest image based on the width & height 
				// html attributes
				$max_size = 0;
				foreach ($images as $image) {
					if ($image->getAttribute('class') != "wp-smiley") {
						$size = $image->getAttribute('width') * $image->getAttribute('height');
						if ($size > $max_size || $max_size == 0) {
							$max_size = $size;
							$this->image = $image->getAttribute('src');
						}
					}
				}
			}
		}

		private function set_description($str) {
			$search = array('@<script[^>]*?>.*?</script>@si',
				'@<style[^>]*?>.*?</style>@siU', 
				'@<![\s\S]*?--[ \t\n\r]*>@'
			); 
			$str = preg_replace($search, "", $str); // removes script, style and comments
			$str = preg_replace("/\"/", "&quot;", $str); // protects double quotes

			// removing extra-spaces and extra-lines
			$str = wpautop($str); 
			$str = preg_replace("/&nbsp;/", "", $str);
			if (seems_utf8($str)) { 
				$str = preg_replace('/[\p{Z}\s]{2,}/u', ' ', $str);
			} else {
				$str = preg_replace('/\s\s+/', ' ', $str);
			}

			$str = strip_shortcodes($str);
			$str = strip_tags($str);
			$str = trim($str);

			if (strlen($str) > self::MAX_LENGTH_DESCRIPTION) {
				$str = substr($str, 0, self::MAX_LENGTH_DESCRIPTION);
				$str .= " ...";
			}

			$this->description = $str;
		}

		public function get_without_count() {
			$html = '<a href="http://www.mogreet.com/moshare/it/" class="'.$this->icon.'"';
			$html .= ' data-channel="wordpress-'.Moshare::$version.'" data-message="'.$this->description.'" data-type="article"';
			$html .= ' data-location="'.$this->url.'" data-title="'.$this->title.'"';
			if ($this->image != '') {
				$html .= ' data-thumbnail="'.$this->image.'"';
			}
			if ($this->cid != '') {
				$html .= ' data-cid="'.$this->cid.'"';
			}
			$html .= '></a>';

			return $html;
		}

		public function get_with_count() {
			$html = '<a href="http://www.mogreet.com/moshare/it/" class="moshare-hoz-counter"';
			$html .= ' data-channel="wordpress-'.Moshare::$version.'" data-message="'.$this->description.'" data-type="article"';
			$html .= ' data-location="'.$this->url.'" data-title="'.$this->title.'"';
			if ($this->image != '') {
				$html .= ' data-thumbnail="'.$this->image.'"';
			}
			if ($this->cid != '') {
				$html .= ' data-cid="'.$this->cid.'"';
			}
			$html .= '></a>';

			return $html;
		}
	}

	class Moshare_Facebook_Widget extends Moshare_Widget {
		private $app_id;

		public function __construct($url) {
			parent::__construct($url);
			$this->app_id = get_option('moshare_fb_app_id');
		}

		public function get_without_count() {
			$html = '<iframe src="//www.facebook.com/plugins/like.php?href='.$this->url;
			$html .= '&amp;send=false&amp;layout=button_count&amp;width=50&amp;show_faces=false';
			$html .= '&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21&amp;';
			$html .= 'appId='.$this->app_id.'" scrolling="no" frameborder="0" style="border:none; ';
			$html .= 'overflow:hidden; width:50px; height:21px;" allowTransparency="true"></iframe>';

			return $html;
		}

		public function get_with_count() {
			$html = '<iframe src="//www.facebook.com/plugins/like.php?href='.$this->url;
			$html .= '&amp;send=false&amp;layout=button_count&amp;width=95&amp;show_faces=false';
			$html .= '&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21&amp;';
			$html .= 'appId='.$this->app_id.'" scrolling="no" frameborder="0" style="border:none; ';
			$html .= 'overflow:hidden; width:95px; height:21px;" allowTransparency="true"></iframe>';

			return $html;
		}
	}


	class Moshare_Twitter_Widget extends Moshare_Widget {
		private $via;
		private $text;

		public function __construct($url) {
			$this->text = get_the_title();
			parent::__construct($url);
			$this->via = get_option('moshare_twitter_via');
		}

		public function get_without_count() {
			$html = '<a href="https://twitter.com/share" class="twitter-share-button" ';
			$html .= 'data-count="none" data-via="'.$this->via.'" data-url="'.$this->url.'" ';
			$html .= 'data-text="'.$this->text.'"';
			$html .= '>Tweet</a>';

			return $html;
		}

		public function get_with_count() {
			$html = '<a href="https://twitter.com/share" class="twitter-share-button" ';
			$html .= 'data-via="'.$this->via.'" data-url="'.$this->url.'" ';
			$html .= 'data-text="'.$this->text.'"';
			$html .= '>Tweet</a>';

			return $html;
		}
	}

	class Moshare_Google_Plus_Widget extends Moshare_Widget {
		public function __construct($url) {
			parent::__construct($url);
		}

		public function get_without_count() {
			return '<g:plusone size="medium" annotation="none" href="'.$this->url.'" width="120"></g:plusone>';
		}

		public function get_with_count() {
			return '<div style="display:inline; width: auto !important;"><g:plusone size="medium" annotation="bubble" href="'.$this->url.'" width="120"></g:plusone></div>';
		}
	}

	class Moshare_Linkedin_Widget extends Moshare_Widget {
		public function __construct($url) {
			parent::__construct($url);
		}

		public function get_without_count() {
			return '<script type="IN/Share" data-url="'.$this->url.'"></script>';
		}

		public function get_with_count() {
			return '<script type="IN/Share" data-url="'.$this->url.'" data-counter="right"></script>';
		}
	}
}

Moshare::init();

?>
