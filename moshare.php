<?php
/*
	Plugin Name: moShare 
	Plugin URI: http://www.moShare.com
	Description: Let users share your content via MMS using the Mogreet Messaging Platform
	Version: 1.2.8
	Author: Mogreet
	Author URI: http://www.moShare.com
	Contributors :
		Jonathan Perichon <jonathan.perichon@gmail.com>
		Benjamin Guillet <benjamin.guillet@gmail.com>
		Tim Rizzi <tim@mogreet.com>
	License: GPL2
 */

/*  Copyright 2012  Mogreet

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

		public static $version = '1.2.8';

		public static function init() {
			register_activation_hook(__FILE__, array(__CLASS__, 'set_options'));
			register_deactivation_hook(__FILE__, array(__CLASS__, 'unset_options'));
			add_action('admin_init', array(__CLASS__, 'moshare_admin_init'));
			add_action('admin_menu', array(__CLASS__, 'moshare_admin_menu'));
			add_filter('the_content', array(__CLASS__, 'add_widget'));
			add_action('wp_enqueue_scripts', array(__CLASS__, 'sharing_scripts'));
			add_filter('get_the_excerpt', array(__CLASS__, 'excerpt'));
		}

		public static function set_options() {
			// deprecated options
			delete_option('moshare_version');
			delete_option('moshare_icon');
			delete_option('moshare_cid');
			delete_option('moshare_twitter_via');
			delete_option('moshare_fb_app_id');
			delete_option('moshare_counts');
			delete_option('moshare_location');
			delete_option('moshare_services');
			delete_option('moshare_excerpt');

			$moshare = array(
				'version' => self::$version,
				'cid' => '',
				'icon' => 'moshare-button',
				'location' => 'beginning',
				'services_available' => 'moshare,facebook,twitter,gplus,linkedin',
				'excerpt' => false,
				'default_thumbnail' => array('enabled' => false, 'url' => ''),
				'facebook' => array('app_id' => '', 'enabled' => false, 'count' => false),
				'twitter' => array('user_id' => '', 'enabled' => false, 'count' => false),
				'gplus' => array('enabled' => false, 'count' => false),
				'linkedin' => array('enabled' => false, 'count' => false)
			);

			update_option('moshare', $moshare);
		}

		public static function unset_options() {
			delete_option('moshare');
		}

		/**
		 * Adds the moShare embed code to each post/page
		 */
		public static function add_widget($content) {
			$options = get_option('moshare');
			$services = explode(',', $options['services_available']);
			$html = '';
			global $post;
			$url = get_permalink($post->ID);
			foreach ($services as $service) {
				switch ($service) {
					case 'moshare':
						$s = new Moshare_Button($url, $post);
						$html .= ' '.$s->get_button();
						break;
					case 'twitter':
						if ($options['twitter']['enabled']) {
							$s = new Moshare_Twitter_Widget($url);
							if ($options['twitter']['count'])
								$html .= ' '.$s->get_with_count();
							else
								$html .= ' '.$s->get_without_count();

						}
						break;
					case 'linkedin':
						if ($options['linkedin']['enabled']) {
							$s = new Moshare_Linkedin_Widget($url);
							if ($options['linkedin']['count']) {
								$html .= ' '.$s->get_with_count();
							}
							else {
								$html .= ' '.$s->get_without_count();
							}
						}
						break;
					case 'facebook':
						if ($options['facebook']['enabled']) {
							$s = new Moshare_Facebook_Widget($url);
							if ($options['facebook']['count'])
								$html .= ' '.$s->get_with_count();
							else
								$html .= ' '.$s->get_without_count();
						}
						break;
					case 'gplus':
						if ($options['gplus']['enabled']) {
							$s = new Moshare_Google_Plus_Widget($url);
							if ($options['gplus']['count'])
								$html .= ' '.$s->get_with_count();
							else
								$html .= ' '.$s->get_without_count();

						}
						break;
					default:
						$s = NULL;
						break;
				}

				if (!$s) {
					continue;
				}
			}
			if ($options['location'] == 'beginning') {
				$content = $html . $content;
			} else {
				$content = $content . $html;
			}
			return $content;
		}

		/**
		* Registers the moshare options and moshare scripts and css
		*/
		public static function moshare_admin_init() {
			register_setting('moshare_options','moshare', array(__CLASS__, 'options_validate'));
			wp_register_style( 'moshare-settings-css', plugins_url('moshare.css', __FILE__));
			wp_register_script('moshare-settings-javascript', plugins_url('moshare.js', __FILE__), array('jquery','media-upload','thickbox'));
		}

		/**
		 * Adds moShare to the menu and enqueues scripts and css
		 */
		public static function moshare_admin_menu() {
			$moshare_options = add_options_page(
				__('moShare Options', 'moshare')
				, __('moShare', 'moshare')
				, 'manage_options'
				, 'moshare_options'
				, array(__CLASS__, 'options_form')
			);

			add_action('admin_print_styles-' . $moshare_options, array(__CLASS__, 'moshare_scripts_and_styles'));
		}

		/**
		* Enqueues moshare scripts and css
		*/
		public static function moshare_scripts_and_styles() {
        	wp_enqueue_script('jquery-ui-sortable');
        	wp_enqueue_style('moshare-settings-css');
        	wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
			wp_enqueue_script('moshare-settings-javascript');
		}
		
		
		/*
		 * moShare options form
		 * - customize the widget
		 * - set up the campaign ID
		 */
		public static function options_form() {

			?>
				<div id="fb-root"></div>
				<script>
					(function(d, s, id) {
	  					var js, fjs = d.getElementsByTagName(s)[0];
	  					if (d.getElementById(id)) return;
	  					js = d.createElement(s); js.id = id;
	  					js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=351220561567519";
	  					fjs.parentNode.insertBefore(js, fjs);
					} (document, 'script', 'facebook-jssdk'));
				</script>


				<div class="wrap">
					<h2><?php _e('moShare Options', 'moshare'); ?></h2>
					<div style="padding:10px;border:1px solid #aaa;background-color:#9fde33;text-align:center;display:none;" id="moshare_updated">Your options were successfully updated</div>
					<?php @readfile("http://www.mogreet.com/moshare/embed/news.html"); ?>
					<div id="poststuff" class="metabox-holder has-right-sidebar">
						<div id="side-info-column" class="inner-sidebar">
							<div class="meta-box-sortables">
								<div id="about" class="postbox">
									<h3 class="hndle" id="about-sidebar"><?php _e('About moShare') ?></h3>
									<div class="inside">
										<?php $options = get_option('moshare'); ?>
										<p>We make it easy for your visitors to share your content directly to mobile phones!</p>
										<p>moShare Homepage: <a href="http://www.moshare.com" target="_blank">moShare.com</a></br />
										moShare WP Version: <?php echo(self::$version); ?><br />
										Email: <a href="mailto:wordpress@moshare.com" target="_blank">wordpress@moshare.com</a></p>
									</div>
								</div>
							</div>
							<div class="meta-box-sortables">
								<div id="connect" class="postbox">
									<h3 class="hndle" id="connect-sidebar"><?php _e('Connect with us!') ?></h3>
									<div class="inside">
										<p>Found a bug?<br />
										Is there a feature you’d like to see?<br />
										Have general feedback?</p>

										<p>We want to hear from you! Tweet us <a href="https://twitter.com/#!/moShareit" target="_blank">@moShareit</a>, post in our <a href="http://wordpress.org/tags/moshare?forum_id=10" target="_blank">WordPress forum</a>, 
											or shoot us an email at <a href="mailto:wordpress@moshare.com" target="_blank">wordpress@moshare.com</a>.</p>
										<div class="fb-like-box" data-href="https://www.facebook.com/pages/moShare/138497486263063" data-width="292" data-show-faces="false" data-stream="false" data-header="false"></div>
										<a href="https://twitter.com/#!/moShareit" class="twitter-follow-button" data-show-count="true" data-lang="en">Follow @moShare</a>
										<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
									</div>
								</div>
							</div>					
						</div>

						<div id="post-body" class="has-sidebar">
							<div id="post-body-content" class="has-sidebar-content">
								<div id="normal-sortables" class="meta-box-sortables">
									<form method="post" action="options.php">
										<?php settings_fields('moshare_options'); ?>
										<?php $options = get_option('moshare'); ?>
										<div id="general" class="postbox">
											<h3>1. General Settings</h3>
											<div class="inside">
												<h4>Pick up your moShare button style:</h4>
												<input type="radio" name="moshare[icon]" value="moshare-button" <?php if ($options['icon'] == 'moshare-button') echo('checked'); ?> /> <iframe id="iframe" src="http://www.mogreet.com/moshare/button/?share=http%3A%2F%2Fwww.moshare.com&message=moShare+rocks%21&channel=website&layout=classic" scrolling="no" frameborder="0" style="cursor: pointer;margin-right: 20px; padding: 0; border: none; overflow:hidden; width:86px; height:20px; background-color: translucend;" allowtransparency="true"></iframe>
												<input type="radio" name="moshare[icon]" value="moshare-button-hor" <?php if ($options['icon'] == 'moshare-button-hor') echo('checked'); ?> /> <iframe id="iframe" src="http://www.mogreet.com/moshare/button/?share=http%3A%2F%2Fwww.moshare.com&message=moShare+rocks%21&channel=website&layout=classic-hor" scrolling="no" frameborder="0" style="cursor: pointer;margin: 0; padding: 0; border: none; overflow:hidden; width:135px; height:22px; background-color: translucend;" allowtransparency="true"></iframe>
												<input type="radio" name="moshare[icon]" value="moshare-button-mini"<?php if ($options['icon'] == 'moshare-button-mini') echo('checked'); ?> /> <iframe id="iframe" src="http://www.mogreet.com/moshare/button/?share=http%3A%2F%2Fwww.moshare.com&message=moShare+rocks%21&channel=website&layout=chicklet" scrolling="no" frameborder="0" style="cursor: pointer;margin-right: 20px; padding: 0; border: none; overflow:hidden; width:22px; height:22px; background-color: translucend;" allowtransparency="true"></iframe>
												<input type="radio" name="moshare[icon]" value="moshare-chicklet-hor" <?php if ($options['icon'] == 'moshare-chicklet-hor') echo('checked'); ?> /> <iframe id="iframe" src="http://www.mogreet.com/moshare/button/?share=http%3A%2F%2Fwww.moshare.com&message=moShare+rocks%21&channel=website&layout=chicklet-hor" scrolling="no" frameborder="0" style="cursor: pointer;margin: 0; padding: 0; border: none; overflow:hidden; width:80px; height:22px; background-color: translucend;" allowtransparency="true"></iframe>
												<h4>Button appears:</h4>
												<input type="radio" name="moshare[location]" value="beginning"  <?php if ($options['location'] == 'beginning') echo('checked'); ?> /> Beginning of the post
												<input type="radio" name="moshare[location]" value="end"  <?php if ($options['location'] == 'end') echo('checked'); ?> /> End of the post
											</div>
										</div>
										<div id="advanced" class="postbox">
											 <h3>2. Advanced Settings</h3>
											 <div class="inside">
											 	Sharing button on excerpts: <input type="checkbox" name="moshare[excerpt]" <?php if ($options['excerpt'] == true) echo('checked'); ?> /> enable <br />
											 	<em>enabling will show the sharing button on excerpts.</em><br /><br />

											 	Use a default thumbnail: <input type="checkbox" id="thumbnail_checkbox" name="moshare[default_thumbnail][enabled]" <?php if ($options['default_thumbnail']['enabled'] == true) echo('checked'); ?> /> enable <br />
											 	<em>If a thumbnail does not exist for a post, the default thumbnail will be used in its place.</em><br /><br />
											 	<div id="default_thumbnail_uploader" style="display: none;">
												 	<tr valign="top">
														<th scope="row">Default thumbnail:</th>
														<td>
															<label for="upload_image">
																<input id="upload_image" type="text" size="36" name="moshare[default_thumbnail][url]" value="<?php echo(urldecode($options['default_thumbnail']['url'])); ?>" />
																<input id="upload_image_button" type="button" value="Upload Image" /><br />
															</label>
														</td>
													</tr>
												</div>
											</div>
										</div>
										<div id="social" class="postbox">
											 <h3>3. Social Configuration</h3>
											 <div class="inside">
												<h4>Click and drag a sharing service to arrange the order in which it is displayed.</h4>
												<input type="hidden" id="services_available" name="moshare[services_available]" value="<?php echo ($options['services_available']); ?>" size="30" /> 
												<div id="config_container">
													<table id="header_table">
														<tbody>
															<tr id="header_row">
															 <td class="order_header">
															 	<div class="header_content_wrap header_content_wrap_start">
															 	Order
															 	</div><!--header_content_wrap close-->
															 </td>
															 <td class="button_header">
															 	<div class="header_content_wrap">
															 	Service
															 	</div><!--header_content_wrap close-->
															 </td>
															 <td class="switch_header">
															 	<div class="header_content_wrap">
															 	Enable
															 	</div><!--header_content_wrap close-->
															 </td>
															 <td class="counter_header">
															 	<div class="header_content_wrap header_content_wrap_end">
															 	Counter
															 	</div><!--header_content_wrap close-->
															 </td>
															</tr><!--header_row-->
														</tbody>
													</table><!--header_table close-->
													
													<table id="config_settings">
														<tbody>
															<?php
													   			$options['services_available'] = explode(',', $options['services_available']);
													   			foreach($options['services_available'] as $service) {
													   				switch($service) {
													   					case 'facebook':
													   						?>
					                                                            <tr id="<?php echo $service; ?>">
																				 <td class="count_cell">
																				 	<div class="count_wrap">
																				 		<span class="count_text">3</span>
																				 	</div>
																				 </td>
																				 <td class="brand_cell">
																				 	<div class="content_wrap">
																						<img src="<?php echo(plugins_url('img/facebook_brand.png', __FILE__)); ?>" alt="facebook"/>
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="switch_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[facebook][enabled]" <?php if ($options['facebook']['enabled'] == true) echo('checked'); ?>/>
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="counter_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[facebook][count]" <?php if ($options['facebook']['count'] == true) echo('checked'); ?> />
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="drag_cell">
																				 	<div class="content_wrap content_wrap_end">
																				 		<img src="<?php echo(plugins_url('img/drag.png', __FILE__)); ?>" alt="drag" width="29" height="17" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				</tr> <!--facebook_row-->
													   						<?php
													   						break;
													   					case 'twitter':
													   						?>
					                                                            <tr id="<?php echo $service; ?>">
																				 <td class="count_cell">
																				 	<div class="count_wrap">
																				 		<span class="count_text">2</span>
																				 	</div>
																				 </td>
																				 <td class="brand_cell">
																				 	<div class="content_wrap">
																						<img src="<?php echo(plugins_url('img/twitter_brand.png', __FILE__)); ?>" alt="twitter_brand"/>
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="switch_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[twitter][enabled]" <?php if ($options['twitter']['enabled'] == true) echo('checked'); ?>/>
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="counter_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[twitter][count]" <?php if ($options['twitter']['count'] == true) echo('checked'); ?>/>
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="drag_cell">
																				 	<div class="content_wrap content_wrap_end">
																				 		<img src="<?php echo(plugins_url('img/drag.png', __FILE__)); ?>" alt="drag" width="29" height="17" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				</tr> <!--twitter_row-->												   						
													   						<?php
													   						break;
													   					case 'gplus':
													   						?>
					                                                            <tr id="<?php echo $service; ?>">
																				 <td class="count_cell">
																				 	<div class="count_wrap">
																				 		<span class="count_text">4</span>
																				 	</div>
																				 </td>
																				 <td class="brand_cell">
																				 	<div class="content_wrap">
																						<img src="<?php echo(plugins_url('img/google_brand.png', __FILE__)); ?>" alt="google_brand" />
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="switch_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[gplus][enabled]" <?php if ($options['gplus']['enabled'] == true) echo('checked'); ?>/>
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="counter_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[gplus][count]" <?php if ($options['gplus']['count'] == true) echo('checked'); ?>/>
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="drag_cell">
																				 	<div class="content_wrap content_wrap_end">
																				 		<img src="<?php echo(plugins_url('img/drag.png', __FILE__)); ?>" alt="drag" width="29" height="17" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				</tr> <!--google_row-->
													   						<?php
													   						break;
													   					case 'moshare':
													   						?>
																				<tr id="<?php echo $service; ?>">
																				 <td class="count_cell">
																				 	<div class="count_wrap">
																				 		<span class="count_text">1</span>
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="brand_cell">
																				 	<div class="content_wrap">
																				 		<img src="<?php echo(plugins_url('img/moshare_brand.png', __FILE__)); ?>" alt="moshare_brand" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="switch_cell">
																				 	<div class="content_wrap">
																				 	<input type="checkbox" checked="checked" disabled="disabled"/>
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="counter_cell">
																				 	<div class="content_wrap">
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="drag_cell">
																				 	<div class="content_wrap content_wrap_end">
																				 		<img src="<?php echo(plugins_url('img/drag.png', __FILE__)); ?>" alt="drag" width="29" height="17" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				</tr> <!--moShare_row-->
													   						<?php
													   						break;
													   					case 'linkedin':
													   						?>
					                                                            <tr id="<?php echo $service; ?>">
																				 <td class="count_cell">
																				 	<div class="count_wrap">
																				 		<span class="count_text">5</span>
																				 	</div>
																				 </td>
																				 <td class="brand_cell">
																				 	<div class="content_wrap">
																						<img src="<?php echo(plugins_url('img/linkedin_brand.png', __FILE__)); ?>" alt="linkedin_brand" />
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="switch_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[linkedin][enabled]" <?php if ($options['linkedin']['enabled'] == true) echo('checked'); ?> />
																				 	</div><!--content_wrap close-->
																				 </td>
																				 <td class="counter_cell">
																				 	<div class="content_wrap">
																				 		<input type="checkbox" name="moshare[linkedin][count]" <?php if ($options['linkedin']['count'] == true) echo('checked'); ?> />
																					</div><!--content_wrap close-->
																				 </td>
																				 <td class="drag_cell">
																				 	<div class="content_wrap content_wrap_end">
																				 		<img src="<?php echo(plugins_url('img/drag.png', __FILE__)); ?>" alt="drag" width="29" height="17" />
																				 	</div><!--content_wrap close-->
																				 </td>
																				</tr>
													   						<?php
													   						break;
													   					default:
													   						
																	}
																}
													   		?>
														</tbody>
													</table>
												</div><!--config_container close-->
											</div>
										</div>
										<div id="social-advanced" class="postbox">
											 <h3>4. Social Configuration advanced (optional)</h3>
											 <div class="inside">
												Facebook App ID: <input type="text" name="moshare[facebook][app_id]" value="<?php echo($options['facebook']['app_id']); ?>" /><br />
												Twitter User ID: <input type="text" name="moshare[twitter][user_id]" value="<?php echo($options['twitter']['user_id']); ?>"/> (without @ symbol)<br />
											</div>
										</div>
										</fieldset>
										<input type="submit" class="button-primary" name="submit_button" value="<?php _e('Update moShare Options', 'moshare'); ?>" />
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php	
		}

		/**
		* Sanitazes
		*/
		public static function options_validate($input) {
			if (!isset($input['cid'])) {
				$input['cid'] = '';
			}


			if (!isset($input['icon'])) {
				$input['icon'] = 'moshare-button';
			}

			if (!isset($input['location'])) {
				$input['location'] = 'beginning';
			}
			$input['facebook']['app_id'] = wp_filter_nohtml_kses($input['facebook']['app_id']);	
			$input['twitter']['user_id'] = wp_filter_nohtml_kses($input['twitter']['user_id']);	
			$input['services_available'] = wp_filter_nohtml_kses($input['services_available']);	
			
			$input['excerpt'] = isset($input['excerpt']) ? true : false;
			$input['default_thumbnail']['enabled'] = isset($input['default_thumbnail']['enabled']) ? true : false;

			$input['facebook']['enabled'] = isset($input['facebook']['enabled']) ? true : false;
			$input['twitter']['enabled'] = isset($input['twitter']['enabled']) ? true : false;
			$input['gplus']['enabled'] = isset($input['gplus']['enabled']) ? true : false;
			$input['linkedin']['enabled'] = isset($input['linkedin']['enabled']) ? true : false;	

			$input['facebook']['count'] = isset($input['facebook']['count']) ? true : false;
			$input['twitter']['count'] = isset($input['twitter']['count']) ? true : false;
			$input['gplus']['count'] = isset($input['gplus']['count']) ? true : false;
			$input['linkedin']['count'] = isset($input['linkedin']['count']) ? true : false;


			return $input;
		}


		/**
		 * Includes the moShare JavaScript once per page
		 */
		public static function sharing_scripts() {
			$options = get_option('moshare');
			$services = explode(',', $options['services_available']);
			wp_enqueue_script('moshare', 'http://www.mogreet.com/moshare/embed/moshare.js', array(), '10.0', true);
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
			$options = get_option('moshare'); 

		   	if ($options['excerpt'] == 1) {
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
		   else {
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
				$text = apply_filters('the_excerpt', $text);
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

	class Moshare_Button {
		const MAX_LENGTH_DESCRIPTION = 1000;

		private $url;
		private $cid;
		private $icon;
		private $title;
		private $message;
		private $image;
		private $description;
		private $options;

		public function __construct($url, $post) {
			$this->options = get_option('moshare');
			$this->url     = $url;
			$this->cid     = $this->options['cid'];
			$this->icon    = $this->options['icon'];
			$this->title   = get_the_title();

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

		public function get_button() {
			$html = '<a href="http://www.mogreet.com/moshare/it/" class="'.$this->icon.'"';
			$html .= ' data-channel="wordpress-'.Moshare::$version.'" data-message="'.$this->description.'" data-type="article"';
			$html .= ' data-location="'.$this->url.'" data-title="'.$this->title.'"';
			if ($this->image != '') {
				$html .= ' data-thumbnail="'.$this->image.'"';
			}
			else if ($this->options['default_thumbnail']['enabled'] == true) {
				$html .= ' data-thumbnail="' . $this->options['default_thumbnail']['url'] . '"';
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
			$options = get_option('moshare');
			$this->app_id = $options['facebook']['app_id'];
		}

		public function get_without_count() {
			$html = '<iframe src="//www.facebook.com/plugins/like.php?href='.$this->url;
			$html .= '&amp;send=false&amp;layout=button_count&amp;width=50&amp;show_faces=false';
			$html .= '&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21&amp;';
			$html .= 'appId='.$this->app_id.'" scrolling="no" frameborder="0" style="border:none; ';
			$html .= 'overflow:hidden; width:53px; height:21px;" allowTransparency="true"></iframe>';

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
			$options = get_option('moshare');
			$this->via = $options['twitter']['user_id'];
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
