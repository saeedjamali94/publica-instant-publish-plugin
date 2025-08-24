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
            'status' => 'false',
            'message' => 'لطفا عنوان رپورتاژ و محتوا ارسال شوند'
        ]);
        die();
    }

    $result = [];

    echo wp_json_encode($result);
    die();
}

