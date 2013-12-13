<?php
/*
Plugin Name: Tap Login
Plugin URI: http://www.azid.ru/
Text Domain: taplogin
Description: Allow your visitors to log in by tapping just one button on their phones, without entering a password.
Version: 1.0
Author: Matrix Platform LLC
License: Public domain
*/

include_once dirname(__FILE__).'/lib/EnvConf.php';
include_once dirname(__FILE__).'/lib/TokenManager.php';
include_once dirname(__FILE__).'/lib/AuthConfirmation.php';
include_once dirname(__FILE__).'/lib/TapAuthorization.php';
include_once dirname(__FILE__).'/lib/SecureRandom.php';


class TapLogin
{
	private static $bAskEmail = false;
	
	public static function showWidget()
	{
		if (@$_GET['loggedout'] == 'true'){
			?>
			<iframe style="display: none" src="//www.azid.ru/auth/?act=logout"></iframe>
			<?php
		}
		
		if (self::$bAskEmail){
			self::requestEmail();
			return;
		}
		
		?>
		<script>
			function onTapLoginLoaded(){
				showTapLogin();
			}
		</script>

		<?php
		self::loadTapLogin();
	}
	
	private static function loadTapLogin()
	{
		$node_id = get_option('taplogin_node_id');
		if (!$node_id)
			throw new Exception("Plase set up TapLogin properly");
		
		?>
		<script>
			
			function showTapLogin(){
				if (typeof TapLogin == 'undefined'){
					setTimeout(showTapLogin, 200);
					return;
				}
				TapLogin.displayAuthIframe();
			}
			
			var TapLoginProperties = new function(){
				this.node_id = <?php echo $node_id ?>;
				this.local_easyxdm_file = '<?php echo plugins_url('easyxdm_name.html', __FILE__) ?>';
				this.onAuth = function(employee_id, auth_token, url){
					var f = document.taploginform;
					f.employee_id.value = employee_id;
					f.auth_token.value = auth_token;
					f.url.value = url;
					f.submit();
				};
			};
			
			(function() {
				var async_js = document.createElement('script');
				async_js.type = 'text/javascript';
				async_js.async = true;
				async_js.src = "//www.azid.ru/js/auth.js";
				async_js.onload = async_js.onreadystatechange = function(){
					if (typeof TapLogin != 'undefined'){
						async_js.onload = async_js.onreadystatechange = null;
						onTapLoginLoaded();
					}
				};
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(async_js, s);
			})();
			
			(function() {
				var div = document.createElement('div');
				div.innerHTML = '<form name="taploginform" style="display: none" method="POST" action="">\
					<input type="hidden" name="employee_id" />\
					<input type="hidden" name="auth_token" />\
					<input type="hidden" name="url" />\
				</form>';
				document.getElementsByTagName('body')[0].appendChild(div);
			})();
			
		</script>

		<?php
	}
	
	public static function handleRequest()
	{
		if (!is_admin())
			wp_enqueue_script('jquery');
		
		if (@$_POST['employee_id'] && @$_POST['auth_token'])
			self::handleLogin();
		elseif (@$_POST['taplogin_request_id'] && @$_GET['check_authorization_response'])
			self::checkAuthorizationResponse();
	}
	
