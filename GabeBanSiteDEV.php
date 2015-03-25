<?php
/*
 * Plugin Name: HotchDev_Template
 * Description: Purge's a site page when executed.
 * Version: 0.0.1
 * Author: Gabriel Hotchner
 */

//The plugin currently only performs URL page purges.
//Only wokrs on Varnish 4.0

/*Functionality to add:
 * Varnish 3.0 support
 * Support ban commands
 * Add option to use admin port
 */
    

class VarnishSiteBan {
   
    //Creates the plugin
    function VarnishSiteBan() {
        
        //Default Settings
        $address_option = "127.0.0.1";
        $port_option = "80";
        $page_option = "https://www.mysite.com/some/page";
        
        //Add the settings
        if(!get_option("address_option"))
            add_option("address_option", $address_option, '', 'yes');
        if(!get_option("port_option"))
            add_option("port_option", $port_option, '', 'yes');
        if(!get_option("page_option"))
            add_option("page_option", $page_option, '', 'yes');
        
        //Create the admin menu
        add_action('admin_menu', array(&$this, 'CreateMenu'));
    } 
    
    //Creates the plugin menu
    function CreateMenu() {
                add_options_page(
			'My Varnish Settings',
			'HotchDev_Template',
			1,
			'varnish-menu',
			array(&$this,'varnish_init_menu'));
    }
    
    //Purges a URL page
    function purge_varnish(){ 
                    //alert("Let the Purge Commence!");
                    
                    //Set up the socket connection to varnish
                     $errno = (integer) "";
                     $errstr = (string) "";
                     $varnish_sock = fsockopen(get_option('address_option'), get_option('port_option'), $errno, $errstr, 10);
                     
                    //Check if the settings provided connect to a varnish socket
                    if (!$varnish_sock) {
                        error_log("Varnish connect error: ". $errstr ."(". $errno .")");
                    } else {
                     
                        //Take the user's URL
                       $txtUrl = get_option('page_option');
                       
                       //We need the host name and page
                       //So we perform a few operations to get those bits of information from the URL
                       $txtUrl = str_replace("http://", "", $txtUrl); 
                       $hostname = substr($txtUrl, 0, strpos($txtUrl, '/'));
                       $url = substr($txtUrl, strpos($txtUrl, '/'), strlen($txtUrl));
                        
                        // Build the request 
                        $cmd = "PURGE ". $url ." HTTP/1.0\r\n";
                        $cmd .= "Host: ". $hostname ."\r\n";
                        $cmd .= "Connection: Close\r\n";
                        $cmd .= "\r\n";
               
                        //Testing some Ban commands: 
                        //$cmd = "ban req.http.host == $hostname && req.url ~ $url\n";
                        //$cmd = "ban req.http.host == " . $hostname . " && req.url ~ " . $url;
                        //$cmd = "ban req.http.host == " . "http://ghotchnerdev.smca.ucf.edu/" . " && req.url ~ " . "/readme.html$";
                                
                        // Send the request to the socket
                         fwrite($varnish_sock, $cmd);
                    
                        // Get the reply (I may just remove this since I'm not using it)
                        $response = "";
                        while (!feof($varnish_sock)) {
                            $response .= fgets($varnish_sock, 128);
                        }
                      }
                     
                     //Close socket connection
                     fclose($varnish_sock);    
                } 
                
                
    //Creates the style of the settings page
    function varnish_init_menu(){
        if(current_user_can('administrator')) {
            if($_SERVER["REQUEST_METHOD"] == "POST") {
                if(isset($_POST['save_settings'])) {
                    if(isset($_POST["address_option"]))
			update_option("address_option", trim(strip_tags($_POST["address_option"])));
                    if(isset($_POST["port_option"]))
			update_option("port_option", (int)trim(strip_tags($_POST["port_option"])));
                    if(isset($_POST["page_option"]))
			update_option("page_option", trim(strip_tags($_POST["page_option"])));
                    
?>
        <div class="updated"><p><?php echo "Settings Saved!"; ?></p></div>
<?php
            }
            if(isset($_POST['purge_button'])){
                $this->purge_varnish();
            }
         
            
        }
        //Enter html code:
?>
        
        
        <div class="wrap">
            <h2><?php echo "Varnish Plugin Settings"; ?></h2>
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                <table class="form-table">
			<tr valign="top">
			<th scope="row">IP Address</th>
			<td><input type="text" name="address_option" value="<?php echo esc_attr( get_option('address_option', '127.0.0.1') ); ?>" /></td>
			</tr>
		
			<tr valign="top">
			<th scope="row">Port</th>
			<td><input type="text" name="port_option" value="<?php echo esc_attr( get_option('port_option', '80') ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">URL</th>
			<td><input type="text" name="page_option" value="<?php echo esc_attr( get_option('page_option', 'https://www.mysite.com/some/page') ); ?>" /></td>
			</tr>
		</table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" name="save_settings" value="<?php echo "Save Changes"; ?>"> 
                    <input type="submit" class="button-secondary" name="purge_button" value="<?php echo "Purge URL"; ?>">
                </p>
                
            </form>
        </div>
            
<?php //Done with html
               
        }
    }
    
    
    
}
$siteBan = & new VarnishSiteBan();

?>