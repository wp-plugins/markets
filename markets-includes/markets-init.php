<?php

if(!class_exists('Markets')) {
	
	class Markets{


		/**
		 * Configures the plugin and future actions.
		 *
		 * @since 1.0
		 */
		public function __construct() {

			//Set default settings
			add_action( 'plugins_loaded', array(&$this, 'default_settings') );

			//Create directories
			add_action( 'init', array(&$this, 'init_directories') );

			//Load classes
			add_action( 'init', array(&$this, 'init_classes') );


			//localize the plugin
			add_action( 'plugins_loaded', array(&$this, 'localization'), 9 );
		}

		/**
		 * Default Settings
		 *
		 * @since 1.0
		 */
		public function default_settings(){
			$settings = $this->get_settings();
			if(empty($settings)){
				$settings = array();
				$settings['config']['id'] = "";
				$settings['config']['key'] = "";
				if( is_multisite() ){
					add_site_option('markets_settings', $settings);
				}else{
					add_option('markets_settings', $settings);
				}
			}
		}

		/**
		 * Create the required directories
		 *
		 * @since 1.0
		 */
		public function init_directories(){
			if (!is_dir(MARKETS_CONTENT_DIR)) {
				mkdir(MARKETS_CONTENT_DIR, 0, true);
				chmod(MARKETS_CONTENT_DIR, 0777);
				$this->blank_file(MARKETS_CONTENT_DIR.'/index.php');
			}
		}

		/**
		 * Create a blank file
		 * 
		 * @since 1.0
		 */
		function blank_file($destination){
			$file_handle = fopen($destination, 'w') or die("can't open file");
			fclose($file_handle);
		}

		/**
		 * Initialise all required classes
		 * 
		 * @since 1.0
		 */
		function init_classes(){
			$this->load_classes(MARKETS_DIR.'/core/', true);
			$this->load_classes(MARKETS_DIR.'/extensions/', true);
		}

		/**
		 * Load Classes in directories
		 * 
		 * @since 1.0
		 */
		private function load_classes($dir= '', $instatiate = false){
			$plugins = array();
			$classes = array();
			if ( !is_dir( $dir ) )
				return;
			if ( ! $dh = opendir( $dir ) )
				return;
				
			while ( ( $plugin = readdir( $dh ) ) !== false ) {
				if ( substr( $plugin, -4 ) == '.php' ){
					$plugins[] = $dir . $plugin;
				}
			}
			closedir( $dh );
			sort( $plugins );
						
			//include them suppressing errors
			foreach ($plugins as $file){
				include_once( $file );
				if($instatiate){
					$fp = fopen($file, 'r');
					$class = $buffer = '';
					$i = 0;
					while (!$class) {
						if (feof($fp)) break;
						$buffer .= fread($fp, 512);
						if (preg_match('/class\s+(\w+)(.*)?\{/', $buffer, $matches)) {
							if($matches[1] != "Markets_Extensions")
								$classes[]  = $matches[1];
							break;
						}
					}
				}
			}
			if($instatiate){
				foreach ($classes as $class){
					$c = new $class();
				}
			}
		}

		/**
		 * Save Settings
		 *
		 * @since 1.0
		 */
		public function save_settings($settings){
			if( is_multisite() ){
				return update_site_option('markets_settings',$settings);
			}else{
				return update_option('markets_settings',$settings);
			}
		}


		/**
		 * Get Settings
		 *
		 * @since 1.0
		 */
		public function get_settings(){
			if( is_multisite() ){
				return get_site_option('markets_settings');
			}else{
				return get_option('markets_settings');
			}
		}


		/**
		 * Get Admin mail
		 *
		 * @since 1.0
		 */
		public function get_admin_mail(){
			if( is_multisite() ){
				return get_site_option('admin_email');
			}else{
				return get_option('admin_email');
			}
		}

		/**
		 * Send Mail
		 */
		public function send_mail($subject, $message){
			$site_name = get_bloginfo('name');
			$defualt_email = $this->get_admin_mail();
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $site_name <$defualt_email>" . "\r\n";
			add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
			@wp_mail($defualt_email, $subject, $message, $headers);
		}


		/**Check if account is connected
		 *
		 * @since 1.0
		 */
		public function is_connected(){
			$settings = $this->get_settings();
			return (!empty($settings['config']['id']));
		}

		/**
		 * Localization
		 *
		 * @since 1.0
		 */
		function localization(){
			$lang_dir = MARKETS_DIR.'/languages';
			load_plugin_textdomain('markets', false, $lang_dir);
		}


		/**
		 * Get Currencies from API and also check cache
		 *
		 * @since 1.0
		 */
		function currencies(){
			$cache_file = 'currencies';
			$cache = Markets_Api::get_cache($cache_file);
			if($cache){
				return $cache;
			}else{
				$countres = Markets_Api::get("countries");
				if($countres){
					Markets_Api::save_cache($cache_file, $countres);
					return $countres;
				}
			}
		}
	}
}
/**
 * Load plugin function during the WordPress init action
 *
 * @since 3.6.2
 *
 * @return void
 */
function markets_action_init() {
	global $markets;
	$markets = new Markets();
}

add_action( 'init', 'markets_action_init', 0 ); // load before widgets_init at 1
?>