<?php

/**
 * @package Auto-Install Free SSL
 * This package is a WordPress Plugin. It issues and installs free SSL certificates in cPanel shared hosting with complete automation.
 *
 * @author Free SSL Dot Tech <support@freessl.tech>
 * @copyright  Copyright (C) 2019-2020, Anindya Sundar Mandal
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://freessl.tech
 * @since      Class available since Release 1.0.0
 *
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace AutoInstallFreeSSL\FreeSSLAuto\Admin;

use AutoInstallFreeSSL\FreeSSLAuto\Email;

/**
 * Class to force SSL
 *  @since 2.0.0
 *
 */
class ForceSSL
{
    /**
     * Add action hooks
     *
     * @since 2.0.0
     * 
     */
    public function __construct()
    {
        //Following function should be called for any value of aifs_force_ssl, so that revert to HTTP possible from admin dashboard
        add_action( 'admin_init', array( $this, 'aifs_force_ssl_controller' ) );
        
        if( get_option( 'aifs_force_ssl' ) == 1 ) {
            /* Go back to HTTP if it triggered */
            add_action('wp', array($this, 'revert_force_ssl'));
            
            /* 301 HTTPS redirection */
            add_action('wp_loaded',  array($this, 'force_ssl'), 20);
            
            /* Fix mixed content */
            if (is_admin()) {
                add_action("admin_init", array($this, 'start_buffer_wp'), 100);
            }
            else {
                add_action("init", array($this, 'start_buffer_wp'));
            }
            
            add_action("shutdown", array($this, 'end_buffer_wp'), 999);
        }
                
    }
    
    
    /**
     * Revert to HTTP using secret nonce (i.e., link)
     *
     * @since 2.0.0
     */
    public function revert_force_ssl()
    {        
        $revert_nonce = get_option('aifs_revert_http_nonce') ? get_option('aifs_revert_http_nonce') : false;
        
        if (isset($_GET['aifs_revert_http_nonce']) && $revert_nonce != false) {
            
                if($revert_nonce == $_GET['aifs_revert_http_nonce']){
                
                update_option('aifs_force_ssl', 0);
                
                //Update siteurl and home options with HTTP
                update_option( 'siteurl', str_ireplace( 'https:', 'http:', get_option( 'siteurl' ) ) );
                update_option( 'home', str_ireplace( 'https:', 'http:', get_option( 'home' ) ) );
                
                exit(esc_html__('Reverted back to HTTP successfully. Now you can access your website over http://', 'auto-install-free-ssl'));
            }
            else{
                wp_die("Access denied due to invalid secret code. Please use the link provided in the latest email (when you activated force HTTPS last time).");
            }
        }
    }
    
    
    /**
     * Force SSL redirect
     *
     * @since 2.0.0
     */
    public function force_ssl() {
        /* Force SSL for javascript */
        add_action('wp_print_scripts', array($this, 'force_ssl_javascript'));
        
        /* Force SSL wordpress redirect */
        add_action('wp', array($this, 'force_ssl_wp'), 40, 3);
    }
    
    /**
     * Force enable SSL with javascript
     *
     * @since 2.0.0
     */
    public function force_ssl_javascript() {
        $script = '<script>';
        $script .= 'if (document.location.protocol != "https:") {';
        $script .= 'document.location = document.URL.replace(/^http:/i, "https:");';
        $script .= '}';
        $script .= '</script>';
        
        echo $script;
    }
    
    /**
     * Force SSL with WordPress 301 redirect
     *
     * @since 2.0.0
     */
    public function force_ssl_wp() {
        if ( ! is_ssl() ) {
            $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            wp_redirect($redirect_url, 301);
            exit;
        }
    }
    
