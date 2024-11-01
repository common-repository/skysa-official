<?php
/*
Plugin Name: Skysa App Bar Integration
Plugin URI: http://wordpress.org/extend/plugins/skysa-official/
Description: Skysa App Bar settings plugin
Version: 2.1
Author: Skysa
Author URI: http://www.skysa.com
*/

if ( ! function_exists( 'is_plugin_active_for_network' ) )
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if(!is_admin()){
	if( function_exists( 'wp_print_footer_scripts' ) ) {
		if(!in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')))
			add_action( 'wp_print_footer_scripts', 'skysa_filter_footer' );
	} else {
		add_action( 'wp_footer', 'skysa_filter_footer' );
	}
}
if((skysa_is_network_admin() && is_plugin_active_for_network('skysa-official/skysa.php')) || (!skysa_is_network_admin() && !get_site_option('SkysaNetworkGlobal')) || (!is_plugin_active_for_network('skysa-official/skysa.php') && !skysa_is_network_admin())){
add_action('admin_menu', 'skysa_config_page');
add_action('network_admin_menu', 'skysa_config_page');
skysa_admin_warnings();
}

add_action( 'wp_ajax_skyauth_friend_ajax', 'skysa_friend_process_ajax' );


function skysa_is_network_admin() {

	if ( defined( 'WP_NETWORK_ADMIN' ) )

		return WP_NETWORK_ADMIN;

	return false;

}

function skysa_friend_process_ajax() {
	global $bp;
	if (!$bp || !is_user_logged_in() )
		die();
	$user_id = $bp->loggedin_user->id;
	if ( bp_has_members( 'user_id='.$user_id.'&type=active&populate_extras=0' ) ) {
		while ( bp_members() ) {
			bp_the_member();
			echo bp_member_user_id() . ',';
		}
	}
	die();
}

function skysa_filter_footer() {
    $skysa_network_global = get_site_option('SkysaNetworkGlobal');
    if(!$skysa_network_global || !is_plugin_active_for_network('skysa-official/skysa.php')){
	    $skysa_toolbarid = get_option('SkysaAppbarID');
	    $skysa_loginpage = get_option('SkysaAppbarLogin');
	    $skysa_hide = get_option('SkysaAppbarHide');
		$skysa_hide_list = get_option('SkysaAppbarHideList');
	    $skysa_memberint = get_option('SkysaAppbarMemberInt');
	    $skysa_showrealname = get_option('SkysaAppbarShowRealName');
    }
    else{
        $skysa_toolbarid = get_site_option('SkysaAppbarID');
	    $skysa_loginpage = get_site_option('SkysaAppbarLogin');
	    $skysa_hide = get_site_option('SkysaAppbarHide');
		$skysa_hide_list = get_site_option('SkysaAppbarHideList');
	    $skysa_memberint = get_site_option('SkysaAppbarMemberInt');
	    $skysa_showrealname = get_site_option('SkysaAppbarShowRealName');
    }
	global $current_user;
	global $bp;
	global $mngl_user;
	get_currentuserinfo();
	$skysa_hide_bar = true;
	if(!isset($skysa_hide_list)){
		$skysa_hide_bar = false;	
	}
	else{
		$skysa_hide_list = explode(',',$skysa_hide_list);
		$skysa_hl_temp = array('init' => true);
		foreach($skysa_hide_list as $name){
			$skysa_hl_temp[$name] = true;	
		}
		foreach($current_user->roles as $id){
			if(!$skysa_hl_temp[$id]){
				$skysa_hide_bar = false;
				break;
			}
		}
		
		if($current_user->ID == '' && !$skysa_hl_temp['logged_out']) $skysa_hide_bar = false;
		unset($id, $values, $name, $hide, $skysa_hl_temp);
	}
	if($mngl_user){
		$e = $mngl_user->get_profile_url();
	}
	else{
		$e = $bp ? $bp->loggedin_user->domain : '/user-edit.php?user_id='.$current_user->ID;
	}
	$avatar = function_exists( 'bp_core_fetch_avatar' ) ? bp_core_fetch_avatar( array( 'item_id' => $current_user->ID, 'type' => 'thumb' ) ) : get_avatar($current_user->ID,32);
	if($avatar && $avatar != ''){
		$avatar = str_replace('"',"'",$avatar);
		if(strpos($avatar,'http:')){
			
			$avatarArr = explode('http:',$avatar);
			if($avatarArr[1]){
				$avatarArr = explode("'",$avatarArr[1]);
				$avatar = 'http:'.$avatarArr[0];
			}
			else{
				$avatar = '';	
			}
		}
		else{
			$avatar = '';	
		}
	}
	else{
		$avatar = '';	
	}
	if ($skysa_toolbarid != '') {
		
		if((!$skysa_hide || $current_user->ID != '') && !$skysa_hide_bar){
			if($skysa_memberint !== '0'){
				echo '<script type="text/javascript">';
				echo '	var _SKYAUTH = {';
				echo "	loginUrl:'" . ($skysa_loginpage && $skysa_loginpage != '' ? $skysa_loginpage : '/wp-login.php') . "',";
				echo "	memberNick:'" . (!$skysa_showrealname ? $current_user->display_name : $current_user->user_login) . "',";
				echo "	memberId:'" . $current_user->ID . "',";
				echo "	profileUrl:'" . $e . "',";
				echo "	photoUrl:'".$avatar."',";
				echo "	friendsCall:'" . admin_url('admin-ajax.php') . "?action=skyauth_friend_ajax'";
				echo '	};';
				echo '</script>';
			}
			echo '<a href="http://www.skysa.com" id="SKYSA-NoScript">Skysa App Bar</a><script src="//static2.skysa.com/?i='.$skysa_toolbarid.'" type="text/javascript"></script>';
		}
	}
	else{
		echo '<a href="http://www.skysa.com" id="SKYSA-NoScript">Skysa App Bar</a><script src="//static2.skysa.com/?i=sample_key" type="text/javascript"></script>';
	}
}

function skysa_admin_warnings() {
	$skysa_toolbarid = get_option('SkysaAppbarID');
	if ( !skysa_toolbarid || $skysa_toolbarid == '' ) {
		function skysa_warning() {
			echo "
			<div id='skysa-warning' class='updated fade'><p><strong>".__('Skysa App Bar is almost setup.')."</strong> ".sprintf(__('<a href="%1$s">Enter your Skysa Bar ID</a> to enable customization.'), "admin.php?page=skysa-key-config")."</p></div>
			";
		}
		add_action('admin_notices', 'skysa_warning');
		return;
	}
}

function skysa_config_page() {
	if ( ! function_exists( 'is_ssl' ) ) {
	  function is_ssl() {
	   if ( isset($_SERVER['HTTPS']) ) {
		if ( 'on' == strtolower($_SERVER['HTTPS']) )
		 return true;
		if ( '1' == $_SERVER['HTTPS'] )
		 return true;
	   } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
		return true;
	   }
	   return false;
	  }
	 }

	 if ( version_compare( get_bloginfo( 'version' ) , '3.0' , '<' ) && is_ssl() ) {
	  $wp_content_url = str_replace( 'http://' , 'https://' , get_option( 'siteurl' ) );
	 } else {
	  $wp_content_url = get_option( 'siteurl' );
	 }
	 $wp_content_url .= '/wp-content';
	 $wp_content_dir = ABSPATH . 'wp-content';
	 $wp_plugin_url = $wp_content_url . '/plugins';
	 $wp_plugin_dir = $wp_content_dir . '/plugins';
	 $wpmu_plugin_url = $wp_content_url . '/mu-plugins';
	 $wpmu_plugin_dir = $wp_content_dir . '/mu-plugins';
	 
	add_menu_page(__('Skysa App Bar'), __('Skysa App Bar'), 'manage_options', 'skysa-key-config', 'skysa_config', plugins_url( '/images/icon.png', __FILE__ ));
    $skysa_network_global = get_site_option('SkysaNetworkGlobal');
    if(!$skysa_network_global){
	    $skysa_toolbarid = get_option('SkysaAppbarID');
    }
    else{
        $skysa_toolbarid = get_site_option('SkysaAppbarID');
    }
	if ($skysa_toolbarid != '') {
        if((skysa_is_network_admin() && $skysa_network_global) || (!skysa_is_network_admin() && !$skysa_network_global) || !is_plugin_active_for_network('skysa-official/skysa.php')){
		    add_submenu_page( 'skysa-key-config',__('Plugin Configuration'), __('Plugin Configuration'), 'manage_options', 'skysa-key-config', 'skysa_config');
		    add_submenu_page( 'skysa-key-config', __('Apps and Settings'), __('Apps and Settings'), 'manage_options', 'skysa_apps_settings', 'skysa_apps' );
        }
	}
}

