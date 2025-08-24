<?php

/*
Plugin Name: Publica Instant Publish
Plugin URI: https://publica.ir
Description: انتشار لحظه ای رپورتاژ پس از خرید از پلتفرم پابلیکا
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: Saeed Jamali
Author URI: https://github.com/saeedjamali94
License: GPLv2 or later
Text Domain: publica
*/

define( 'PUBLICA_VERSION', '1.0.0' );
define( 'PUBLICA_MINIMUM_WP_VERSION', '5.8' );
define( 'PUBLICA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PUBLICA_NAMESPACE' , 'pb');
define( 'PUBLICA_PANEL_DOMAIN' , 'panel.publica.ir');
define( 'PUBLICA_PANEL_IP' , '1.1.1.1');


require_once PUBLICA_PLUGIN_DIR . 'classes/RateLimiter.php';
require_once PUBLICA_PLUGIN_DIR . 'classes/HTMLParser.php';


require_once PUBLICA_PLUGIN_DIR . 'inc/functions.php';
require_once PUBLICA_PLUGIN_DIR . 'inc/ajax.php';


register_activation_hook( __FILE__, 'pbPluginActivation' );