    /**
     * Filter the buffer to convert all HTTP to HTTPS
     *
     * @since 2.0.0
     */
    public function filter_buffer_wp($buffer) {
        
        if (substr($buffer, 0, 5) == "<?xml") return $buffer;
        
        $home = str_replace("https://", "http://", get_option('home'));
        $home_no_www = str_replace("://www.", "://", $home);
        $home_yes_www = str_replace("://", "://www.", $home_no_www);
        
        /* In the escaped version we only replace the home_url, not it's www or non-www counterpart. So it may not be used. */
        $escaped_home = str_replace("/", "\/", $home);
        
        $search_array = array(
            $home_yes_www,
            $home_no_www,
            $escaped_home,
            "src='http://",
            'src="http://',
        );
        
        $ssl_array = str_replace(array("http://", "http:\/\/"), array("https://", "https:\/\/"), $search_array);
        
        /* Replace these links now */
        $buffer = str_replace($search_array, $ssl_array, $buffer);
        
        /* replace all HTTP links except hyperlinks */
        /* all tags with src attr are already fixed by str_replace */
        $pattern = array(
            '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
            '/<link [^>]*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<meta property="og:image" [^>]*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<form [^>]*?action=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
        );
        
        $buffer = preg_replace($pattern, 'https://', $buffer);
        
        /* handle multiple images in srcset */
        $buffer = preg_replace_callback('/<img[^\>]*[^\>\S]+srcset=[\'"]\K((?:[^"\'\s,]+\s*(?:\s+\d+[wx])(?:,\s*)?)+)["\']/', array($this, 'replace_srcset_wp'), $buffer);
                
        return $buffer;
    }
    
    
    /**
     * Replace HTTP to HTTPS
     *
     * @since 2.0.0
     */
    public function replace_srcset_wp($matches) {
        return str_replace("http://", "https://", $matches[0]);
    }
    
    
    /**
     * Start buffer
     *
     * @since 2.0.0
     */
    public function start_buffer_wp() {
        ob_start(array($this, 'filter_buffer_wp'));
    }
    
    /**
     * End buffer
     *
     * @since 2.0.0
     */
    public function end_buffer_wp() {
        if (ob_get_length()) ob_end_flush();
    }
    
    /**
     * Force SSL form: pass 1 to get activation form, 0 for deactivattion form
     * 
     * @param int $aifs_force_ssl
     * @return string
     * 
     * @since 2.0.0
     */
        /* public function force_ssl_form(int $aifs_force_ssl = 1){
         *  Removing parameter type hint to make compatible with PHP 5.6. Using scalar type hints like string is supported since PHP 7.
         */
        
    public function force_ssl_form($aifs_force_ssl = 1){
        
        $html = '<form method="post">	
        			 <input type="hidden" name="aifs_force_ssl" value="'.$aifs_force_ssl.'" />'.
        			 wp_nonce_field('aifsforcessl', 'aifs-activate-force-ssl', false, false);

        if($aifs_force_ssl){
            $confirmation_text = esc_html__("Are you sure you want to activate force HTTPS?", 'auto-install-free-ssl');
            $button_text = esc_html__( 'Activate Force HTTPS', 'auto-install-free-ssl' );
            $css_class = "button button-primary button-hero";
        }
        else{
            $confirmation_text = esc_html__("Are you sure you want to Deactivate force HTTPS and revert to HTTP?", 'auto-install-free-ssl');
            $button_text = esc_html__( 'Revert to HTTP', 'auto-install-free-ssl' );
            $css_class = "button page-title-action";
        }
        			 
        $html .=	 '<button type="submit" name="aifs_submit" class="'.$css_class.'" onclick="return confirm(\''. $confirmation_text .'\')">'. $button_text .'</button>
      			</form>';
        
      	return $html;        
    }
    