function skysa_config() {
	global $wp_roles;
	$roles_list = array();
	foreach($wp_roles -> roles as $id => $values){
		array_push($roles_list,array( 'name' => $values['name'], 'id' => $id ));
	}
	unset($id,$values);
	array_push($roles_list, array('name'=> 'Logged-Out User', 'id' => 'logged_out'));
	$roles_list = array_reverse($roles_list);
    if(!skysa_is_network_admin()){
	    $skysa_toolbarid = get_option('SkysaAppbarID');
	    $skysa_loginpage = get_option('SkysaAppbarLogin');
	    $skysa_hide = get_option('SkysaAppbarHide');
		$skysa_hide_list = get_option('SkysaAppbarHideList');
	    $skysa_memberint = get_option('SkysaAppbarMemberInt');
	    $skysa_showrealname = get_option('SkysaAppbarShowRealName');
    }
    else{
        $skysa_toolbarid = get_site_option('SkysaAppbarID');
	    $skysa_loginpage = get_site_option('SkysaAppbarLogin');
	    $skysa_hide = get_site_option('SkysaAppbarHide');
		$skysa_hide_list = get_site_option('SkysaAppbarHideList');
	    $skysa_memberint = get_site_option('SkysaAppbarMemberInt');
	    $skysa_showrealname = get_site_option('SkysaAppbarShowRealName');
    }
    $skysa_network_global = get_site_option('SkysaNetworkGlobal');

	$invalid = false;
	if ( isset($_POST['submit']) ) {
        if(isset($_POST['skysanetworkglobal'])){
            $skysa_network_global = $_POST['skysanetworkglobal'];
            update_site_option('SkysaNetworkGlobal', $skysa_network_global);
        }
        if(isset($_POST['toolbarid'])){
		    if (isset($_POST['toolbarid-valid']) && $_POST['toolbarid-valid'] == 'yes')
		    {
			    $skysa_toolbarid = $_POST['toolbarid'];
		    }
		    else
		    {
			    $invalid = true;
		    }
		    if ($_POST['skysa_hide'] == 'on')
		    {
			    $skysa_hide = 1;
		    }
		    else
		    {
			    $skysa_hide = 0;
		    }
			
			$skysa_hide_list = array();
			foreach($roles_list as $role){
				$found = false;
				$N = count($_POST['skysa_show_list']);
				for($i=0; $i < $N; $i++)
				{
					if($_POST['skysa_show_list'][$i] == $role['id']){
						$found = true;
					}
				}																																																							//echo $role['name'].($skysa_show_list[$role['id']] ? '* ' : ' ');
				if(!$found){
					array_push($skysa_hide_list,$role['id']);
					if($role['id'] == 'logged_out'){
						$skysa_hide = 0;	
					}
				}
					
			}
			unset($role,$found,$N,$i);
			$skysa_hide_list = implode(',',$skysa_hide_list);

		
		    if (isset($_POST['memberint'])){
			    $skysa_memberint = $_POST['memberint'];
		    }
		    else{
			    $skysa_memberint = '1';
		    }

		    if (isset($_POST['showname'])){
			    $skysa_showrealname = $_POST['showname'];
		    }
		    else{
			    $skysa_showrealname = '0';
		    }

		    $skysa_loginpage = $_POST['loginpage'];

            if(!skysa_is_network_admin()){
		        update_option('SkysaAppbarID', $skysa_toolbarid);
		        update_option('SkysaAppbarMemberInt', $skysa_memberint);
		        update_option('SkysaAppbarLogin', $skysa_loginpage);
		        update_option('SkysaAppbarHide', $skysa_hide);
				update_option('SkysaAppbarHideList', $skysa_hide_list);
		        update_option('SkysaAppbarShowRealName', $skysa_showrealname);
            }
            else{
                update_site_option('SkysaAppbarID', $skysa_toolbarid);
		        update_site_option('SkysaAppbarMemberInt', $skysa_memberint);
		        update_site_option('SkysaAppbarLogin', $skysa_loginpage);
		        update_site_option('SkysaAppbarHide', $skysa_hide);
				update_site_option('SkysaAppbarHideList', $skysa_hide_list);
		        update_site_option('SkysaAppbarShowRealName', $skysa_showrealname);
            }
            if((skysa_is_network_admin() && $skysa_network_global) || (!skysa_is_network_admin() && !$skysa_network_global)){
		        if(!$invalid){
			        echo "<div id=\"updatemessage\" class=\"updated fade\"><p>Skysa settings updated.</p></div>\n";
		        }
		        else{
			        echo "<div id=\"updatemessage\" class=\"error fade\"><p>Invalid Skysa Bar ID. Please check your ID and try again.</p></div>\n";
		        }
		        echo "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";	
            }
        }
        
	}
	?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Skysa App Bar - Plugin Configuration</h2>
	<div class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables">
				<form action="" method="post" id="skysa-conf">
                    <?php
                    if(skysa_is_network_admin()){ 
                    ?>
                    <div id="network_settings" class="postbox">
						<h3 class="hndle" style="cursor: default;">
							<span>Multisite Network Settings</span>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th valign="top" scrope="row">
										<label>
											Allow idividual Skysa App Bar configuration?
										</label>
									</th>
									<td valign="top">
										<label>
											<input name="skysanetworkglobal" type="radio" value="0" <?php echo (!$skysa_network_global || $skysa_network_global === '0' ? 'checked="checked"' : ''); ?> /> Yes
										</label> | <label>
											<input name="skysanetworkglobal" type="radio" value="1" <?php echo ($skysa_network_global !== '0' ? 'checked="checked"' : ''); ?> /> No
										</label><br />
										Allowing individual configuration allows each website to be setup with its own Skysa bar and options specific to that site. If this is turned off, the settings will be configured here and those settings will be used on all sites and will not be customizable on individual sites in your network.
									</td>
								</tr>
							</table>
						</div>
					</div>
                    <?php
                    }
                    if((skysa_is_network_admin() && $skysa_network_global) || (!skysa_is_network_admin() && !$skysa_network_global) || !is_plugin_active_for_network('skysa-official/skysa.php')){
                    ?>
					<div id="bar_settings" class="postbox">
						<div class="handlediv" title="Click to toggle" style="display: none;">
							<br />
						</div>
						<a href="http://www.skysa.com/page/help" target="_blank" style="float: right; font-size: 16px; margin: 3px 3px 0 0;" class="button">Help</a>
						<h3 class="hndle" style="cursor: default;">
							<span>Bar Settings</span>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr id="toolbarid-row">
									<th valign="top" scrope="row">
										<label for="toolbarid">
											Skysa Bar ID:
										</label>
									</th>
									<td valign="top">
										<input id="toolbarid" name="toolbarid" type="text" size="45" style="max-width:400px;" maxlength="40" value="<?php echo $skysa_toolbarid; ?>" /><input id="toolbarid-valid" name="toolbarid-valid" type="hidden" value="" /><span id="toolbarid-invalid" style="font-weight: bold; font-size: 16px; color: Red; vertical-align: middle; display: none; text-align: center; width: 20px; cursor: pointer;" title="This is not a valid Skysa Bar ID. You must enter your Skysa Bar ID to enable customization of your app bar.">!</span><br />
										<div id="toolbarid-unknown" style="display:none; color: Red;">Your Bar ID may be correct, but we were unable to reach the Skysa validation server at this time.</div>
										<a href="http://www.skysa.com/page/account" target="_blank">Register on Skysa.com to get your Bar ID (only takes a couple minutes)</a>
									</td>
								</tr>
								<tr>
									<th valign="top" scrope="row">
										<label for="skysa_hide">Skysa Bar Visible To:</label>
									</th>
									<td valign="top">
										<?php 
											$skysa_hide_list = isset($skysa_hide_list) ? explode(',',$skysa_hide_list) : array();
											$skysa_hl_temp = array('init' => true);
											foreach($skysa_hide_list as $name){
												$skysa_hl_temp[$name] = true;	
											}
											unset($name, $hide);
											
											foreach($roles_list as $role){
												?>
													<input type="checkbox" id="rid-<?php echo $role['id']; ?>" name="skysa_show_list[]" <?php echo (!isset($skysa_hl_temp[$role['id']]) && (!$skysa_hide || $role['id'] != 'logged_out') ? 'checked="checked"' : ''); ?> value="<?php echo $role['id']; ?>" /> <label for="rid-<?php echo $role['id']; ?>"><?php echo $role['name']; ?>s</label> | 
												<?php												
											}
											unset($role);
											/*
											 * 	<input type="checkbox" id="skysa_hide" name="skysa_hide" <?php echo ($skysa_hide ? 'checked="checked"' : ''); ?> /> <label for="skysa_hide">Hide Skysa Bar for Logged-Out Users?</label><br/>
											 */
										?>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<div id="member_integration" class="postbox">

						<a href="http://www.skysa.com/page/help" target="_blank" style="float: right; font-size: 16px; margin: 3px 3px 0 0; display: none;" class="button">Help</a>
						<h3 class="hndle" style="cursor: default;">
							<span>Member Integration</span>
						</h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th valign="top" scrope="row">
										<label>
											Enable Member Integration:
										</label>
									</th>
									<td valign="top">
										<label>
											<input name="memberint" type="radio" value="1" <?php echo ($skysa_memberint !== '0' ? 'checked="checked"' : ''); ?> onchange="if(this.checked) jQuery('#member-integration-settings').show('slow');" /> Yes (recomended)
										</label> | <label>
											<input name="memberint" type="radio" value="0" <?php echo ($skysa_memberint === '0' ? 'checked="checked"' : ''); ?> onchange="if(this.checked) jQuery('#member-integration-settings').hide('slow');" /> No
										</label><br />
										Member integration allows the Skysa bar to recognize your site WordPress and BuddyPress members, so they only have to login once on your site. If you prefer not to use the WordPress or BuddyPress member system for your site, you can disable this setting.
									</td>
								</tr>
							</table>
							<div id="member-integration-settings" <?php echo ($skysa_memberint === '0' ? 'style="display: none;"' : ''); ?>>
								<table class="form-table">
									<tr>
										<th valign="top" scrope="row">
											<label>
												User Display Name: 
											</label>
										</th>
										<td valign="top">
											<label>
												<input name="showname" type="radio" value="0" <?php echo (!$skysa_showrealname ? 'checked="checked"' : ''); ?> /> Use Display Name
											</label> | <label>
												<input name="showname" type="radio" value="1" <?php echo ($skysa_showrealname ? 'checked="checked"' : ''); ?> /> Use Unique Username
											</label><br />
											A user's display name is based on their own preference on how they wish to be seen on the site. A username is unchangeable and unique. You may wish to use the unique username option if you have many users with the same display name, which could potentially cause confusion.
										</td>
									</tr>
									<tr>
										<th valign="top" scrope="row">
											<label for="loginpage">Login Page:</label>
										</th>
										<td valign="top">
											<input id="loginpage" name="loginpage" type="text" size="20" maxlength="40" value="<?php echo ($skysa_loginpage && $skysa_loginpage != '' ? $skysa_loginpage : '/wp-login.php'); ?>" /><br />
											Enter the page which you want logged-out users to be directed to if they try to access an app which requires them to be logged in. The default page is: <a href="#" onclick="document.getElementById('loginpage').value = '/wp-login.php'; return false;" title="Set to default.">/wp-login.php</a>
										</td>
									</tr>
									
								</table>
							</div>
						</div>
					</div>
                    <?php
                    }
                    ?>
					<div class="submit">
						<input type="submit" class="button-primary" name="submit" value="Save Settings" />
					</div>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		(function(){
		var idrow = document.getElementById('toolbarid-row'),last, id = document.getElementById('toolbarid'), unknown=document.getElementById('toolbarid-unknown'), invalid=document.getElementById('toolbarid-invalid'), val = document.getElementById('toolbarid-valid'), timer,chtimer;
		var passed = function(){
		clearTimeout(timer);
		val.value = 'yes';
		idrow.className = 'form-required form-valid';
		unknown.style.display = 'none';
		invalid.style.display = 'none';
		};
		var failed = function(){
		clearTimeout(timer);
		val.value = 'no';
		idrow.className = 'form-required form-invalid';
		unknown.style.display = 'none';
		invalid.style.display = 'inline-block';
		};
		var unsure = function(){
		val.value = 'yes';
		idrow.className = 'form-required form-valid';
		unknown.style.display = 'block';
		invalid.style.display = 'none';
		};
		var check = function(){
		var link = document.createElement('script'), uncache = Math.floor(Math.random() * 10000);
		if(id.value != '' && id.value.length < 37 && id.value.search(/[A-Za-z0-9\-]+$/) != -1){
		link.setAttribute('type', 'text/javascript');
		link.setAttribute('src', '//static2.skysa.com/validate/?i='+id.value+'&fn=__SKVAL&uncache='+uncache);
		link.setAttribute('async', true);
		link.onload = function () {
		document.getElementsByTagName('head')[0].removeChild(link);
		};
		document.getElementsByTagName('head')[0].appendChild(link);
		}
		else{
		failed();
		}
		};
		window.__SKVAL = function(p){
		if(p){
		passed();
		}
		else{
		failed();
		}
		};
		var changed = function(){
		if(last != id.value){
		last = id.value;
		check();
		}
		};
		chtimer = setInterval(changed,100);
		timer = setTimeout(unsure,2000);

		})();

	</script>
</div>

<?php
} 

