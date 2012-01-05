<?php
/*
    Plugin Name: moShare 
    Plugin URI: http://corp.mogreet.com 
    Description: Let users share your content via MMS using the Mogreet Messaging Platform
    Version: 1.1.3
    Author: Mogreet
    Author URI: http://corp.mogreet.com
    Contributors :
        Jonathan Perichon <jonathan.perichon@gmail.com>
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

define("MAX_LENGTH_DESCRIPTION", 1000);

/**
 * Sanitizes the description
 * - Strips html tags
 * - Strips wordpress shortcodes
 * - Trims
 * - Shortens the description to the max length
 */
function moshare_sanitize_description($str) {

    $search = array('@<script[^>]*?>.*?</script>@si',
        '@<style[^>]*?>.*?</style>@siU', 
        '@<![\s\S]*?--[ \t\n\r]*>@'
    ); 
    $str = preg_replace($search, "", $str); // removing script, style and comments
    // $str = preg_replace("/&nbsp;/", "", $str); TODO check if it's needed'
    $str = preg_replace("/\"/", "&quot;", $str); // protecting double quotes
    $str = wpautop($str); // removes multiples break lines

    if (seems_utf8($str)) { // removes multiples spaces
        $str = preg_replace('/[\p{Z}\s]{2,}/u', ' ', $str);
    } else {
        $str = preg_replace('/\s\s+/', ' ', $str);
    }

    $str = strip_shortcodes($str);
    $str = strip_tags($str);
    $str = trim($str);

    if (strlen($str) > MAX_LENGTH_DESCRIPTION) {
        $str = substr($str, 0, MAX_LENGTH_DESCRIPTION);
        $str .= " ...";
    }

    return $str;
}

/**
 * Adds the moShare embed code to each post/page
 */
function moshare_add_widget($content) {
    global $post;

    $url         = get_permalink($post->ID);
    $title       = get_the_title();

    $message = "";
    if (function_exists("has_excerpt") && has_excerpt($post->ID)) {
        $message = moshare_sanitize_description($post->post_excerpt);
    } else {
        $message = moshare_sanitize_description($post->post_content);
    }


    $image = "";
    if (current_theme_supports("post-thumbnails") && has_post_thumbnail($post->ID)) {
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
        $image = $img[0];

    } else if ($post->post_content != "") {
        libxml_use_internal_errors(true); // disable libxml warnings
        $doc = DOMDocument::loadHTML($post->post_content);
        $images = $doc->getElementsByTagName("img");
        foreach ($images as $image) {
            if ($image->getAttribute('class') != "wp-smiley") {
                $image = $image->getAttribute('src');
                break;
            }
        }
    }

    $logo = get_option('moshare_icon');
    if ($logo == '') {
        update_option('moshare_icon', 'moshare-button');
    }
    $cid  = get_option('moshare_cid');

    $location = get_option('moshare_location');
    if ($location == '') {
        update_option('moshare_location', 'bottom');
    }

    $html = '<div style="display: inline; white-space: nowrap;"><span style="margin-right: 30px;"><a href="http://www.mogreet.com/moshare/it/" class="'.$logo.'"';
    $html .= ' data-message="'.$message.'" data-type="article"';
    $html .= ' data-location="'.$url.'" data-title="'.$title.'"';
    if ($image != '') {
        $html .= ' data-thumbnail="'.$image.'"';
    }
    if ($cid != '') {
        $html .= ' data-cid="'.$cid.'"';
    }
    $html .= '></a></span>';

    $html .= '</div>';

    if ($location == "top") {
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
function moshare_options_form() {
    $icon     = get_option('moshare_icon');
    $cid      = get_option('moshare_cid');
    $location = get_option('moshare_location');
    $classic  = ($icon == "moshare-button") ? "checked" : "";
    $mini     = ($icon == "moshare-button-mini") ? "checked" : "";
    $top      = ($location == "top") ? "checked" : "";
    $bottom  = ($location == "bottom") ? "checked" : "";

    echo '
        <div class="wrap">
        <h2>'.__('moShare Options', 'moshare').'</h2>
        <div style="padding:10px;border:1px solid #aaa;background-color:#9fde33;text-align:center;display:none;" id="moshare_updated">Your options were successfully updated</div>
        <form id="ak_moshare" name="ak_moshare" action="' . get_bloginfo('wpurl') .'/wp-admin/index.php">
        <fieldset class="options">
        <h3>Pick up your style</h3>
        <input type="radio" name="moshare_icon" value="moshare-button" '. $classic .' /> <img src="http://www.mogreet.com/moshare/embed/moshare.png"/>
        <input type="radio" name="moshare_icon" value="moshare-button-mini"'. $mini .' /> <img src="http://www.mogreet.com/moshare/embed/moshare_chicklet.png"/>
        <h3>Choose the location</h3>
        <input type="radio" name="moshare_location" value="top" '. $top .' /> Top of the post
        <input type="radio" name="moshare_location" value="bottom"'. $bottom .' /> Bottom of the post
        <h3>Set up your campaign ID (not required)</h3>
        <input type="text" name="moshare_cid" value="'. $cid .'" />
        </fieldset>
        <br/>
        <input type="submit" name="submit_button" value="'.__('Update moShare Options', 'moshare').'" />
        <input type="hidden" name="moshare_action" value="moshare_update_settings" />
        </form></div>';
}

/**
 * Adds moShare to the Menu
 */
function moshare_menu_items() {
    add_options_page(
        __('moShare Options', 'moshare')
        , __('moShare', 'moshare')
        , manage_options
        , basename(__FILE__)
        , 'moshare_options_form'
    );
} 

/**
 * Updates moShare options
 */
function moshare_request_handler() {
    $action = $_REQUEST['moshare_action'];
    $icon   = $_REQUEST['moshare_icon'];
    $cid    = $_REQUEST['moshare_cid'];
    $location = $_REQUEST['moshare_location'];

    if (isset($action, $icon, $cid, $location) && $action == "moshare_update_settings") {
        update_option('moshare_icon', $icon);
        update_option('moshare_cid', $cid);
        update_option('moshare_location', $location);
        header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=moshare.php&updated=true');
        die();
    }
}

/**
 * Includes the moShare JavaScript once per page
 */
function moshare_scripts() {
    wp_enqueue_script("moshare", "http://www.mogreet.com/moshare/embed/moshare.js", array(), "1.0", true);
}

add_action('admin_menu', 'moshare_menu_items');
add_action('init', 'moshare_request_handler', 9999);
add_filter('the_content', 'moshare_add_widget');
add_action('wp_enqueue_scripts', 'moshare_scripts');

?>