    /**
     * Set value of aifs_force_ssl
     * 
     * @since 2.0.0
     */
    public function aifs_force_ssl_controller(){
        
        if ( isset( $_POST['aifs-activate-force-ssl'] ) ) {
            if ( !wp_verify_nonce($_POST['aifs-activate-force-ssl'], 'aifsforcessl') ) {
                wp_die('Access denied');
            }
            else{
                
                /* Check if a valid SSL installed on this website - START */
                
                $factory =  new Factory();
                $ssl_details = $factory->is_ssl_installed_on_this_website();

                if($ssl_details !== true && !$ssl_details['domain_site']['ssl_installed']){

                    //Make the text
                    if(!$ssl_details['domain_other_version']['ssl_installed']){

                        $text_display = esc_html__("No", 'auto-install-free-ssl')." <strong>".esc_html__("VALID", 'auto-install-free-ssl')."</strong> ".esc_html__("SSL installed on ", 'auto-install-free-ssl'). $ssl_details['domain_site']['url'] . " " . esc_html__("and", 'auto-install-free-ssl') ." " . $ssl_details['domain_other_version']['url'] . ". <strong>" . esc_html__("Please install an SSL certificate on this website and try again", 'auto-install-free-ssl').".</strong> ";

                        if(strcmp($ssl_details['domain_site']['error_cause'], $ssl_details['domain_other_version']['error_cause']) == 0){

                            $text_display .= esc_html__("Error cause", 'auto-install-free-ssl') .": ". $ssl_details['domain_site']['error_cause'].".";
                        }
                        else{
                            $text_display .= esc_html__("Error cause for", 'auto-install-free-ssl') ." ".$ssl_details['domain_site']['url'].": ". $ssl_details['domain_site']['error_cause'] .". ". esc_html__("Error cause for", 'auto-install-free-ssl'). " ".$ssl_details['domain_other_version']['url'].": ". $ssl_details['domain_other_version']['error_cause'].".";
                        }

                    }
                    else{
                        $general_settings = admin_url('options-general.php');
                        $link_title = esc_html__("Click here to change", 'auto-install-free-ssl');

                        $text_display = esc_html__("The installed SSL covers only", 'auto-install-free-ssl') . " ". $ssl_details['domain_other_version']['url']. ". ".esc_html__("But it doesn't cover", 'auto-install-free-ssl')." ".$ssl_details['domain_site']['url'].". <strong>" . esc_html__("Please either change your", 'auto-install-free-ssl') ." <a href='$general_settings' title='$link_title'>" . esc_html__("WordPress Address (URL) & Site Address (URL)", 'auto-install-free-ssl') ."</a> " .esc_html__("or install an SSL certificate that covers", 'auto-install-free-ssl')." ".$ssl_details['domain_site']['url'].".</strong> ";

                        $text_display .= esc_html__("Error cause", 'auto-install-free-ssl') .": ". $ssl_details['domain_site']['error_cause'] .".";

                    }

                    aifs_add_flash_notice($text_display, 'error');
                    
                    return;
                }

                /* Check if a valid SSL installed on this website - END */
                         
                $force_ssl = absint($_POST['aifs_force_ssl']);
                                    
                if (update_option('aifs_force_ssl', $force_ssl ) ) {
                    
                    //set 'aifs_display_review' = 1 if this option doesn't exist
                    if(!get_option('aifs_display_review'))
                        add_option('aifs_display_review', 1);
                    
                    if($force_ssl == 1){
                        
                        $revert_nonce = uniqid('aifs').time().uniqid();
                        
                        if(update_option('aifs_revert_http_nonce', $revert_nonce ))                    
                            $this->aifs_send_revert_nonce_by_email( $revert_nonce );
                    
                        
                            //Display success/activated message (notice)
                        
                            aifs_add_flash_notice(esc_html__("Congratulations! Force HTTPS has been activated successfully.", 'auto-install-free-ssl'));
                        
                        
                        //redirect to plugin home page, so that HTTPS be forced immediately. This will send the user to the login page over HTTPS.
                        if(!is_ssl()){
                            //$redirect_url = "https://".aifs_get_domain()."/wp-login.php?redirect_to=".urlencode(admin_url('admin.php?page=auto_install_free_ssl'));
                            wp_redirect(admin_url('admin.php?page=auto_install_free_ssl'));
                        }
                    
                        //Update siteurl and home options with HTTPS - this is required to fix dynamic CSS issue with premium themes
                        update_option( 'siteurl', str_ireplace( 'http:', 'https:', get_option( 'siteurl' ) ) );
                        update_option( 'home', str_ireplace( 'http:', 'https:', get_option( 'home' ) ) );
                }
                else{
                    //Update siteurl and home options with HTTP
                    update_option( 'siteurl', str_ireplace( 'https:', 'http:', get_option( 'siteurl' ) ) );
                    update_option( 'home', str_ireplace( 'https:', 'http:', get_option( 'home' ) ) );
                    
                    //Display success message (Deactivated)
                    aifs_add_flash_notice( esc_html__("Force HTTPS has been Deactivated successfully and you have reverted to HTTP.", 'auto-install-free-ssl'));
                }
             }
          }
            
        }
    }
    
