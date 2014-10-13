<?php
if(!class_exists('Markets_Api')) {

	class Markets_Api{

		/**
		 * Handle all Post requests
		 *
		 */
		static function post($path, $params = array()){
			$response = wp_remote_post( MARKETS_API.''.$path, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $params,
				'cookies' => array()
			    )
			);
			if ( is_wp_error( $response ) ) {
				return false;
			}else{
				return self::json_to_array($response['body']);
			}
		}

		/**
		 * Handle all Get Requests
		 *
		 */
		static function get($path, $params = array()){

			$response = wp_remote_get( MARKETS_API.''.$path, $params );

			if ( is_wp_error( $response ) ) {
				return false;
			}else{
				return self::json_to_array($response['body']);
			}
		}


		/**
		 * Save Cache
		 *
		 *
		 */
		static function save_cache($cache_file, $data){
			if(!self::is_cached($cache_file)){
				$cache_file = MARKETS_CONTENT_DIR."/".$cache_file.".cache";
				$file_handle = fopen($cache_file, 'w') or die("can't open file");
				fwrite($file_handle, self::array_to_json($data));
				fclose($file_handle);
			}
		}


		/**
		 * Check if File is cached and is not older that one hour
		 *
		 *
		 */
		static function is_cached($cache_file){
			$cache_file = MARKETS_CONTENT_DIR."/".$cache_file.".cache";
			return (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 60 )));
		}


		/**
		 * Get Cache
		 *
		 */
		static function get_cache($cache_file){
			if(self::is_cached($cache_file)){
				$cache_file = MARKETS_CONTENT_DIR."/".$cache_file.".cache";
				return self::json_to_array(file_get_contents($cache_file));
			}else{
				return false;
			}
		}

		static function get_wp_key(){
			$data = self::get("key/".MARKETS_APPLICATION);
			if($data){
				return $data['application'];
			}else{
				return "0";
			}
		}


		/**
		 * Array to Json
		 *
		 */
		static function array_to_json($array){
			return json_encode($array);
		}


		/**
		 * Json To Array
		 *
		 */
		static function json_to_array($json){
			return json_decode($json, true);
		}
	}
}
?>