<?php


class HTMLParser
{

    public string $html_string;
    
    public function __construct( $html_string = '' ){
        $this->html_string = $html_string;
    }

    /**
     * Extract all html needed info from text
     */
    public function parse(){

        if( trim($this->html_string) == '' ){
            return false;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $findTag = $doc->loadHTML(mb_convert_encoding($this->html_string, 'HTML-ENTITIES', 'UTF-8'));

        // find all img tags
        $elementImg = $doc->getElementsByTagName('img');
        $elementImgSrc = [];
        foreach ($elementImg as $index => $value){

            $alt = $value->getAttribute('alt');
            // Replace dangerous double quotes inside alt with safe quotes
            $fixedAlt = str_replace(['"', '\'', '\\'], ['«', ' ', ' '], $alt);
            $value->setAttribute('alt', $fixedAlt);

            $raw_base64_src = str_replace('\"' , '' , $value->getAttribute('src'));
            $elementImgSrc[] = [ 'src' => $raw_base64_src, 'alt' => $fixedAlt];

            // upload image on server instead of using base64 url
            $uploaded_url = $this->uploadImage($raw_base64_src, "rp-img-".rand(1 , 100000));
            $value->setAttribute('src' , $uploaded_url);

        }
        $lengthImg  = count($elementImgSrc);


        // find all H1 tags
        $elementH1= $doc->getElementsByTagName('h1');
        $elementArrayH1 = [];
        foreach ($elementH1 as $index => $value){
            // if H1 is Not Empty
            if( trim($value->nodeValue) !== "" ){
                $elementArrayH1[] = $value->nodeValue;
                $snippetH1 .= $value->nodeValue;
            }
            else {
                // remove empty H1
                $parentNode = $value->parentNode;
                $parentNode->removeChild($value);
            }
        }
        $lengthH1  = count($elementArrayH1);
        $explodeH1 = explode(' ' ,$snippetH1);
        $explodeHCount1 = count($explodeH1);

        $modifiedHtml = $doc->saveHTML();


        $elementAll = (object) [
            'elementImg' => array ('count' => $lengthImg , 'items' => $elementImgSrc) ,
            'elementH1'  => array('count' => $lengthH1 , 'items' =>  $elementArrayH1) ,
            'fullContent' => $modifiedHtml,
        ];

        return $elementAll;

    }


   
    /**
     * Upload image to server instead of using Base64
     */
    private function uploadImage($base64_img, $title, $max_size = []) {


        // option to resize image - will save space on host usualy don't need image more than 1920 x 1080
        // read more information on :
        // https://developer.wordpress.org/reference/functions/image_resize_dimensions/
        // https://developer.wordpress.org/reference/classes/wp_image_editor_imagick/resize/
        // or
        // https://developer.wordpress.org/reference/classes/wp_image_editor_gd/resize/
        // crop = false
        // or
        // crop[0] = center|left|right
        // crop[1] = center|top|bottom
        //
        // also if one of "width" or "height" = 0 the image will keep ratio
        // if "width" and "height" = 0 image will not be croped

        $max_size = array_merge([
            'width' => 0,
            'height' => 0,
            'crop' => ['center', 'center'],
            'dpi'=>96, // usualy for web is I see 72
        ], $max_size);

        // with fix for  data:image/jpeg;charset=utf-8;base64,
        // https://en.wikipedia.org/wiki/Data_URI_scheme#Syntax
        // generate corect filename as WP
        // extract entire data mime type ex data:image/jpeg or data:text/plain
        //
        $data_src = substr($base64_img, 0, strpos($base64_img, "base64"));
        $mime_type = substr($base64_img, 0, strpos($base64_img, ";"));

        // extract mime type ex image/jpeg or text/plain
        $mime_type = substr($mime_type, 5);

        // fix: "data: image"
        $mime_type = str_replace(' ', '', $mime_type);

        // make sure is image/*  I make a limitation on image but you can skip this for other mime_types
        if (strpos($mime_type, 'image') === false) {
            return false;
        }

        // return extension if false return
        $ext = wp_get_default_extension_for_mime_type($mime_type);
        if (!$ext) {
            return false;
        }

        // Upload dir.
        $upload_dir = wp_upload_dir();
        $upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

        //this is optional but you make sure you don't generate Name.jpg and also name.jpg
        $title = strtolower($title);

        // set file name and make sure is unique tile will be sanitized by WP
        $filename = $title . '.' . $ext;
        $filename = wp_unique_filename($upload_dir['path'], $filename, null);

        // get image content and decode it
        $img_content = str_replace($data_src . 'base64,', '', $base64_img);
        $img_content = str_replace(' ', '+', $img_content);

        // decode in chunks for  large images fix
        $decoded = "";
        for ($i = 0; $i < ceil(strlen($img_content) / 256); $i++) {
            $decoded = $decoded . base64_decode(substr($img_content, $i * 256, 256));
        }
        $img_content = $decoded;


        $the_file = $upload_path . DIRECTORY_SEPARATOR . $filename;

        // Save the image in the uploads directory.
        file_put_contents($the_file, $img_content);

        // set max DPI for jpg, png, gif, webp before any resize
        //setImageDpi($the_file, count($max_size) != 0 ? $max_size['dpi'] : '');


        // resize image
        if (!empty($max_size['width']) || !empty($max_size['height'])) {
            $image = wp_get_image_editor($the_file); // Return an implementation that extends WP_Image_Editor
            if (!is_wp_error($image)) {
                $image->resize((int) $max_size['width'], (int) $max_size['height'], $max_size['crop']);
                $image->save($the_file);
            }
        }


        $attachment = array(
            'post_mime_type' => $mime_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $upload_dir['url'] . '/' . $filename
        );

        $attach_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $filename);


        // make sure function wp_generate_attachment_metadata exist
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, trailingslashit($upload_dir['path']) . $filename);

        wp_update_attachment_metadata($attach_id, $attach_data);

        return wp_get_attachment_url($attach_id);
    }

    private function setImageDpi($file, $dpi) {
        $orig = getimagesize($file);

        switch ($orig[2]) {
            case 1:
                $src = imagecreatefromgif($file);
                break;
            case 2:
                $src = imagecreatefromjpeg($file);
                break;
            case 3:
                $src = imagecreatefrompng($file);
                break;
            case 18:
                $src = imagecreatefromwebp($file);
                break;
            default:
                return '';
                break;
        }

        if (empty($src))
            return'';
        $res=imageresolution($src);
        $res[0]= min($res[0],$dpi);
        $res[1]= min($res[1],$dpi);
        imageresolution($src, $res[0], $res[1]);
        switch ($orig[2]) {
            case 1:
                $src = imagegif($src, $file);
                break;
            case 2:
                $src = imagejpeg($src, $file);
                break;
            case 3:
                $src = imagepng($src, $file);
                break;
            case 18:
                $src = imagewebp($src, $file);
                break;
            default:
                return '';
                break;
        }
    }

}