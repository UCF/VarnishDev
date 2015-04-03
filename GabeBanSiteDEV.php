<?php
/*
 * Plugin Name: HotchDev_Template
 * Description: A plugin for employing purges and bans on the Varnish cache. 
 * Version: 0.0.1
 * Author: Gabriel Hotchner
 */

//The plugin currently only works on Varnish 4.0

/*Functionality to add:
 * Varnish 3.0 support
 * add ability for it to automatically purge when content is created or edited.
 * Add option to use admin port
 */
    
class VarnishSiteBan {
   
    //Creates the plugin
    function VarnishSiteBan() {
        
        //Default Settings
        $address_option = "127.0.0.1";
        $port_option = "80";
        $page_option = "https://www.mysite.com/some/page";
        $version_option = 4;
        
        //Add the settings
        if(!get_option("address_option"))
            add_option("address_option", $address_option, '', 'yes');
        if(!get_option("port_option"))
            add_option("port_option", $port_option, '', 'yes');
        if(!get_option("page_option"))
            add_option("page_option", $page_option, '', 'yes');
        if(!get_option("version_option"))
            add_option("version_option", $version_option, '', 'yes');
        
        //Create the admin menu
        add_action('admin_menu', array(&$this, 'CreateMenu'));
        

        //The following will purge a post whenever it is created,deleted or changed in anyway.
        //Purge or Ban Posts:
        add_action('edit_post', array(&$this,'purge_post'),20);
        add_action('deleted_post', array(&$this, 'purge_post'), 20);
        add_action('save_post ', array(&$this, 'purge_post'), 20);
       

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
    
    
    function purge_post($post){
        $url = get_permalink($post);
        $url = str_replace(get_bloginfo("wpurl"),"",$url);
        $this->purge_specific($url);
    }
    
    function purge_specific($wp_url){
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
                       $url = $wp_url;
                       $url = substr($wp_url, strpos($wp_url, '/'), strlen($wp_url));
                        
                        // Build the request (Purge)
                        $cmd = "PURGE ". $url ." HTTP/1.0\r\n";
                        $cmd .= "Host: ". $hostname ."\r\n";
                        $cmd .= "Connection: Close\r\n";
                        $cmd .= "\r\n";

                      
                      
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
    
    
    //Purges a URL page
    function purge_varnish(){ 
                    
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
                        
                        // Build the request (Purge)
                        $cmd = "PURGE ". $url ." HTTP/1.0\r\n";
                        $cmd .= "Host: ". $hostname ."\r\n";
                        $cmd .= "Connection: Close\r\n";
                        $cmd .= "\r\n";

                      
                      
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
                
    //This function will eventually purge a whole blog, 
    //right now I'm working on getting the bans working properly
    function banPurge_varnish(){
        
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
                       
                       //Testing some Ban commands: 
                       //Lowercase "ban" should ban entire host's domain
                       //I will have to make a separate function for more specific bans
                       $cmd = "ban ". $url ." HTTP/1.0\r\n";
                       $cmd .= "Host: ". $hostname ."\r\n";
                       $cmd .= "Connection: Close\r\n";
                       $cmd .= "\r\n";
                  
                       //$cmd = "ban req.http.host ~ $hostname\n";
                       
                       
                       // Send the request to the socket
                       fwrite($varnish_sock, $cmd."\n");
                    
                        // Get the reply (I may just remove this since I'm not using it)
                        $response = "";
                        while (!feof($varnish_sock)) {
                            $response .= fgets($varnish_sock, 128);
                        }
                      }
                     
                     //Close socket connection
                     fclose($varnish_sock);
    }
    
    function checkVarnish(){
            
            $connection_result = "";
            //Set up the socket connection to varnish
            $errno = (integer) "";
            $errstr = (string) "";
            $varnish_sock = fsockopen(get_option('address_option'), get_option('port_option'), $errno, $errstr, 10);
            if($varnish_sock){
                $connection_result .= "<p>Successfully connected to the Server.</p>";
                fclose($varnish_sock);
            } else {
                $connection_result .= "<p>Unable to connect to the Server.</p>";
            }
            
?>
     <div class="updated"><?php echo $connection_result; ?></div>
<?php
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
                    if(isset($_POST["version_option"]))
			update_option("version_option", $_POST["version_option"]);
                    
?>
        <div class="updated"><p><?php echo "Settings Saved!"; ?></p></div>
<?php
            }
            if(isset($_POST['purge_button'])){
                $this->purge_varnish();
            }
            if(isset($_POST['banPurge_button'])){
                $this->banPurge_varnish();
            }
            if(isset($_POST['verify_connection'])){
                $this->checkVarnish();
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
                        
                        <tr valign="top">
                        <th scope="row">Varnish Version</th>
                        <td>
                            <select id="varnishVersion" name="version_option">
				<option value="4"<?php if(get_option("version_option") == 4) echo " selected"; ?>>V4: PURGE</option>
                		<option value="3"<?php if(get_option("version_option") == 3) echo " selected"; ?>>V3: N/A</option>
                            </select>
                        </td>
                        </tr>
                        
                        <tr>
                            <td> <input type="submit" class="button-secondary" name="verify_connection" value="<?php echo "Verify Varnish Connection"; ?>"> </td>
                        </tr>
                </table> 
                
                <p class="submit">
                    <input type="submit" class="button-primary" name="save_settings" value="<?php echo "Save Changes"; ?>"> 
                    <input type="submit" class="button-secondary" name="purge_button" value="<?php echo "Purge URL"; ?>">
                    <input type="submit" class="button-secondary" name="banPurge_button" value="<?php echo "Purge/Ban whole Blog"; ?>">
                
                </p>
                
            </form>
        </div>
            
<?php //Done with html
               
        }
    }
    
    
    
}
$siteBan = & new VarnishSiteBan();

?>