function skysa_apps() {
	$skysa_toolbarid = get_option('SkysaAppbarID');
	?>
<style type="text/css">body {overflow: hidden;}</style>
<iframe src="http://www.skysa.com/page/account/?bar=<?php echo $skysa_toolbarid; ?>" id="SK-fullscreen-iframe" style="visibility: hidden; position:absolute; z-index: 3; width: 100%; height: 100%; bottom: 0; right: 0;" frameborder="0" allowTransparency="true"></iframe>
<script type="text/javascript" src="http://yui.yahooapis.com/3.3.0/build/yui/yui-min.js"></script>
<script type="text/javascript">
	YUI().use('node','event','transition',function(Y){
	var KeepFit = {
	pass: {},
	store: {},
	reg: function (id, arr, pass) {
	KeepFit.store[id] = arr;
	if (pass) {
	KeepFit.pass[id] = pass;
	}
	},
	unreg: function (id) {
	delete KeepFit.store[id];
	},
	run: function () {
	for (x in KeepFit.store) {
	try {
	var obj = document.getElementById(x);
	for (var i = 0; KeepFit.store[x][i]; i++) {
	for (y in KeepFit.store[x][i]) {
	try {
	if (KeepFit.pass[x]) {
	obj.style[y] = KeepFit.store[x][i][y](KeepFit.pass[x]);
	}
	else {
	obj.style[y] = KeepFit.store[x][i][y]();
	}
	} catch (Error) {  }
	}
	}
	} catch (Error) {  }
	}
	},
	listen: function () {
	if (!KeepFit.store.listening) {
	Y.on('resize', KeepFit.run, window);
	KeepFit.store.listening = true;
	}
	}
	};

	if(Y.one('#wpadminbar') && Y.one('#adminmenuwrap')){
	KeepFit.reg('SK-fullscreen-iframe', [
	{ height: function () {
	var hfr = Y.one('#wpadminbar');
	return (hfr.get('winHeight') - hfr.get('region').height) + 'px';
	}
	},
	{ width: function () {
	var hfr = Y.one('#adminmenuwrap');
	return (hfr.get('winWidth') - hfr.get('region').width) + 'px';
	}
	}
	]);
	}
	else if(Y.one('#wp-admin-bar') && Y.one('#adminmenuwrap')){
	KeepFit.reg('SK-fullscreen-iframe', [
	{ height: function () {
	var hfr = Y.one('#wp-admin-bar');
	return (hfr.get('winHeight') - hfr.get('region').height) + 'px';
	}
	},
	{ width: function () {
	var hfr = Y.one('#adminmenuwrap');
	return (hfr.get('winWidth') - hfr.get('region').width) + 'px';
	}
	}
	]);
	}
	else if(Y.one('#wpbody')){
	KeepFit.reg('SK-fullscreen-iframe', [
	{ height: function () {
	var hfr = Y.one('#wpbody');
	return (hfr.get('region').height) + 'px';
	}
	},
	{ width: function () {
	var hfr = Y.one('#wpbody');
	return (hfr.get('region').width) + 'px';
	}
	},
	{ top: function () {
	var hfr = Y.one('#wpbody');
	return (hfr.get('region').top) + 2 + 'px';
	}
	},
	{ left: function () {
	var hfr = Y.one('#wpbody');
	return (hfr.get('region').left) + 'px';
	}
	}
	]);
	Y.one("#SK-fullscreen-iframe").setStyles({bottom:'',right:''});
	}
	Y.one('body').append(Y.one("#SK-fullscreen-iframe"));
	KeepFit.run();
	setTimeout(function(){KeepFit.run();},50);
	KeepFit.listen();

	Y.one("#SK-fullscreen-iframe").setStyles({visibility:'',opacity:0}).show(true);
	if(Y.one('#collapse-menu')) Y.one('#collapse-menu').on('click',function(){setTimeout(function(){KeepFit.run();},50);});
	});
</script>
<?php
}
?>