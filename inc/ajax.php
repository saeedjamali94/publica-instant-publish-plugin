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
    $meta_title = $_POST['meta_title'] ?: false;
    $meta_gist = $_POST['meta_gist'] ?: false;
    $meta_desc = $_POST['meta_desc'] ?: false;
    $tags =  $_POST['selected_tags'] ?: false;

    if( !$html_content || !$reportage_title ){
        echo wp_json_encode([
            'status' => false,
            'message' => 'لطفا عنوان رپورتاژ و محتوا ارسال شوند'
        ]);
        die();
    }

    // parse html content to fix images and upload them
    $HTMLParser = new HTMLParser( $html_content );
    $parseResult = $HTMLParser->parse();

    $parsed_html = $parseResult->fullContent;
    $count_H1 = $parseResult->elementH1['count'];

    if ($count_H1 > 1){
        echo wp_json_encode([
            'status' => false,
            'message' => 'افزودن بیش از یک h1 در محتوا مجاز نیست'
        ]);
        die();
    }
    
    // Define reportage post data
    $post_data = array(
        'post_title'    => $reportage_title,
        'post_content'  => $parsed_html,
        'post_status'   => 'publish',
        'post_author'   => 1, // user ID of the author
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

    // now build sent tags in wordpress , but first check if they exist
    if( $tags ){
        $selected_tags_array = explode(',', $tags);
        // Trim spaces around each item
        $selected_tags_array = array_map('trim', $selected_tags_array);
        if( count($selected_tags_array) > 0 ){
            foreach( $selected_tags_array as $tag_item ){
                $exists = term_exists( $tag_item, 'post_tag' );
                if( $exists ){
                    wp_set_post_terms( $post_id, $tag_item, 'post_tag', true );
                }
                else {
                    // Try to create the tag 
                    $new_term = wp_insert_term( $tag_item, 'post_tag' );
                    $new_term_id = $new_term['term_id'];
                    wp_set_post_terms( $post_id, array( $new_term_id ), 'post_tag', true );

                }
            }
        }
    }
    

    // set reportage category for the post , first check if this category exists
    $search = 'رپورتاژ'; // part of the category name you want to match

    $categories = get_terms([
        'taxonomy'   => 'category',
        'hide_empty' => false,
        'name__like' => $search,
    ]);

    if ( !empty($categories) && !is_wp_error($categories) ) {
        foreach ($categories as $cat) {
            // attach this existing category to the post
            wp_set_post_categories( $post_id, array( $cat->term_id ), true );
        }
    }
    else {
       
        // 1. Try to create the category with given name
        $category_name = "رپورتاژ آگهی";
        $created_term = wp_insert_term( $category_name, 'category' );

        $created_category_id = $created_term['term_id'];

        // 2. Attach the category to the post
        wp_set_post_categories( $post_id, array( $created_category_id ), true );
    }

    // now remove default category (id = 1) from this post
    wp_remove_object_terms( $post_id, 1, 'category' );

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

