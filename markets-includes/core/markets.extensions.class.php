<?php

if(!class_exists('Markets_Extensions')) {

	class Markets_Extensions{

		//Name of the plugin.
		var $plugin_name = '';
		
		//shortname of plugin
		var $plugin_slug = '';

		//main file of plugin
		var $plugin_file = '';

		//id of plugin
		var $plugin_id = '';

		var $post_type = '';

		function __construct() {

			if (empty($this->plugin_name) || empty($this->plugin_slug) || empty($this->plugin_id) || empty($this->plugin_file) || empty($this->post_type))
				wp_die( __("Extension not set up properlly", "markets") );

			//Initialise
			$this->init();

			add_action( 'wp_ajax_markets_sync_'. $this->plugin_slug, array(&$this, 'sync') );
			add_action( 'wp_ajax_markets_restore_'. $this->plugin_slug, array(&$this, 'restore') );
		}

		/**
		 * Initialise the plugin and settings
		 *
		 */
		function init(){
			global $markets;
			$settings = $markets->get_settings();
			if(empty($settings['plugins'][$this->plugin_slug]['name'])){
				$settings['plugins'][$this->plugin_slug]['name'] = $this->plugin_name;
				$settings['plugins'][$this->plugin_slug]['id'] = $this->plugin_id;
				$settings['plugins'][$this->plugin_slug]['file'] = $this->plugin_file;
				$markets->save_settings($settings);
			}
		}


		/**
		 * Check if the plugin is active
		 *
		 */
		function is_active(){
			return is_plugin_active($this->plugin_file);
		}

		/**
		 * Sync Products
		 *
		 */
		function sync(){
			check_ajax_referer( $this->plugin_slug.'_markets_nonce', 'security' );
			$response = array();
			$response["message"] = __("Error Syncing. Please try again later","markets");
			global $markets;
			$settings = $markets->get_settings();
			if($this->is_active()){
				$data = $this->get_data();
				if(!empty($data)){
					$wordpress_key = Markets_Api::get_wp_key();
					$marketid = $settings['config']['id'];
					$marketkey = $settings['config']['key'];
					$market = Markets_Api::post("market/sync/$marketid/$marketkey/$wordpress_key", array('products' => Markets_Api::array_to_json($data)));
					if($market['success']){
						$response["message"] = __("Products synced","markets");
					}
				}else{
					$response["message"] = __("No products found to sync","markets");
				}
			}else{
				$response["message"] = __("Plugin is not active","markets");
			}
			header('Content-type: application/json; charset=utf-8');
			echo Markets_Api::array_to_json($response);
			exit();
		}

		/**
		 * Restore Products
		 *
		 */
		function restore(){
			check_ajax_referer( $this->plugin_slug.'_markets_nonce', 'security' );
			$response = array("message" => __("Not yet implemented","markets"));
			global $markets;
			$settings = $markets->get_settings();
			if($this->is_active()){
				
			}
			header('Content-type: application/json; charset=utf-8');
			echo Markets_Api::array_to_json($response);
			exit();
		}

		function get_data(){
			$data = array();
			add_filter( 'posts_where', array(&$this,'filter_since_id'));
			$args = array(
				'order' => 'ASC',
				'numberposts'       => -1,
				'post_type'        => $this->post_type);
			$posts_array = get_posts( $args );
			if (is_array($posts_array) && count($posts_array) > 0) {
				$last_id = 0;
				foreach ($posts_array as $product) {
					$product = array("id");
					$data[] = $this->product_json($product);
					$last_id = $product->ID;
				}
				global $markets;
				$settings = $markets->get_settings();
				$settings['plugins'][$this->plugin_slug]['latest_id'] = $last_id;
				$markets->save_settings($settings);
			}
			remove_filter( 'posts_where', array(&$this,'filter_since_id'));

			return $data;
		}


		function product_json($product){
			return array("id" => $this->plugin_id."".$product->ID,
					"name" => $product->post_title,
					"description" => $product->post_content,
					"price" => $this->get_price($product->ID),
					"categoryid" => implode(",",get_the_category($product->ID)),
					"datecreated" => $product->post_date);
		}

		function get_price($id){
			return get_post_meta($id, 'price', true);
		}


		function filter_since_id($where = ''){
			global $markets;
			$settings = $markets->get_settings();
			$latest_id = $settings['plugins'][$this->plugin_slug]['latest_id'];
			if(!empty($latest_id)){
				$where .= " AND ID > $latest_id";
			}
		    
		    return $where;
		}
	}
}
?>