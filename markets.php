<?php
/*
Plugin Name: Markets
Description: Markets is an e-commerce plugin that allows you to sync your products. The markets API manages all your products in the database allowing you to sync your products on the supported e-commerce extensions . The Markets plugin allows you to sync products across supported e-commerce plugins and import them into other plugins activated in your WordPress installation. This allows you to try out and share products across different e-commerce WordPress plugins
Version: 1.0.3
Author: rixeo
Author URI: http://thebunch.co.ke/
Plugin URI: http://markets.thebunch.co.ke/
Text Domain: markets
*/

//Lets define constants
define('MARKETS_PLUGIN_BASENAME',plugin_basename(__FILE__));
define('MARKETS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MARKETS_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)));

define('MARKETS_URL', MARKETS_PLUGIN_URL.'/markets-includes');
define('MARKETS_DIR', MARKETS_PLUGIN_DIR.'/markets-includes');


define('MARKETS_DATA_URL', content_url().'/markets'); //Store data outside plugin directory
define('MARKETS_CONTENT_DIR', WP_CONTENT_DIR.'/markets');

define('MARKETS_API', 'http://api.markets.thebunch.co.ke/'); //Api url
define('MARKETS_APPLICATION', 'wp'); //To help us know its comming from WordPress and allow us to control abuse access

require_once(MARKETS_DIR.'/markets-init.php');
?>