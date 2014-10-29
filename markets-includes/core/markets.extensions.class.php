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
			
			add_action('save_post', array(&$this,'updated_ids'));

			add_action( 'wp_ajax_markets_sync_'. $this->plugin_slug, array(&$this, 'sync') );
			add_action( 'wp_ajax_markets_restore_'. $this->plugin_slug, array(&$this, 'restore') );
			add_action( 'wp_ajax_markets_delete_'. $this->plugin_slug, array(&$this, 'delete') );
			add_action( 'wp_ajax_markets_download_'. $this->plugin_slug, array(&$this, 'download') );
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
					$total = count($data);
					$wordpress_key = Markets_Api::get_wp_key();
					$marketid = $settings['config']['id'];
					$marketkey = $settings['config']['key'];
					$market = Markets_Api::post("market/sync/$marketid/$marketkey/$wordpress_key", array('products' => Markets_Api::array_to_json($data)));
					if($market['success']){
						$response["message"] = __("$total Products synced","markets");
					}
				}else{
					$response["message"] = __("No products found to sync","markets");
				}
			}else{
				$response["message"] = __("Plugin is not active","markets");
			}
			$response["message"] .= " ." . $this->update_products();
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
			$response = array("message" => __("An error occured. Please try again in afew minutes","markets"));
			global $markets;
			$settings = $markets->get_settings();
			if($this->is_active()){
				$wordpress_key = Markets_Api::get_wp_key();
				$marketid = $settings['config']['id'];
				$marketkey = $settings['config']['key'];

				//We first check if the user has posts to be pulled before we delete.

				$saved_products = Markets_Api::post("market/restore/$marketid/$marketkey/$wordpress_key", array('slug' => $this->plugin_id));
				if($saved_products){
					if(is_array($saved_products) && count($saved_products) > 0){
						//We first clear the old custom posts
						$products = get_posts(array('post_type' => $this->post_type , 'posts_per_page' => '-1'));
						if (is_array($products) && count($products) > 0) {
							if($products){
								foreach ($products as $product) {
									wp_delete_post( $product->ID, TRUE );
								}
							}
						}
						$count = 0;
						$saved_products = $saved_products['products'];
						foreach($saved_products as $saved_product){
							 $post_data = array(
										'import_id' => substr($saved_product['id'], strlen($this->plugin_id)),
										'post_title' => $saved_product['name'],
										'post_content' => $saved_product['description'],
										'post_status' => 'publish',
										'post_type' => $this->post_type
										);
							$new_post_id = wp_insert_post($post_data);
							if ($new_post_id) {
								update_post_meta($new_post_id, 'price', $saved_product['price']);
								$count++;
							}
						}
						$response["message"] = __("$count Products restored","markets");						
					}
				}else{
					$response["message"] = __("No products have been saved on the server. Please do a sync before restoring","markets");
				}

				
			}
			header('Content-type: application/json; charset=utf-8');
			echo Markets_Api::array_to_json($response);
			exit();
		}

		/**
		 * Delete from server
		 */
		function delete(){
			check_ajax_referer( $this->plugin_slug.'_markets_nonce', 'security' );
		}

		/**
		 * Download from server
		 *
		 */
		function download(){
			check_ajax_referer( $this->plugin_slug.'_markets_nonce', 'security' );
		}

		function get_data(){
			global $wpdb;
			global $markets;
			
			$settings = $markets->get_settings();
			$last_post_id = 0;
			$latest_id = $settings['plugins'][$this->plugin_slug]['latest_id'];
			if(!empty($latest_id)){
				$last_post_id = $latest_id;
			}
			
			
			$sql = "SELECT * FROM $wpdb->posts
					WHERE $wpdb->posts.post_status = 'publish'
					AND $wpdb->posts.post_type = '$this->post_type'
					AND ID > $last_post_id 
					ORDER BY post_date DESC";
			$data = array();
							
			$posts_array = $wpdb->get_results($sql);
			
			if (is_array($posts_array) && count($posts_array) > 0) {
				$last_id = 0;
				foreach ($posts_array as $product) {
					$data[] = $this->product_json($product);
					$last_id = $product->ID;
				}
				$settings['plugins'][$this->plugin_slug]['latest_id'] = $last_id;
				$markets->save_settings($settings);
			}

			return $data;
		}
		
		/**
		 * Update products
		 */
		function update_products(){
			$response = array();
			$response["message"] = __(" No products updated","markets");
			global $wpdb;
			global $markets;
			$settings = $markets->get_settings();
			$updated_ids = $settings['plugins'][$this->plugin_slug]['updated_ids'];
			if(!empty($updated_ids)){
				$updated_ids = implode(",",$updated_ids);
				$sql = "SELECT * FROM $wpdb->posts
						WHERE $wpdb->posts.post_status = 'publish'
						AND $wpdb->posts.post_type = '$this->post_type'
						AND ID IN ($updated_ids) 
						ORDER BY post_date DESC";
				$data = array();
								
				$posts_array = $wpdb->get_results($sql);

				if (is_array($posts_array) && count($posts_array) > 0) {
					$data = array();
					foreach ($posts_array as $product) {
						$data[] = $this->product_json($product);
					}
					if(!empty($data)){
						$total = count($data);
						$wordpress_key = Markets_Api::get_wp_key();
						$marketid = $settings['config']['id'];
						$marketkey = $settings['config']['key'];
						$market = Markets_Api::post("market/update/$marketid/$marketkey/$wordpress_key", array('products' => Markets_Api::array_to_json($data)));
						if($market['success']){
							unset($settings['plugins'][$this->plugin_slug]['updated_ids']);
							$settings['plugins'][$this->plugin_slug]['updated_ids'] = array();
							$markets->save_settings($settings);
							$response["message"] = __(" $total Products updated","markets");
						}
					}
				}
			}
			return $response['message'];
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
		
		/**
		 * Get the updated products so we update them
		 *
		 */
		function updated_ids($post_id){
			global $markets;
			$settings = $markets->get_settings();
			$settings['plugins'][$this->plugin_slug]['updated_ids'][] = $post_id;
			$markets->save_settings($settings);
		}
	}
}
?>