<?php
/*
 * Plugin Name: HotchDev_Template
 * Description: Purge's a site page when executed.
 * Version: 0.0.1
 * Author: Gabriel Hotchner
 */

	//Hook  to add an admin menu for the plugin
	add_action('admin_menu','plugin_setup_menu');
	
	//Function to create settings page
	function plugin_setup_menu(){
		add_options_page(
			'My Varnish Settings',
			'HotchDev_Template',
			'manage_options',
			'varnish-menu',
			'varnish_init_func');
	}
	
	//Hook to initialize settings page
	add_action( 'admin_init', 'my_plugin_settings' );
	
	
	//Register the settings we want on the settings page
	function my_plugin_settings() {
            register_setting( 'my-plugin-settings-group', 'ip_address' );
            register_setting( 'my-plugin-settings-group', 'port_number' );
            register_setting( 'my-plugin-settings-group', 'url_page' );
	}
	
	//Function to set up the style and look of the setting page
	function varnish_init_func() {
		?>
		
		<!-- This is the main Div -->
		<div class="wrap">
		<h2>Varnish Plugin Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'my-plugin-settings-group' ); ?>
			<?php do_settings_sections( 'my-plugin-settings-group' ); ?>
		<table class="form-table">
			<tr valign="top">
			<th scope="row">IP Address</th>
			<td><input type="text" name="ip_address" value="<?php echo esc_attr( get_option('ip_address', '127.0.0.1') ); ?>" /></td>
			</tr>
		
			<tr valign="top">
			<th scope="row">Port</th>
			<td><input type="text" name="port_number" value="<?php echo esc_attr( get_option('port_number', '80') ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">URL</th>
			<td><input type="text" name="url_page" value="<?php echo esc_attr( get_option('url_page', 'https://www.mysite.com/some/page') ); ?>" /></td>
			</tr>
		</table>
		
		<!-- This is the save settings button -->
		<?php submit_button(); ?> 
		
		<!-- This is the Purge URL button -->
                <input type="submit" value="Purge URL" onclick="purge_test()" />
                <button onclick="purge_test()">Purge URL</button>
                
		<!-- This JS Script will grab the settings info and run the purge function-->
		<script type="text/javascript">
		        function purge_test(){
				var p1 = <?php echo get_option('port_number'); ?>;
				alert(p1);
				alert("hello world!");
			}
                        
                    function purge_varnish(){ 
                    alert("hello world!");
                    
                    <?php 
                    
                     $errno = (integer) "";
                     $errstr = (string) "";
                     $varnish_sock = fsockopen("127.0.0.1", "80", $errno, $errstr, 10); 
                     if (!$varnish_sock) {
                        error_log("Varnish connect error: ". $errstr ."(". $errno .")");
                    } else {
                      // Build the request
                     $cmd = "PURGE ". "/readme.html" ." HTTP/1.0\r\n";
                     $cmd .= "Host: ". "10.192.4.105" ."\r\n";
                     $cmd .= "Connection: Close\r\n";
                    // Finish the request
                     $cmd .= "\r\n";
                     // Send the request
                     echo "Sending request: <blockquote>". nl2br($cmd) ."</blockquote>";
                     fwrite($varnish_sock, $cmd);
                    // Get the reply
                    echo "Received answer: <blockquote>";
                    $response = "";
                    while (!feof($varnish_sock)) {
                       $response .= fgets($varnish_sock, 128);
                       }
                     echo nl2br($response);
                     echo "</blockquote>";
                      }
                     fclose($varnish_sock);
                     ?>
                             
                     } 
		</script>
		
		</form>
		</div>
	<?php }
	
        //if (isset($_POST['PButton'])) {
          //  purge_varnish();
      //  }
	//Function to remove settings upon deactivation of the plugin
	function deactivate() {
		delete_option('ip_address');
		delete_option('port_number');
		delete_option('time_delay');
	}
	
	//Hook to run the deactivate function upon deactivation
	register_deactivation_hook(__FILE__, 'deactivate');
	
       
	//Create PURGE function
        
         //TO DO:
	//Execute purge function 
	//Then show output somehow
        
	//Test code to grab information stored in the settings page
	/*$var1 = get_option('port_number');
	echo 'HELLOWORLDIAMGABEN'; 

         <input type="text" value="<?php isset($_POST['txtURL']) ? $_POST['txtURL'] : '' ?>" name="txtURL" class="defaultText" title="http://yourhost/some-url.html" />
            <input type="submit"  name="PButton" value="Purge URL" />
         *          */

	//EOF
	?>

