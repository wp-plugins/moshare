<?php
/*
    Plugin Name: MoShare 
    Plugin URI: http://corp.mogreet.com 
    Description: Let users share your content via MMS using the Mogreet Messaging Platform
    Version: 1.0
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
 * Adds the MoShare embed code to each post/page
 */
function moshare_add_widget($content) {
    global $post;

    $url         = get_permalink($post->ID);
    $title       = get_the_title();

    $description = strip_tags($content);
    $description = trim($description);

    if (strlen($description) > MAX_LENGTH_DESCRIPTION) {
        $description = substr($description, 0, MAX_LENGTH_DESCRIPTION);
        $description .= " ...";
    }
    $description = preg_replace('/\\\"/', '&quot;', $description);

    $first_img   = '';
    $doc = DOMDocument::loadHTML($content);
    $images = $doc->getElementsByTagName("img");
    foreach ($images as $image) {
        if ($image->getAttribute('class') != "wp-smiley") {
            $first_img = $image->getAttribute('src');
            break;
        }
    }

    $logo = get_option('moshare_icon');
    if ($logo == '') {
        update_option('moshare_icon', 'moshare-button');
    }
    $cid  = get_option('moshare_cid');

    $html = "<a href='http://www.mogreet.com/moshare/it/' class='$logo'"
        . " data-description='$description' data-type='article'"
        . " data-location='$url' data-title='$title'";

    if ($first_img != '') {
        $html .= " data-thumbnail='$first_img'";
    }
    if ($cid != '') {
        $html .= " data-cid='$cid'";
    }
    $html .= '></a>';

    echo $content . $html;
}

/*
 * MoShare options form
 * - customize the widget
 * - set up the campaign ID
 */
function moshare_options_form() {
    $icon    = get_option('moshare_icon');
    $cid     = get_option('moshare_cid');
    $classic = ($icon == "moshare-button") ? "checked" : "";
    $mini    = ($icon == "moshare-button-mini") ? "checked" : "";

    echo '
        <div class="wrap">
        <h2>'.__('MoShare Options', 'moshare').'</h2>
        <div style="padding:10px;border:1px solid #aaa;background-color:#9fde33;text-align:center;display:none;" id="moshare_updated">Your options were successfully updated</div>
        <form id="ak_moshare" name="ak_moshare" action="' . get_bloginfo('wpurl') .'/wp-admin/index.php">
        <fieldset class="options">
        <h3>Pick up your style</h3>
        <input type="radio" name="moshare_icon" value="moshare-button" '. $classic .' /> <img src="http://www.mogreet.com/moshare/embed/moshare.png"/>
        <input type="radio" name="moshare_icon" value="moshare-button-mini"'. $mini .' /> <img src="http://www.mogreet.com/moshare/embed/moshare_chicklet.png"/>
        <h3>Set up your campaign ID (not required)</h3>
        <input type="text" name="moshare_cid" value="'. $cid .'" />
        </fieldset>
        <br/>
        <input type="submit" name="submit_button" value="'.__('Update MoShare Options', 'moshare').'" />
        <input type="hidden" name="moshare_action" value="moshare_update_settings" />
        </form></div>';
}

function moshare_menu_items() {
    add_options_page(
        __('MoShare Options', 'moshare')
        , __('MoShare', 'moshare')
        , manage_options
        , basename(__FILE__)
        , 'moshare_options_form'
    );
} 

/**
 * Updates MoShare options
 */
function moshare_request_handler() {
    $action = $_REQUEST['moshare_action'];
    $icon   = $_REQUEST['moshare_icon'];
    $cid    = $_REQUEST['moshare_cid'];

    if (isset($action, $icon, $cid) && $action == "moshare_update_settings") {
        update_option('moshare_icon', $icon);
        update_option('moshare_cid', $cid);
        header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=moshare.php&updated=true');
        die();
    }
}

/**
 * Includes the MoShare JavaScript once per page
 */
function moshare_scripts() {
    wp_enqueue_script("moshare", "http://www.mogreet.com/moshare/embed/moshare.js", array(), "1.0", true);
}

add_action('admin_menu', 'moshare_menu_items');
add_action('init', 'moshare_request_handler', 9999);
add_filter('the_content', 'moshare_add_widget');
add_action('wp_enqueue_scripts', 'moshare_scripts');

?>
