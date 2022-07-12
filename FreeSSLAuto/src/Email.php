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

namespace AutoInstallFreeSSL\FreeSSLAuto;

use DateTime;
use AutoInstallFreeSSL\FreeSSLAuto\Logger;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\Factory;

class Email
{
    private $logger;
    
    public function __construct(){
        
        $this->logger = new Logger();
        
    }
    
    /**
     * Add review request in automated email notification
     * @return string
     *
     * @since 2.0.0
     */
    public function add_review_request_in_email(){
        
        $display_review = get_option( 'aifs_display_review' );
        
        if ($display_review != false && $display_review != 0) { //If aifs_display_review is set to 1, add the review request
            
            $admin_email = get_option('admin_email');        
            $admin = get_user_by('email', $admin_email);
            $admin_first_name = $admin->first_name;
            
            $html = "<div style='background-color: #000000; padding: 15px; margin-bottom: 18px;'>
                            <div style='color: #FFFF00; font-size: 1.25em; margin-bottom: 16px;'>
                                " . __('Hey', 'auto-install-free-ssl' ) .' '.$admin_first_name. ', <strong>' . AIFS_NAME . '</strong> ' . __( 'has saved your $$$ by providing Free SSL Certificates and will save more. Could you please do me a BIG favor and give it a 5-star rating on WordPress? To help me spread the word and boost my motivation.', 'auto-install-free-ssl' ) . " <br />~Anindya
                            </div>
                            <a style='background: #399642; color: #ffffff; text-decoration: none; padding: 7px 15px; border-radius: 5px;' href='https://wordpress.org/support/plugin/auto-install-free-ssl/reviews/#new-post' target='_blank'>Sure! You Deserve It.</a>
                      </div>";
        }
        else{
            $html = "";
        }
        
        return $html;
    }
    
    
    /**
     * Add email signature
     * @return string
     *
     * @since 2.0.0
     */
    public function add_email_signature(){
        
        $html = "This is a system generated email.<br />
        Do not reply to this automated email.<br /><br />
        --------------<br />
        Regards,<br />
        Team <strong>".AIFS_NAME."</strong><br />
        Powered by <a href='https://getwww.me'>GetWWW.me</a> (Beautiful WordPress website design service) and <a href='https://speedify.tech/wordpress-website-speed-optimization-service'>SpeedUpWebsite.info</a> (WordPress website speed optimization service)<br />";
        
        return $html;
    }
    
    
    /**
     * Send an email.
     *
     * @param string $admin_email
     * @param array  $domains_array
     * @param bool   $ssl_installation_feature
     * @param bool   $ssl_installation_status
     * @param string $domainPath
     * @param string $homedir
     * 
     * @since 1.0.0
     */
    public function sendEmail($le_registrant_email, $domains_array, $ssl_installation_feature, $ssl_installation_status, $domainPath, $homedir)
    {
        $admin_email = get_option('admin_email');        
        $admin = get_user_by('email', $admin_email);
        $admin_first_name = $admin->first_name;
                
        $domain = \is_array($domains_array) ? reset($domains_array) : $domains_array;
        
        if(strpos($domain, 'www.') !== false && strpos($domain, 'www.') == 0){ //If www. found at the beginning
            $domain_without_www = substr($domain, 4);
        }
        else{
            $domain_without_www = $domain;
        }

        $certificate = $domainPath.'certificate.pem';
        $private_key = $domainPath.'private.pem';
        $ca_bundle = $domainPath.'cabundle.pem';

        $cert_array = openssl_x509_parse(openssl_x509_read(file_get_contents($certificate)));
        $date = new DateTime('@'.$cert_array['validTo_time_t']);
        $expiry_date = $date->format('Y-m-d H:i:s').' '.date_default_timezone_get();
        $date = new DateTime();
        $now = $date->format('Y-m-d H:i:s').' '.date_default_timezone_get();

        $subjectAltName = str_replace('DNS:', '', $cert_array['extensions']['subjectAltName']);

        $issuerShort = $cert_array['issuer']['O'];
        $issuerFull = $cert_array['issuer']['CN'];

        //Email body
        $body = "<html><body>";
        $body .= esc_html__("Hello", 'auto-install-free-ssl')." ".$admin_first_name.",";
        
        $body_log = [];
        
        if ($ssl_installation_feature) {
            //SSL installation feature exists
            //Install SSL
            if ($ssl_installation_status) {
                //Send confirmation email to admin
                $subject = "'".AIFS_NAME."' installed ${issuerShort} SSL on ".$domain;
                $body .= "<h2>'".AIFS_NAME."' installed ${issuerShort} SSL on ".$domain.'</h2><br />';
                $body .= "Congrats! '".AIFS_NAME."' has successfully installed the ${issuerShort} SSL for ".$domain.". <br /><br />";
                              
                if($domain_without_www == aifs_get_domain()){ //If this is the domain on which this plugin is installed
                    $body .= "<strong>Now login to this website, go to the dashboard of ".AIFS_NAME." and click the 'Activate Force HTTPS' button.</strong> 
                             Doing this is necessary to get the padlock in the address bar of browsers when users access your website.<br /><br />";

                    $body_log[] = "Now login to this website, go the dashboard of ".AIFS_NAME." and click the 'Activate Force HTTPS' button. Doing this is necessary to get the padlock in the address bar of browsers.";
                }
                else{
                    $body .= "<strong>Now, to get the padlock in the browsers' address bar you need to install this plugin in $domain too (if this website is made with WordPress) and click the 'Activate Force HTTPS' button.</strong> No need to do basic settings etc other configuration there.<br /><br />";
                    $body_log[] = "Now, to get the padlock in the browsers' address bar you need to install this plugin in $domain too (if this website is made with WordPress) and click the 'Activate Force HTTPS' button. No need to do basic settings etc other configuration there.";                    
                }
                
                $body .= "For your information, the SSL files have been saved at the locations given below (web hosting log in required to access).<br />";
                
                $body_log[] = 'The SSL files have been saved at the locations given below (web hosting log in required to access)';
            } else {
                $subject = "'".AIFS_NAME."' generated ${issuerShort} SSL for ".$domain;
                $body .= "<h2><a href='https://freessl.tech'>".AIFS_NAME."</a> generated ${issuerShort} SSL for ".$domain.'</h2><br />';
                $body .= "Congrats! '".AIFS_NAME."' has successfully generated the ${issuerShort} SSL for ".$domain.'. <br /><br />
                                <strong>But there was a problem installing the SSL. Please visit this plugin\'s dashboard and scroll down. We have put a video guide there just now: How to Install Free SSL Certificate on cPanel Shared Hosting.</strong><br /><br />
                                Please find the SSL files at the locations given below (web hosting log in required to access) and install SSL manually.<br />';

                $body_log[] = 'But there was a problem installing the SSL';
                $body_log[] = 'Please find the SSL files at the locations given below (web hosting log in required to access) and install SSL manually';
            }
        } else {
            //Send email with paths of SSL and CA bundle,
            //but do not attach private key. Send the location instead
            $subject = "'".AIFS_NAME."' generated ${issuerShort} SSL for ".$domain;
            $body .= "<h2><a href='https://freessl.tech'>".AIFS_NAME."</a> generated ${issuerShort} SSL for ".$domain.'</h2><br />';
            $body .= "Congrats! '".AIFS_NAME."' has successfully generated the ${issuerShort} SSL for ".$domain.". <br /><br />
                            <strong>But the SSL was not installed automatically, because you don't have an SSL installation feature most probably. Please visit this plugin's dashboard and scroll down. We have put a video guide there just now: How to Install Free SSL Certificate on cPanel Shared Hosting.</strong><br /><br />
                            Please find the SSL files at the locations given below (web hosting log in required to access) and install SSL manually with the help of your web hosting service provider. <em>It is recommended not to download the SSL files for security reason. Please copy the SSL locations and send the text to your web host.</em><br />";

            $body_log[] = "But the SSL was not installed automatically, because you don't have SSL installation feature";
            $body_log[] = 'Please find the SSL files at the locations given below (you need to log in to web hosting to access these) and install SSL manually. Please take the help of your web hosting service provider, if required. It is recommended not to download the SSL files for security reason. Please copy the following SSL locations and send the text to your web host.';
        }
        //Common element of the email

        $body .= "<pre>
        Certificate (CRT): ${certificate}<br />
        Private Key (KEY): ${private_key}<br />
        Certificate Authority Bundle (CABUNDLE): ${ca_bundle}</pre><br />
        This SSL certificate has been issued for the following domain names:<br /><pre>
        ".$subjectAltName."<br /></pre>
        Expiry date: ${expiry_date}<br /><br />
        Issuer: ${issuerFull}<br /><br />";
        
        //$body .= "<br /><br />";
        
        $body .= $this->add_review_request_in_email(); /* Review display option @since 1.1.0 */
        
        $body .= $this->add_email_signature();        
        $body .= "</body></html>";
        
        $body_log[] = "Certificate (CRT): ${certificate}";
        $body_log[] = "Private Key (KEY): ${private_key}";
        $body_log[] = "Certificate Authority Bundle (CABUNDLE): ${ca_bundle}";
        $body_log[] = "Expiry date: ${expiry_date}";

        $this->logger->log($subject);

        foreach ($body_log as $bl) {
            $this->logger->log($bl);
        }

        //Send email to admin email id and Let's Encrypt registrant email id (provided in basic settings) if both are different
        if(in_array($admin_email, $le_registrant_email)){
            $to = implode(',', $le_registrant_email);
        }
        else{
            $to = $admin_email . "," . implode(',', $le_registrant_email);
        }

        // Set content-type header
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = 'From:wordpress@'.aifs_get_domain();

        // Send the email
        
        if (wp_mail($to, $subject, $body, $headers)) {
            $this->logger->log('Congratulations, email to admin was sent successfully!');
        } else {
            $this->logger->log('Sorry, there was an issue sending the email.');
        }
    }
    
    
    /**
     * If the user provided cPanel credentials over unsecured connection (HTTP), send an email
     * recommending password change and update the same in cPanel Settings.
     * 
     * @since 2.1.0
     */
    public function send_cpanel_password_change_notification(){
        
        $app_settings = aifs_get_app_settings();
        
        $this_domain = aifs_get_domain(false);
        
        $factory =  new Factory();
                
        if(isset($app_settings['send_security_notification']) && $app_settings['send_security_notification'] && $factory->is_ssl_issued_and_valid($this_domain)){
            
            $subject = "Security Notification from '".AIFS_NAME."'";
            
            $aifs_force_ssl = get_option( 'aifs_force_ssl' );
            
            $admin_email = get_option('admin_email');
            $admin = get_user_by('email', $admin_email);
            $admin_first_name = $admin->first_name;
            
            //Email body
            $body = "<html><body>Hello ".$admin_first_name.",<br /><br />";
            
            $body .= "Thanks for using '".AIFS_NAME."'.<br /><br />
                
                During configuring this WordPress plugin at <strong>$this_domain</strong> you provided cPanel password over simple HTTP, which is not
                a best practice from the security point of view. Data travels the internet as plain text on HTTP.
                On the other hand, on an HTTPS connection data travels with encryption.<br /><br />";
            
            if(get_option('aifs_ssl_installed_on_this_website')){//SSL installed on this website
                
                $body .= "As our plugin has installed an SSL certificate for you, your website is 
                        now capable of this encryption. <strong>So we strongly recommend the 
                        following security precautions:</strong><br /><ol>";
                
            }
            else{//SSL issued but not installed
                $body .= "Our plugin has issued an SSL certificate for your website. <strong>So we 
                        strongly recommend the following security precautions:</strong><br />                    
                    <ol>";
                
                if($aifs_force_ssl != 1){
                    $body .= "<li>Please install the SSL certificate on your website. It will be 
                            capable of encryption.</li>"; 
                }
            }
            
            if($aifs_force_ssl != 1){
                $body .= "<li>Login to your WordPress website, go to the dashboard of '".AIFS_NAME."' 
                    and click the 'Activate Force HTTPS' button. Now your website loads over HTTPS, 
                    and you get the padlock in the browser address bar.</li>";
            }
            
            $body .= "<li>Change your cPanel password.</li>
                <li>Go to the 'cPanel settings' page of ".AIFS_NAME.", provide the changed cPanel password and submit.</li>
                </ol>
            That's it!<br /><br />";
            
            $body .= $this->add_review_request_in_email();
            
            $body .= $this->add_email_signature();
            $body .= "</body></html>";
            
            //Send email to admin email id and Let's Encrypt registrant email id (provided in basic settings) if both are different
            if(in_array($admin_email, $app_settings['admin_email'])){
                $to = implode(',', $app_settings['admin_email']);
            }
            else{
                $to = $admin_email . "," . implode(',', $app_settings['admin_email']);
            }
            
            // Set content-type header
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';
            $headers[] = 'From:wordpress@'.aifs_get_domain();
            
            // Send the email
            
            if (wp_mail($to, $subject, $body, $headers)) {
                $this->logger->log('Security notification email was sent successfully!');
            } else {
                $this->logger->log('Sorry, there was an issue sending the security notification email.');
            }
            
        }
    }
    
    
    
