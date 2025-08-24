<?php 

/**
 * All AJAX Hooks needed in Plugin
 */

if ( ! defined('ABSPATH')) exit();
if (! defined('PUBLICA_VERSION')) {
    exit;
}




/**
 * Request to get data from publica reportage editor
 */
add_action('wp_ajax_pb_get_editor_data' , 'pb_get_editor_data');
add_action('wp_ajax_nopriv_pb_get_editor_data' , 'pb_get_editor_data');
function pb_get_editor_data(){

    header('Content-Type: application/json; charset=utf-8');

    // check Rate Limit
    PbCheckRateLimit();

    // check if referrer domain is panel.publica.ir
    $remote_ip = $_SERVER["REMOTE_ADDR"];


    // check if content and reportage title and h1 is not empty
    $html_content = $_POST["content"] ?: false;
    $reportage_title = $_POST["title"] ?: false;

    if( !$html_content || !$reportage_title ){
        echo wp_json_encode([
            'status' => false,
            'message' => 'لطفا عنوان رپورتاژ و محتوا ارسال شوند'
        ]);
        die();
    }

    // parse html content to fix images and upload them
    $HTMLParser = new HTMLParser( $html_content );
    $parsed_html = $HTMLParser->parse();
    
    // Define reportage post data
    $post_data = array(
        'post_title'    => $reportage_title,
        'post_content'  => $parsed_html,
        'post_status'   => 'publish',
        'post_author'   => 1, // user ID of the author
        'post_category' => array(1) // category IDs
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);

    if (!$post_id) {
        echo wp_json_encode([
            'status' => false,
            'message' => 'خطا در انتشار رپورتاژ توسط وبسایت رسانه'
        ]);
        die();
    }

    echo wp_json_encode([
        'status' => true,
        'message' => 'رپورتاژ با موفقیت منتشر شد',
        'data' => [
            'reportage_link' => get_the_permalink($post_id),
            'title' => $reportage_title,
        ]
    ]);
    die();
}