	private static function handleLogin()
	{
		$employee_id = $_POST['employee_id'];
		$objAuthConfirmationResponse = AuthConfirmation::confirm(
				@$_POST['employee_id'], @$_POST['auth_token'], @$_POST['url'], $_SERVER['REMOTE_ADDR']);
		if (!@$objAuthConfirmationResponse->authenticated)
			die("not authenticated: ".print_r($objAuthConfirmationResponse, true));
		
		$current_user = wp_get_current_user();
		$current_user_id = $current_user ? $current_user->ID : null;
		
		$user_id = self::getUserIdByEmployeeId($employee_id);
		if ($user_id && $current_user_id && $user_id != $current_user_id)
			die(__("Your AZid account is already linked to another user of this site", 'taplogin'));
		
		if (!$user_id){ // new AZid user
			if (!$current_user_id){ // login sequence
				$bRequireEmail = get_option('taplogin_require_email');
				$user_email = null;
				if (@$_POST['taplogin_email'] && is_email($_POST['taplogin_email']))
					$user_email = $_POST['taplogin_email'];
				elseif (!$bRequireEmail)
					$user_email = uniqid('', true)."@example.com";
				
				if (!$user_email){
					self::$bAskEmail = true;
					return;
				}
				
				// Create a new user
				$user_id = wp_insert_user(array (
					'user_login' => self::deriveLogin($objAuthConfirmationResponse->login),
					'display_name' => $objAuthConfirmationResponse->login,
					'user_email' => $user_email,
					'first_name' => '',
					'last_name' => '',
					'user_url' => '',
					'user_pass' => wp_generate_password()
				));
				if (!$user_id)
					throw new Exception("failed to create user");
				if ($user_id instanceof WP_Error) // e.g. duplicate email
					die("failed to create user: ".$user_id->get_error_message());
			//	update_user_meta($user_id, 'taplogin_employee_id', $_POST['employee_id']);
				do_action('user_register', $user_id);
			}
			else // linking sequence
				$user_id = $current_user_id;
			self::linkUserIdToEmployeeId($user_id, $employee_id);
		}
		
		//Setup Cookie
		if (!$current_user_id){
			$user_data = get_userdata ($user_id);
			wp_clear_auth_cookie ();
			wp_set_auth_cookie ($user_data->ID, true);
			do_action ('wp_login', $user_data->user_login, $user_data);
			wp_safe_redirect(!empty($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url());
			exit();
		}
	}
	
	
	private static function checkAuthorizationResponse()
	{
		$request_id = $_POST['taplogin_request_id'];
		$arrResponse = TapAuthorization::getAuthorizationResponse($request_id);
		die(json_encode($arrResponse));
	}
	
	private static function requestEmail()
	{
		$objResponse = TapAuthorization::requestData(array('employee_id' => $_POST['employee_id']), 
			array('email'), parse_url(site_url(), PHP_URL_HOST));
		if (!$objResponse)
			throw new Exception("requestData failed");
		if (!$objResponse->request_id)
			throw new Exception("bad response from requestData: ".print_r($objResponse, true));
		$request_id = $objResponse->request_id;
		
		?>
		<div id="taplogin_emaildiv">
			<div style="width: 100%; height: 100%; background-color: #ffffff; position: absolute; top: 0; left: 0; z-index: 1000; opacity: 0.8;"></div>
			<div id="taplogin_popupdiv" style="position: absolute; top: 0; left: 0; z-index: 1001">
				<div id="taplogin_box" style="position: absolute; z-index: 1002; background-color: white; border: 1px solid #aaaaaa; border-radius: 4px; box-shadow: 5px 5px 10px rgba(0,0,0,0.5)">
					<div style="padding: 10px">
						<p id="taplogin_error" style="color: red; font-weight: bold"></p>
						<p><?php _e("Email is a required field. We sent a request to your phone to get your email address stored with AZid.ru. Please approve the request, or enter your another email below", 'taplogin') ?>:</p>
						
							<label for="taplogin_email"><?php _e("E-mail address", 'taplogin') ?>:</label> <input type='text' id="taplogin_email" name="taplogin_email"/> <input type="submit" value="<?php _e("Continue", 'taplogin') ?>" class="button button-primary button-large"/>
							<input type="hidden" name="employee_id" value="<?php echo $_POST['employee_id']; ?>"/>
							<input type="hidden" name="auth_token" value="<?php echo $_POST['auth_token']; ?>"/>
							<input type="hidden" name="url" value="<?php echo $_POST['url']; ?>"/>
						
					</div>
				</div>
			</div>
		</div>
		<script>
			function checkEmail()
			{
				var email = jQuery("#taplogin_email").get(0);
				if (email.value.indexOf('@')<0 || email.value.length<6){
					alert("<?php _e("Please enter a valid email address", 'taplogin')?>");
					email.focus();
					return false;
				}
				return true;
			}
			
			function adjustPopupPosition()
			{
				var widget_width = 500;
			//	jQuery("#taplogin_box").css({width: widget_width+"px"});
				document.getElementById("taplogin_box").style.width = widget_width+"px";
				var widget_height = document.getElementById("taplogin_box").style.height;
				var windowWidth = window.innerWidth;
				var windowHeight = window.innerHeight;
				if (!windowWidth){ // IE 6-8
					if (document.body && document.body.offsetWidth) {
						windowWidth = document.body.offsetWidth;
						windowHeight = document.body.offsetHeight;
					}
					else if (document.compatMode=='CSS1Compat' &&
							document.documentElement &&
							document.documentElement.offsetWidth ) {
						windowWidth = document.documentElement.offsetWidth;
						windowHeight = document.documentElement.offsetHeight;
					}
					if (!windowWidth){
						windowWidth = 600;
						windowHeight = 400;
					}
				}
				var widget_left = windowWidth/2 - widget_width/2;
				var widget_top = windowHeight/2 - widget_height/2;

			//	jQuery("#taplogin_box").css({'left': widget_left+"px", 'top': widget_top+"px", width: widget_width+"px"});
				document.getElementById("taplogin_box").style.left = widget_left+"px";
				document.getElementById("taplogin_box").style.top = widget_top+"px";
			}
			
			var checker;
			
			function stopWaiting()
			{
				clearInterval(checker);
			}
			
			function checkAuthorizationResponse()
			{
				jQuery.post(
					'?check_authorization_response=1',
					{
						taplogin_request_id: <?php echo $request_id; ?>
					},
					function (response){
						if (response.result == 'ok'){
							if (response.response == 'Y'){
								stopWaiting();
								jQuery("#taplogin_email").val(response.arrFieldValues.email);
							//	jQuery("#taplogin_email_form").get(0).submit();
								jQuery("#loginform").get(0).submit();
							}
							else if (response.response == 'N')
								stopWaiting();
						}
						else if (response.result == 'error')
							jQuery("#taplogin_error").html(response.error);
						else
							alert("unrecognized result");
					},
					'json'
				);
			}
			
			jQuery(document).ready(function(){
				// replace the standard login form with our popup
				var h = jQuery("#taplogin_emaildiv").html();
				jQuery("#taplogin_emaildiv").html('');
				jQuery("#loginform").html(h).submit(checkEmail);
				adjustPopupPosition();
				checker = setInterval(checkAuthorizationResponse, 2000);
				setTimeout(stopWaiting, 300000);
			});
		</script>
		<?php
	}


	/**
	 * Derives unique login
	 * 
	 * @param string $user_login suggested login
	 * @return unique login
	 */
	private static function deriveLogin($user_login)
	{
		if (username_exists ($user_login))
		{
			$i = 1;
			$user_login_tmp = $user_login;
			do
			{
				$user_login_tmp = $user_login . ($i++);
			}
			while (username_exists ($user_login_tmp));
			$user_login = $user_login_tmp;
		}
		return $user_login;
	}
	
	private static function getUserIdByEmployeeId($employee_id)
	{
		global $wpdb;
		return $wpdb->get_var("SELECT user_id FROM ".$wpdb->prefix."taplogin_users WHERE employee_id=".$employee_id);
	}
	
	private static function getEmployeeIdByUserId($user_id)
	{
		global $wpdb;
		return $wpdb->get_var("SELECT employee_id FROM ".$wpdb->prefix."taplogin_users WHERE user_id=".$user_id);
	}
	
	private static function linkUserIdToEmployeeId($user_id, $employee_id)
	{
		global $wpdb;
		$wpdb->query("INSERT IGNORE INTO ".$wpdb->prefix."taplogin_users 
			SET user_id=".$user_id.", employee_id=".$employee_id);
	}
	
	function add_admin_menu() {
		add_options_page(__('TapLogin Integration', 'taplogin'), __('TapLogin Integration', 'taplogin'), 'manage_options', 'taplogin_setup', array('TapLogin', 'displayAdminSetupPage'));
	//	$current_user = wp_get_current_user();
	//	if (!$current_user || !$current_user->ID || !self::getEmployeeIdByUserId($current_user->ID))
			add_menu_page(__('Link to TapLogin', 'taplogin'), 'TapLogin', 'read', 'taplogin', array('TapLogin', 'displayAdminLinkPage'), '', null );
	}
	
	private static function saveAdminPage()
	{
		$node_id = (int)@$_POST['node_id'];
		$require_email = (int)@$_POST['require_email'];
		$secret_key = @$_POST['secret_key'];
		if (!$node_id)
			return __("Please enter a valid node ID", 'taplogin');
		if (strlen($secret_key) < 20)
			return __("Please enter a valid secret key, minimum 20 characters", 'taplogin');
		update_option('taplogin_node_id', $node_id);
		update_option('taplogin_secret_key', $secret_key);
		update_option('taplogin_require_email', $require_email);
		return null;
	}

	public static function displayAdminLinkPage()
	{
		$current_user = wp_get_current_user();
		$employee_id = self::getEmployeeIdByUserId($current_user->ID);
		if ($employee_id){
			?>
			<div class="wrap">
				<h2>TapLogin</h2>
				<p><?php _e("You can log in to this site without a password, by tapping just one button on your phone", 'taplogin') ?>.</p>
				<p><?php _e("You always have an option to login with username/password in case you lose your phone", 'taplogin') ?>.</p>
			</div>
			<?php
		}
		else{
			self::loadTapLogin();
			?>
			<div class="wrap">
				<h2>TapLogin</h2>
				<p><?php _e("Do you want to log in to this site without a password, by tapping just one button on your phone? You can <a href='javascript:showTapLogin()'>set up your phone for one-tap login</a> right now", 'taplogin') ?>.</p>
				<p><?php _e("You always have an option to login with username/password in case you lose your phone", 'taplogin') ?>.</p>
			</div>
			<?php
		}
	}
	
	public static function displayAdminSetupPage()
	{
		$error = null;
		$bSaved = false;
		if (!empty($_POST['submit'])){
			$error = self::saveAdminPage();
			$bSaved = !$error;
		}
		$node_id = get_option('taplogin_node_id');
		$secret_key = get_option('taplogin_secret_key');
		$require_email = get_option('taplogin_require_email') ? 'checked' : '';
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2><?php _e("TapLogin Integration", 'taplogin') ?></h2>
			<?php if($bSaved) { ?>
			<div class="settings-error updated"><p><?php _e("Options saved", 'taplogin') ?>.</p></div>
			<?php } ?>
			<?php if($error) { ?>
			<div class="settings-error updated"><p><?php echo $error ?></p></div>
			<?php } ?>
			<form method="post" action="">
				<p><?php _e("Please edit Node ID and Secret Key only during initial setup and don't change them afterwards (unless the secret key was compromised)", 'taplogin') ?>.</p>
				<p><?php _e("If it is the first time that you set up TapLogin for your site, the secret key was already generated for you (otherwise please enter something random for Secret Key). Please <a target='_blank' href='https://www.azid.ru/control/new_desktop_app_node.php'>create a new desktop node at AZid.ru</a> by copying and pasting the secret key from here to AZid.ru, then enter here the ID of the newly created node. Save changes, and you're done", 'taplogin') ?>.</p>
				<table>
					<tr>
						<td style="width: 100px;">Node ID</td>
						<td><input type="text" name="node_id" value="<?php echo $node_id ?>" size='5' /></td>
					</tr>
					<tr>
						<td>Secret Key</td>
						<td><input type="text" name="secret_key" value="<?php echo $secret_key ?>" size='60' /></td>
					</tr>
					<tr>
						<td colspan='2'><input type="checkbox" name="require_email" value="1" <?php echo $require_email ?> /> <?php _e("Require email address (if unchecked, a random email address will be generated for all new users)", 'taplogin') ?></td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Save Changes", 'taplogin') ?>"/>
				</p>
			</form>
		</div>
		
		<?php
	}

	public static function install()
	{
		global $wpdb;
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."taplogin_users (
			user_id BIGINT NOT NULL PRIMARY KEY,
			employee_id BIGINT NOT NULL UNIQUE,
			creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)");
		$secret_key = get_option('taplogin_secret_key');
		if (!$secret_key)
			update_option('taplogin_secret_key', SecureRandom::genRandomBase64String(40));
	}
}

register_activation_hook( __FILE__, array('TapLogin', 'install') );
add_action('login_form', array('TapLogin', 'showWidget'));
add_action('init', array('TapLogin', 'handleRequest'));
add_action('admin_menu', array('TapLogin', 'add_admin_menu'));
load_plugin_textdomain('taplogin', false, basename( dirname( __FILE__ ) ) . '/languages/' );