    /**
     * Send Email notification if encryption key has been changed due to update
     *
     * @since 2.1.1
     */
    public function send_encryption_key_change_notification_email($text){
        
        $app_settings = aifs_get_app_settings();
                
        $notification = maybe_unserialize(get_option('aifs_encryption_key_change_notification_email'));
                
        if(isset($notification['sent_date'])){
            $sent_date = new DateTime($notification['sent_date']);
            $now = new DateTime();
            
            $interval = (int) $sent_date->diff($now)->format('%R%a');
        }
        else{
            $interval = 0;
        }
        
        
        if(!isset($notification['email_sent']) || !$notification['email_sent'] || $interval > 7){
            
            $subject = "'".AIFS_NAME."' : ".__("An action required from your end", 'auto-install-free-ssl' );
            
            $admin_email = get_option('admin_email');
            $admin = get_user_by('email', $admin_email);
            $admin_first_name = $admin->first_name;
            
            //Email body
            $body = "<html><body>" . __("Hello", 'auto-install-free-ssl' ) ." ". $admin_first_name.",<br />";
            
            $body .= $text;
                        
            $body .= $this->add_email_signature();
            $body .= "</body></html>";
            
            //Send email to admin email id and Let's Encrypt registrant email id (provided in basic settings) if both are different
            if(in_array($admin_email, $app_settings['admin_email'])){
                $to = implode(',', $app_settings['admin_email']);
            }
            else{
                $to = $admin_email . "," . implode(',', $app_settings['admin_email']);
            }
            
            // Set content-type header
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';
            $headers[] = 'From:wordpress@'.aifs_get_domain();
            
            // Send the email            
            if (wp_mail($to, $subject, $body, $headers)) {
                
                $date = new DateTime();                
                
                $notification_values = array(
                    'email_sent' => true,
                    'sent_date' => $date->format('Y-m-d')
                );
                
                update_option('aifs_encryption_key_change_notification_email', maybe_serialize($notification_values));
                
            }
        }
    }
    
    
    
}