    /**
     * Send the revert nonce by email
     * 
     * @param string $revert_nonce
     * 
     * @since 2.0.0
     */
    public function aifs_send_revert_nonce_by_email($revert_nonce){
        
        $admin_email = get_option('admin_email');        
        $admin = get_user_by('email', $admin_email);
        $admin_first_name = $admin->first_name;
        
        $revert_url = str_replace('https:', 'http:', site_url()) . "/?aifs_revert_http_nonce=".$revert_nonce;
        
        $subject = "'".AIFS_NAME ."' ". esc_html__( 'has activated Force HTTPS on your website', 'auto-install-free-ssl' )." ".aifs_get_domain(false);
                
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From:wordpress@'.aifs_get_domain();
        
        //Email body
        $body = "<html><body>";
        
        $body .= esc_html__("Hello", 'auto-install-free-ssl')." ".$admin_first_name.",<br /><br />";
        
        $body .= esc_html__("We have successfully activated Force HTTPS on your website", 'auto-install-free-ssl')." ".aifs_get_domain(false).".<br /><br />";
        
        $body .= esc_html__("Please refresh your website to get the padlock. Still don't see it? If your theme has an option to", 'auto-install-free-ssl')." ";
        
        $body .= "<strong>". esc_html__("regenerate CSS files", 'auto-install-free-ssl')."</strong>, ";
        
        $body .= esc_html__("please do it. Otherwise, search the theme and/or plugins for", 'auto-install-free-ssl')." ";
        
        $body .= "<strong>". esc_html__("hardcoded URL", 'auto-install-free-ssl').", </strong> ";
        
        $body .= esc_html__("if any, and fix it. Please contact us at", 'auto-install-free-ssl')." <em>support@freessl.tech</em> ";
        
        $body .= esc_html__("for any help", 'auto-install-free-ssl').".<br /><br />";
        
        
        $body .= esc_html__("If the SSL certificate has not been installed properly, or if an invalid SSL certificate installed on your website, you may face issues. Your WordPress website may be inaccessible too. In that case please click the link given below to deactivate force HTTPS and revert to HTTP.", 'auto-install-free-ssl')."<br />";
        
        $body .= "<a href='$revert_url'>$revert_url</a><br /><br />";
        
        $body .= esc_html__("Clicking the above link will instantly deactivate force HTTPS and revert your website to HTTP", 'auto-install-free-ssl').".<br /><br />";
        
        $body .= esc_html__("But if the issue persists", 'auto-install-free-ssl').", ";
        
        $body .= '<a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#reverthttp">' . esc_html__("click here", 'auto-install-free-ssl')."</a> ";
        
        $body .= esc_html__("for documentation on more options on how to revert to HTTP", 'auto-install-free-ssl').".<br /><br />";
        
        $email = new Email();
        $body .= $email->add_review_request_in_email();
        $body .= $email->add_email_signature();
                
        $body .= "</body></html>";
        
        //now send the email
        wp_mail($admin_email, $subject, $body, $headers);
        
    }
    
}
