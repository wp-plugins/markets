<?php
if(!class_exists('Markets_Admin')) {

	class Markets_Admin{

		/**
		 * Configures the plugin admin.
		 *
		 * @since 1.0
		 */
		public function __construct() {
			add_action('admin_print_styles', array($this, 'scripts') );
			add_action('admin_print_scripts', array($this, 'styles') );

			add_action('admin_menu', array(&$this, 'admin_menu'));
		}

		/**
		 * Load the required javascripts
		 * 
		 * @since 1.0
		 */
		function scripts(){
			wp_enqueue_script('markets_js', MARKETS_URL.'/js/script.js', array('jquery'), '', false);
			wp_enqueue_script("markets_js");
			wp_localize_script( 'markets_js', 'mrkts', array( 
				'processing' => __("Processing. Please wait", "markets")
			) );
		}

		/**
		 * Load the required css files
		 * 
		 * @since 1.0
		 */
		function styles(){
			wp_enqueue_style('markets_css', MARKETS_URL.'/css/style.css');
		}

		/**
		 * Lets create the admin menu
		 * 
		 * @since 1.0
		 */
		function admin_menu(){
			if ( current_user_can('manage_options') && current_user_can('edit_others_posts')){
				add_object_page( __('Markets', 'markets'), __('Markets', 'markets'), 'edit_others_posts', 'markets', '', MARKETS_URL . '/images/icon.png');
				add_submenu_page('markets', __('Markets', 'markets'), __('Markets', 'markets'), 'edit_others_posts', 'markets', array(&$this, 'settings'));
				add_submenu_page('markets', __('Product Management', 'markets'), __('Product Management', 'markets'), 'edit_others_posts', 'markets-products', array(&$this, 'manage_products'));
			}
		}

		/**
		 * Admin Settings
		 * 
		 * @since 1.0
		 */
		function settings(){
			// Use nonce for verification
			wp_nonce_field( MARKETS_PLUGIN_BASENAME, 'markets_noncename' );

			//Check if the current user has permission to access this page
			if (!current_user_can('manage_options')) {
				wp_die(__('You do not have sufficient permissions to access this page.','markets'));
			}

			global $markets;

			if(! empty($_REQUEST['logout'])){
				if($_REQUEST['logout']){
					if($markets->is_connected()){
						$settings = $markets->get_settings();
						$settings['config']['name'] = "";
						$settings['config']['description'] = "";
						$settings['config']['countryid'] = "";
						$settings['config']['id'] = "";
						$settings['config']['key'] = "";
						$markets->save_settings($settings);
						?>
						<div id="message" class="error fade">
							<h3><?php _e('Logout Successful','markets'); ?></h3>
						</div>
						<?php
					}
				}
			}

			$wordpress_key = Markets_Api::get_wp_key();

			if (! empty( $_POST ) && check_admin_referer('markets_settings','markets_noncename') ){
				$settings = $markets->get_settings();
				if(!$markets->is_connected()){
					$response = __('All fields required','markets');
					if(!empty( $_POST['marketid'] )){
						$marketid = sanitize_text_field($_POST['marketid']);
						$marketkey = sanitize_text_field($_POST['marketkey']);
						$market = Markets_Api::get("market/$marketid/$marketkey/$wordpress_key");
						if($market){
							$settings['config']['name'] = $market['name'];
							$settings['config']['description'] = $market['description'];
							$settings['config']['countryid'] = $market['countryid'];
							$settings['config']['id'] = $marketid;
							$settings['config']['key'] = $marketkey;
							$response = __('Login Successful','markets');
						}else{
							$response = __('Market not found. Please check your login credentials','markets');
						}
					}else if(!empty( $_POST['marketname'] )){
						$marketname = sanitize_text_field($_POST['marketname']);
						$marketcurrency = sanitize_text_field($_POST['marketcurrency']);
						$marketdesc = sanitize_text_field($_POST['marketdesc']);
						if(!empty($marketname) && !empty($marketcurrency) && !empty($marketdesc)){
							$market = Markets_Api::post("market/$wordpress_key", array('name' => $marketname,
																						 'countryid' => $marketcurrency,
																						 'description' => $marketdesc));
							if($market){
								if($market['success']){
									$settings['config']['id'] = $market['id'];
									$settings['config']['key'] = $market['key'];
									$settings['config']['countryid'] = $marketcurrency;
									$settings['config']['description'] = $marketdesc ;
									$settings['config']['name'] = $marketname;
									$response = __('Market saved successfully','markets');

									$message = __("Welome to Markets","markets");
									$message .= "<br/>";
									$message .= __("Your Market ID is ","markets");
									$message .= $market['id'];
									$message .= "<br/>";
									$message .= __("Your Market Key is ","markets");
									$message .= $market['key'];


									$subject = __("Welome to Markets","markets");

									$markets->send_mail($subject,$message); //Send mail
								}else{
									$response = __('Error saving market','markets');
								}
							}else{
								$response = __('Error saving market','markets');
							}
						}else{
							$response = __('Please fill in all required registration fields','markets');
						}
					}
				}else{
					//Update market settings
					$response = __('All fields required','markets');
					$marketname = sanitize_text_field($_POST['name']);
					$marketcurrency = sanitize_text_field($_POST['currency']);
					$marketdesc = sanitize_text_field($_POST['description']);
					if(!empty($marketname) && !empty($marketcurrency) && !empty($marketdesc)){
						$marketid = $settings['config']['id'];
						$marketkey = $settings['config']['key'];
						$market = Markets_Api::post("market/up/$marketid/$marketkey/$wordpress_key", array('name' => $marketname,
																					'countryid' => $marketcurrency,
																					'description' => $marketdesc));
						if($market){
							$settings['config']['name'] = $market['name'];
							$settings['config']['description'] = $market['description'];
							$settings['config']['countryid'] = $market['countryid'];
							$response = __('Update Successful','markets');
						}else{
							$response = __('Error updating. Try again shortly','markets');
						}
					}
				}
				$markets->save_settings($settings);
				?>
				<div id="message" class="error fade">
					<h3><?php echo $response; ?></h3>
				</div>
				<?php
			}
			$currencies = $markets->currencies();
			?>
			<div class="wrap">
				<h2><?php _e('Markets Settings','markets'); ?></h2>
				<div id="message" class="update-nag">
					<p>
						<?php _e("The Markets plugin allows you to sync products across supported e-commerce plugins and import them into other plugins activated in your WordPress installation. This allows you to try out and share products across different e-commerce WordPress plugins","markets");?>
					</p>
				</div>
				<?php if(!$markets->is_connected()){ ?>
				<div class="actionbtns">
					<button id="logintable" class="login actionbtn button-primary"><?php _e('Login','markets'); ?></button>
					<button id="registrationtable" class="register actionbtn button-primary"><?php _e('Register','markets'); ?></button>
				</div>
				<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
					<?php wp_nonce_field('markets_settings','markets_noncename'); ?>
					<div class="logintable">
						<table class="form-table widefat">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="marketid"><?php _e('Market ID','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="" id="marketid" name="marketid" />
										<p class="description"><?php _e('Your Market ID','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="marketkey"><?php _e('Market Key','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="" id="marketkey" name="marketkey" />
										<p class="description"><?php _e('Your Market Key','markets'); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
						<p class="submit">
							<input class='button-primary' type='submit' value="<?php _e('Login','markets'); ?>"/ >
						</p>
					</div>
					<div class="registrationtable" style="display:none">
						<table class="form-table widefat">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="marketname"><?php _e('Market Name','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="" id="marketname" name="marketname" />
										<p class="description"><?php _e('The name of your market','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="marketcurrency"><?php _e('Market Currency','markets'); ?> :</label>
									</th>
									<td>
										<select name="marketcurrency">
											<option><?php _e('Select Currency','markets'); ?></option>
											<?php
												foreach ($currencies as $k => $v) {
													echo '<option value="' . $v['id'] . '">' . esc_html($v['country']) . '</option>' . "\n";
												}
											?>
										</select>
										<p class="description"><?php _e('The currency for your market','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="marketdesc"><?php _e('Market Description','markets'); ?> :</label>
									</th>
									<td>
										<textarea name="marketdesc" class="large-text code"  cols="50" rows="10"></textarea>
										<p class="description"><?php _e('Brief description about your market','markets'); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
						<p class="submit">
							<input class='button-primary' type='submit' value="<?php _e('Register','markets'); ?>"/ >
						</p>
					</div>
				</form>
				<script type="text/javascript">
					jQuery(document).ready(function(){
						jQuery("button.actionbtn").on('click', function(e) {
							markets.toggle(jQuery(this).attr("id"));
						});
					});
				</script>
				<?php }else{
					$settings = $markets->get_settings();
					?>
					<div class="actionbtns">
						<a class='button-primary' href="<?php echo admin_url('admin.php?page=markets&logout=true'); ?>"><?php _e('Logout','markets'); ?></a>
					</div>
					<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
						<?php wp_nonce_field('markets_settings','markets_noncename'); ?>
						<table class="form-table widefat">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="marketid"><?php _e('Market ID','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="<?php echo $settings['config']['id']; ?>" disabled/>
										<p class="description"><?php _e('Your Market ID','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="marketkey"><?php _e('Market Key','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="<?php echo $settings['config']['key']; ?>"  disabled/>
										<p class="description"><?php _e('Your Market Key','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="name"><?php _e('Market Name','markets'); ?> :</label>
									</th>
									<td>
										<input type="text" class="regular-text" value="<?php echo $settings['config']['name']; ?>" name="name" />
										<p class="description"><?php _e('The name of your market','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="currency"><?php _e('Market Currency','markets'); ?> :</label>
									</th>
									<td>
										<select name="currency">
											<option><?php _e('Select Currency','markets'); ?></option>
											<?php
												foreach ($currencies as $k => $v) {
													echo '<option value="' . $v['id'] . '"' . ($v['id'] == $settings['config']['countryid'] ? ' selected' : '') . '>' . esc_html($v['country']) . '</option>' . "\n";
												}
											?>
										</select>
										<p class="description"><?php _e('The currency for your market','markets'); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="description"><?php _e('Market Description','markets'); ?> :</label>
									</th>
									<td>
										<textarea name="description" class="large-text code"  cols="50" rows="10"><?php echo $settings['config']['description']; ?></textarea>
										<p class="description"><?php _e('Brief description about your market','markets'); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
						<p class="submit">
							<input class='button-primary' type='submit' value="<?php _e('Update','markets'); ?>" />
						</p>
					</form>
					<?php
					}  ?>
			</div>
			<?php
		}


		/**
		 * Manage Products
		 *
		 * @since 1.0
		 */
		function manage_products(){
			// Use nonce for verification
			wp_nonce_field( MARKETS_PLUGIN_BASENAME, 'markets_products_noncename' );

			//Check if the current user has permission to access this page
			if (!current_user_can('manage_options')) {
				wp_die(__('You do not have sufficient permissions to access this page.','markets'));
			}

			global $markets;

			if(!$markets->is_connected()){
				wp_die(__('Please login or register for an account first.','markets'));
			}
			$settings = $markets->get_settings();
			?>
			<div class="wrap">
				<h2><?php _e('Markets Settings','markets'); ?></h2>
				<div id="message" class="update-nag">
					<p>
						<?php _e("Manage your products for the supported plugins","markets");?>
						<div class="process_status"></div>
					</p>
				</div>
				<br/><br/><br/>
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col"><?php _e("Name","markets");?></th>
							<th scope="col"><?php _e("Actions","markets");?></th>
						</tr>
					</thead>
					<tbody>
						<?php
							$plugins = $settings['plugins'];
							foreach ($plugins as $key => $value) {
								$active = "";
								$active_text = "";
								if(!is_plugin_active($value['file'])){
									$active = "disabled";
									$active_text = "  (".__("Plugin not activated","markets").")";
								}
								$ajax_nonce = wp_create_nonce($key."_markets_nonce");
								?>
								<tr>
									<td><?php echo $value['name'].$active_text; ?></td>
									<td>
										<button class='button-primary market-sync' data-slug="<?php echo $key; ?>" data-nonce="<?php echo $ajax_nonce; ?>" <?php echo $active; ?> ><?php _e("Sync Products","markets");?></button>&nbsp;&nbsp;<button class='button-primary market-restore' data-slug="<?php echo $key; ?>" data-nonce="<?php echo $ajax_nonce; ?>" <?php echo $active; ?>><?php _e("Download Products","markets");?></button>
									</td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">
					jQuery(document).ready(function(){
						jQuery("button.market-sync").on('click', function(e) {
							markets.sync(jQuery(this));
						});
						jQuery("button.market-restore").on('click', function(e) {
							markets.restore(jQuery(this));
						});
					});
				</script>
			</div>
			<?php
		}
	}
}
?>