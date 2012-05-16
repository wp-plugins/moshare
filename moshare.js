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


jQuery(document).ready(function($) {

    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    var setOrder = function() {
        $('#config_settings tr').each(function(index, value) {
            $(this).find('span.count_text').text(index+1);
        });
    };


    if ($('#thumbnail_checkbox').is(':checked')) {
        $('#default_thumbnail_uploader').show();
    }

    setOrder();

    $('#config_settings tbody').sortable({
        helper: fixHelper,
        cursor: 'move',
        update: function(event, ui) {
            var newOrder = $(this).sortable('toArray').toString();
            $('#services_available').val(newOrder);
        },
        stop: function(event, ui) {
            setOrder();
        }
    }).disableSelection();


    $('#thumbnail_checkbox').click(function() {
        $('#default_thumbnail_uploader').toggle($('#thumbnail_checkbox').checked);
    });


    $('#upload_image_button').click(function() {
        formfield = $('#upload_image').attr('name');
        tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
        return false;
    });

    window.send_to_editor = function(html) {
        imgurl = $('img',html).attr('src');
        $('#upload_image').val(imgurl);
        tb_remove();
    }
});
