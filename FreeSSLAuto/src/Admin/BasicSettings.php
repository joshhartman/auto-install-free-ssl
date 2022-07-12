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

use AutoInstallFreeSSL\FreeSSLAuto\Admin\Factory;
use AutoInstallFreeSSL\FreeSSLAuto\Acme\Factory as AcmeFactory;
use AutoInstallFreeSSL\FreeSSLAuto\Email;

/**
 * Basic settings to set the very basic options
 *
 */
class BasicSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
    public $display_review;
    
    public $acmeFactory;
    
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
        
        add_action( 'admin_enqueue_scripts', array( $this, 'aifs_admin_styles' ) );
        
        // Set class property
        $this->options = get_option('basic_settings_auto_install_free_ssl') ? get_option('basic_settings_auto_install_free_ssl') : add_option('basic_settings_auto_install_free_ssl');
        
        //hook
        add_action('admin_menu', array( $this, 'add_basic_settings_menu' ));
        add_action('admin_init', array( $this, 'basic_settings_page_init' ));
        add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
                
        /*          
         * Review display option @since 1.1.0
         * 
         */
        
        if(isset($this->options['homedir'])){
            
            //initialize the Acme Factory class
            $this->acmeFactory = new AcmeFactory($this->options['homedir'].'/'.$this->options['certificate_directory'], $this->options['acme_version'], $this->options['is_staging']);
            
            //get the path of SSL files
            $certificates_directory = $this->acmeFactory->getCertificatesDir();
            
            if(is_dir($certificates_directory)){
                
                $factory =  new Factory();
                
                //get the domains for which SSL is present in the certificate directory
                $all_domains = $factory->getExistingSslList($certificates_directory);
                
                //If at least one SSL cert exists in the $certificates_directory, set 'aifs_display_review' = 1 if this option doesn't exist
                if (count($all_domains) > 0) {
                    
                    if(!get_option('aifs_display_review'))
                        add_option('aifs_display_review', 1);
                }
            }
        }
        
        
        if (is_admin()) {
            add_action( 'admin_notices', array( $this, 'aifs_display_admin_notice' ) );
        }
        else{
            //add_action( 'init', array( $this, 'aifs_display_admin_notice' ) );//Send the email even if the frontend page loaded : Not working
        }
                
        add_action( 'admin_init', array( $this, 'aifs_admin_notice_handler' ) );
        
        /*          
         * Announcement display option @since 2.2.2
         * 
         */
        
        if(!get_option('aifs_display_announcement')){
            add_option('aifs_display_announcement', 1);
        }
               
    }
    
    
    /**
     * Enqueue admin CSS and JS
     *
     * @since 1.1.0
     */
    public function aifs_admin_styles(){
        
        wp_enqueue_style(
            AIFS_NAME,
            AIFS_URL . 'assets/css/aifs-admin.css',
            FALSE,
            AIFS_VERSION,
            'all'
        );
        
    }

    
    /**
     * Display Admin notice
     *
     * @since 2.1.1
     */
    public function aifs_display_admin_notice(){
        
        $display_review_request = true;
        $cpanel_password_missing = false;
        $dns_api_credential_missing = false;
        
        if(isset($this->options['homedir'])){
            $app_settings = aifs_get_app_settings();
                        
            //Check cPanel settings
            if($app_settings['is_cpanel'] && !empty($app_settings['cpanel_host']) && !empty($app_settings['username']) && !empty($app_settings['password'])){
                
                $factory =  new Factory();
                $cpanel_password_decrypted = $factory->decryptText($app_settings['password']);
                
                if(empty($cpanel_password_decrypted)){
                    $cpanel_password_missing = true;
                    $display_review_request = false;
                }
            }
            
            //Check DNS service provider settings
            if($app_settings['use_wildcard'] && isset($app_settings['dns_provider']) && is_array($app_settings['dns_provider']) && !empty($app_settings['dns_provider'][0]['api_credential'])){
                
                $factory =  new Factory();
                $dns_api_credential_decrypted = $factory->decryptText($app_settings['dns_provider'][0]['api_credential']);
                
                if(empty($dns_api_credential_decrypted)){
                    $dns_api_credential_missing = true;
                    $display_review_request = false;
                }
                
            }
            
            $counter = (int)get_option('aifs_admin_notice_display_counter');
            
            if($counter % 3 == 0){
                
                $this->aifs_encryption_key_change_notification($cpanel_password_missing, $dns_api_credential_missing);
            
                $this->aifs_display_review_request($display_review_request);
            }
            else{
                /*
                 * Display announcement only if 'aifs_display_review' exists,
                 * i.e., at least one SSL cert issued
                 */
                if(get_option('aifs_display_review') !== false){

                    $this->aifs_display_announcement();
                }
            }
            
            
            $this->aifs_admin_notice_display_counter();
        }
        
       
    }
    
    
    /**
     * Display encryption key change notification
     *
     * @since 2.1.1
     */
    public function aifs_encryption_key_change_notification($cpanel_password_missing, $dns_api_credential_missing){
                        
        $text = "<p><strong>" . AIFS_NAME . "</strong> ".__("encryption key has been changed during the recent update. So we are unable to retrieve your", 'auto-install-free-ssl' );
        
        if($cpanel_password_missing){
            $text .= __(" cPanel password", 'auto-install-free-ssl' );
        }
                
        if($cpanel_password_missing && $dns_api_credential_missing){            
            $text .= __(" and", 'auto-install-free-ssl' );            
        }
                
        if($dns_api_credential_missing){            
            $text .= __(" DNS Service Provider API Credential", 'auto-install-free-ssl' );            
        }
        
        $text .= ".</p> ";
        
        $text .= "<p>".__("Please update the", 'auto-install-free-ssl' )." ";
        
        if($cpanel_password_missing){
            $text .= " <a href='".get_site_url()."/wp-admin/admin.php?page=aifs_cpanel_settings'>".__("cPanel Settings", 'auto-install-free-ssl' )."</a> ";
        }
                
        if($cpanel_password_missing && $dns_api_credential_missing){
            $text .= __("and", 'auto-install-free-ssl' )." ";
        }
                
        if($dns_api_credential_missing){
            $text .= " <a href='".get_site_url()."/wp-admin/admin.php?page=aifs_dns_service_providers'>".__("DNS Service Provider API Credential", 'auto-install-free-ssl' )."</a> ";
        }
        
        $text .= __("once again, provide the password/credential and click 'Save Changes'", 'auto-install-free-ssl' ) . ".</p>";
                        
        $text .= "<p>". __("We are extremely sorry for the inconvenience caused", 'auto-install-free-ssl' ).".</p>";
        
        
        if ($cpanel_password_missing || $dns_api_credential_missing){
            
            echo '<div class="notice notice-error">'.$text.'</div>';
            
        }
        
        
        if ($cpanel_password_missing || $dns_api_credential_missing){
            //Add more text for email
            $text .= "<p>". __("For your information, we encrypt your password/credentials with the encryption key and save it in your database. We decrypt them using the same encryption key to issue/install free SSL certificates. Due to the change in the encryption key, we are currently unable to retrieve them.", 'auto-install-free-ssl' )."</p>";
            
            //send the email (once in a week until the required action)
            $email = new Email();
            $email->send_encryption_key_change_notification_email($text);
        }
        
    }
    
    
    
    /**
     * Display review request
     * 
     * @since 1.1.0
     */
    public function aifs_display_review_request($display_review_request){
        
        //Get the value of aifs_display_review
        $display_review = get_option( 'aifs_display_review' );
        
        if ($display_review_request && $display_review != false && $display_review == 1 ){
            
            $admin_email = get_option('admin_email');        
            $admin = get_user_by('email', $admin_email);
            $admin_first_name = $admin->first_name;
            
            $already_done = wp_nonce_url( get_site_url().$_SERVER['REQUEST_URI'], 'aifs_reviewed', 'aifsrated' );
            $remind_later = wp_nonce_url( get_site_url().$_SERVER['REQUEST_URI'], 'aifs_review_later', 'aifslater' );
            $html = '<div class="notice notice-success aifs-review">
			<div class="aifs-review-box">
			  <img class="aifs-notice-img-left" src="' . AIFS_URL . 'assets/img/icon.jpg" />
			  <p>' . __('Hey', 'auto-install-free-ssl' ) .' '.$admin_first_name. ', <strong>' . AIFS_NAME . '</strong> ' . __( 'has saved 			your $$$ by providing Free SSL Certificates and will save more. Could you please do me a BIG favor and give it a 5-star 		rating on WordPress? To help me spread the word and boost my motivation.', 'auto-install-free-ssl' ) . ' <br />~Anindya</p>
			</div>
			<a class="aifs-review-now aifs-review-button" href="https://wordpress.org/support/plugin/auto-install-free-ssl/reviews/#new-post" target="_blank">' . esc_html__( 'Sure! You Deserve It.', 'auto-install-free-ssl' ) . '</a>
			<a class="aifs-review-button" href="' . $already_done . '" rel="nofollow" onclick="return confirm(\'Are you sure you have reviewed '.AIFS_NAME.' plugin?\')">' . esc_html__( 'I have done', 'auto-install-free-ssl' ) . '</a>
			<a class="aifs-review-button" href="' . $remind_later . '" rel="nofollow" onclick="return confirm(\'Are you sure you need '.AIFS_NAME.' to remind you later?\')">' . esc_html__( 'Remind me later', 'auto-install-free-ssl' ) . '</a>
		      </div>';
            echo  $html ;
        }        
    }
    
    
    /**
     * Display announcement
     * 
     * @since 2.2.2
     */
    public function aifs_display_announcement(){
        
        //Get the value of aifs_display_announcement
        $display_announcement = get_option( 'aifs_display_announcement' );
        
        if ($display_announcement !== false && $display_announcement == 1 ){
            
            $admin_email = get_option('admin_email');        
            $admin = get_user_by('email', $admin_email);
            $admin_first_name = $admin->first_name;
            
            $already_read = wp_nonce_url( get_site_url().$_SERVER['REQUEST_URI'], 'aifs_announcement_already_read', 'aifsannouncementdone' );
            $remind_later = wp_nonce_url( get_site_url().$_SERVER['REQUEST_URI'], 'aifs_announcement_read_later', 'aifsannouncementlater' );
                        
            $html = '<div class="notice notice-warning aifs-review">
                    <div class="aifs-review-box">                      
                      <p>' . __('Hello', 'auto-install-free-ssl' ) .' '.$admin_first_name. '; <span style="color: #ffb900;">' . __( 'we are going to restructure the features of ', 'auto-install-free-ssl' ) . '<strong>' . AIFS_NAME . '.</strong></span><br />' . __( 'Please take a moment to read our announcement regarding the Survival challenge and the solution [premium version].', 'auto-install-free-ssl' ) . '</p>
                      <img class="aifs-notice-img-right" src="' . AIFS_URL . 'assets/img/icon.jpg" />
                    </div>
                    <a class="aifs-review-now aifs-review-button" href="https://freessl.tech/blog/auto-install-free-ssl-needs-your-help-to-survive" target="_blank">' . esc_html__( 'Read Announcement', 'auto-install-free-ssl' ) . '</a>
                    <a class="aifs-review-button" href="' . $already_read . '" rel="nofollow" onclick="return confirm(\'Are you sure you have read the Announcement regarding the Premium Version of '.AIFS_NAME.' plugin?\')">' . esc_html__( 'I have already read', 'auto-install-free-ssl' ) . '</a>
                    <a class="aifs-review-button" href="' . $remind_later . '" rel="nofollow" onclick="return confirm(\'Are you sure you need '.AIFS_NAME.' to remind you later to read the Announcement regarding the Premium Version?\')">' . esc_html__( 'Remind me later', 'auto-install-free-ssl' ) . '</a>
                                      
                    </div>';
            
            echo  $html ;
        }        
    }
    
    
    /**
     * Execute admin notice actions
     *
     * @since 1.1.0 (renamed since 2.2.2)
     */
    public function aifs_admin_notice_handler()
    {
        
        //Review
        if ( isset( $_GET['aifsrated'] ) ) {
            if ( !wp_verify_nonce( $_GET['aifsrated'], 'aifs_reviewed' ) ) {
                wp_die( 'Access denied' );
            }
            update_option( 'aifs_display_review', 0);
            wp_redirect($this->aifs_remove_parameters_from_url(get_site_url().$_SERVER['REQUEST_URI'], ['aifsrated']));
        } else {
            
            if ( isset( $_GET['aifslater'] ) ) {
                if ( !wp_verify_nonce( $_GET['aifslater'], 'aifs_review_later' ) ) {
                    wp_die( 'Access denied' );
                }
                update_option( 'aifs_display_review', 5);
                wp_schedule_single_event(strtotime("+5 days", time()), 'aifs_display_review_init' );
                wp_redirect($this->aifs_remove_parameters_from_url(get_site_url().$_SERVER['REQUEST_URI'], ['aifslater']));
            }
            
        }
        
        //Announcement
        if ( isset( $_GET['aifsannouncementdone'] ) ) {
            if ( !wp_verify_nonce( $_GET['aifsannouncementdone'], 'aifs_announcement_already_read' ) ) {
                wp_die( 'Access denied' );
            }
            update_option( 'aifs_display_announcement', 0);
            wp_redirect($this->aifs_remove_parameters_from_url(get_site_url().$_SERVER['REQUEST_URI'], ['aifsannouncementdone']));
        } else {
            
            if ( isset( $_GET['aifsannouncementlater'] ) ) {
                if ( !wp_verify_nonce( $_GET['aifsannouncementlater'], 'aifs_announcement_read_later' ) ) {
                    wp_die( 'Access denied' );
                }
                update_option( 'aifs_display_announcement', 5);
                wp_schedule_single_event(strtotime("+3 days", time()), 'aifs_display_announcement_init' );
                wp_redirect($this->aifs_remove_parameters_from_url(get_site_url().$_SERVER['REQUEST_URI'], ['aifsannouncementlater']));
            }
            
        }
        
    }
    

    /**
     * Admin notice display counter. Required to display more than one admin notices alternately
     * 
     * @since 2.2.2
     */
    public function aifs_admin_notice_display_counter() {
        
        if(!get_option('aifs_admin_notice_display_counter')){
            add_option('aifs_admin_notice_display_counter', 1);
        }
        else{
            $counter = get_option('aifs_admin_notice_display_counter') < 99999999 ? get_option('aifs_admin_notice_display_counter') : 0; //if equal to 99999999, reset to 0
            update_option('aifs_admin_notice_display_counter', ($counter+1));
        }
    }
    
    
    /**
     * Remove parameters from a given URL
     * 
     * @param string $url
     * @param array $exclude_parameters
     * 
     *  @since 2.2.2
     */
    public function aifs_remove_parameters_from_url($url, $exclude_parameters){
        
        $url_parts = explode('?', $url);
        
        if(empty($url_parts[1])){
            return $url;
        }
        
        $query_string = $url_parts[1];
        
        $query_parameters = explode('&', $query_string);
        
        $query_parameters_filtered = [];
        
        foreach ($query_parameters as $parameter){
            
            if(!empty($parameter)){
            
                $parameter_key_value = explode('=', $parameter);

                if(!in_array($parameter_key_value[0], $exclude_parameters)){

                    $query_parameters_filtered[] = $parameter;
                }
            }
        }
        
        
        if(count($query_parameters_filtered) > 0){
            return $url_parts[0].'?'.implode('&', $query_parameters_filtered);
        }
        else{
            return $url_parts[0];
        }
        
    }
    
    
    /**
     * Add the sub menu
     */
    public function add_basic_settings_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Basic Settings Page", 'auto-install-free-ssl'), esc_html__("Basic Settings", 'auto-install-free-ssl'), 'manage_options', 'aifs_basic_settings', array( $this, 'create_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
            exit;
        }
        
        global $wp_version;
        
        $version_parts = explode(".", $wp_version);
        
        $version_base = (int) $version_parts[0];
        
        /* if (!defined('AIFS_ENC_KEY')) {
            $factory = new Factory();
            $key = $factory->encryptionTokenGenerator();
            
            $entry = <<<ENTRY
            
/** Auto-Install Free SSL encryption key */ /*
define('AIFS_ENC_KEY', '${key}');

ENTRY;
            $file_path = __DIR__.DS.'aifs-config.php';
            
            if($version_base === 5){
                $style = 'class="notice notice-error"';
            }
            else{
                $style = 'id="message" class="error below-h2"';
            } 
            
            echo "<div $style>".esc_html__("We could not write to the aifs-config.php file. Please copy the following content and paste it into this file", 'auto-install-free-ssl').": <strong>$file_path</strong><br /><pre>$entry</pre></div>";
        } */
    
        echo '<div class="wrap">';
        
        echo '<h1>'.esc_html__("Basic Settings", 'auto-install-free-ssl').'</h1>';
    
        echo '<form method="post" action="options.php">';
                        
        settings_fields('basic_settings_ais_group');
        do_settings_sections('basic_settings_ais_admin');
        submit_button();
        echo '<a href="'.menu_page_url('auto_install_free_ssl', false).'" class="page-title-action button">'.esc_html__("Cancel", 'auto-install-free-ssl').'</a>';
            
        echo '</form>'; ?>
                        
            <!-- Powered by -->
            <br />
            <div class="header-footer">
              	<p>             
              		<?php echo esc_html__("Need help", 'auto-install-free-ssl'); ?>? <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#help" target="_blank">Click here!</a> <span style="margin-left: 15%;"><?php echo esc_html__("For documentation", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#documentation" target="_blank">click here</a>.</span>
              	</p>          	
          	</div> <!-- End Powered by -->
                    
        <?php
            echo '</div>';
    }

    /**
     * Register and add settings
     */
    public function basic_settings_page_init()
    {
        register_setting(
            'basic_settings_ais_group', // Option group
            'basic_settings_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'basic_settings_section_id', // Section ID
            esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'),
            array( $this, 'print_section_info' ), // Callback
            'basic_settings_ais_admin' // Page
        );

        add_settings_field(
            'acme_version', // acme_version
            esc_html__("Let's Encrypt ACME version", 'auto-install-free-ssl'), // acme_version
            array( $this, 'acme_version_callback' ), // Callback
            'basic_settings_ais_admin', // Page
            'basic_settings_section_id' // Section ID
        );

        add_settings_field(
            'use_wildcard',
            esc_html__("Use wildcard SSL for sub-domains?", 'auto-install-free-ssl'),
            array( $this, 'use_wildcard_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
        );
        
        add_settings_field(
            'is_staging',
            esc_html__("Issue real SSL cert (LIVE)?", 'auto-install-free-ssl'),
            array( $this, 'is_staging_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'admin_email',
            __("Your email id to register Let's Encrypt account <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'admin_email_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'country_code',
            esc_html__("Your Country", 'auto-install-free-ssl'),
            array( $this, 'country_code_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'state',
            __("State <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'state_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'organization',
            esc_html__("Organization", 'auto-install-free-ssl'),
            array( $this, 'organization_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'homedir',
            __("Home directory of your server <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'homedir_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'certificate_directory',
            __("Provide a name of the SSL certificate directory <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'certificate_directory_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'days_before_expiry_to_renew_ssl',
            esc_html__("Number of days prior the expiry date you want to renew the SSL", 'auto-install-free-ssl'),
            array( $this, 'days_before_expiry_to_renew_ssl_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'is_cpanel',
            __("Is your web hosting control panel cPanel? <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'is_cpanel_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'server_ip',
            __("IP Address of this server <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'server_ip_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'using_cdn',
            __("Are you using Cloudflare or any other CDN? <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'using_cdn_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'key_size',
            esc_html__("Key Size for SSL certificates", 'auto-install-free-ssl'),
            array( $this, 'key_size_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'agree_to_le_terms',
            __('I agree to the <a href="https://acme-v01.api.letsencrypt.org/terms" target="_blank">Let\'s Encrypt Subscriber Agreement</a> <sup>(required)</sup>', 'auto-install-free-ssl'),
            array( $this, 'agree_to_le_terms_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
        
        add_settings_field(
            'agree_to_freessl_tech_tos_pp',
            __('I agree to the FreeSSL.tech <a href="https://freessl.tech/terms-of-service" target="_blank">Terms of Service</a> and <a href="https://freessl.tech/privacy-policy" target="_blank">Privacy Policy</a> <sup>(required)</sup>', 'auto-install-free-ssl'),
            array( $this, 'agree_to_freessl_tech_tos_pp_callback' ),
            'basic_settings_ais_admin',
            'basic_settings_section_id'
            );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input (Contains all settings fields as array keys)
     */
    public function sanitize($input)
    {
        $new_input = array();
        
        /* if (isset($input['acme_version'])) {
            $new_input['acme_version'] = absint($input['acme_version']);            
        } */
        
        $new_input['acme_version'] = AIFS_DEFAULT_LE_ACME_VERSION; //LE ACME V1 is reaching end of life soon. So, we prefer using V2.

        if (isset($input['use_wildcard'])) {
            $new_input['use_wildcard'] = (bool) filter_var($input['use_wildcard'], FILTER_SANITIZE_NUMBER_INT);
        }
        
        if (isset($input['is_staging'])) {
            $new_input['is_staging'] = (bool) filter_var($input['is_staging'], FILTER_SANITIZE_NUMBER_INT);
        }
        
        if (isset($input['admin_email'])) {
            $new_input['admin_email'][0] = sanitize_email($input['admin_email']);
        }
       
        if (isset($input['country_code'])) {
            $new_input['country_code'] = sanitize_text_field($input['country_code']);
        }
        
        if (isset($input['state'])) {
            $new_input['state'] = sanitize_text_field($input['state']);
        }
        
        if (isset($input['organization'])) {
            $new_input['organization'] = sanitize_text_field($input['organization']);
        }
        
        if (isset($input['homedir'])) {
            $new_input['homedir'] = sanitize_text_field($input['homedir']);
            $new_input['homedir'] = rtrim($new_input['homedir'], '\/'); //remove / at the end
        }
        
        if (isset($input['certificate_directory'])) {
            $new_input['certificate_directory'] = sanitize_text_field($input['certificate_directory']);
        }
        
        if (isset($input['days_before_expiry_to_renew_ssl'])) {
            $new_input['days_before_expiry_to_renew_ssl'] = absint($input['days_before_expiry_to_renew_ssl']);
        }
        
        if (isset($input['is_cpanel'])) {
            $new_input['is_cpanel'] = (bool) filter_var($input['is_cpanel'], FILTER_SANITIZE_NUMBER_INT);
        }
        
        if (isset($input['server_ip'])) {
            $new_input['server_ip'] = sanitize_text_field($input['server_ip']);
        }
        
        if (isset($input['using_cdn'])) {
            $new_input['using_cdn'] = (bool) filter_var($input['using_cdn'], FILTER_SANITIZE_NUMBER_INT);
        }
        
        if (isset($input['key_size'])) {
            $new_input['key_size'] = absint($input['key_size']);
        }
        
        if (isset($input['agree_to_le_terms'])) {
            $new_input['agree_to_le_terms'] = sanitize_text_field($input['agree_to_le_terms']);
        }
         
        if (isset($input['agree_to_freessl_tech_tos_pp'])) {
            $new_input['agree_to_freessl_tech_tos_pp'] = sanitize_text_field($input['agree_to_freessl_tech_tos_pp']);
        }
            
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo esc_html__("Please fill in the following form and click 'Save Changes' button:", 'auto-install-free-ssl');
    }

    /**
     * acme_version
     */
    public function acme_version_callback()
    {
        ?>
        <select id="acme_version" name="basic_settings_auto_install_free_ssl[acme_version]" disabled>
        		<option<?php echo (isset($this->options['acme_version']) && 2 === $this->options['acme_version']) ? ' selected' : null; ?>>2</option>
                <option<?php echo (isset($this->options['acme_version']) && 1 === $this->options['acme_version']) ? ' selected' : null; ?>>1</option>                                              
        </select>
        <label for="use_wildcard">
        	<?php echo esc_html__("Let's Encrypt ACME version 1 is reaching the end of life soon. So, we prefer to use version 2.", 'auto-install-free-ssl'); ?>        	
        </label>
         <?php
    }

    /**
     * use_wildcard
     */
    public function use_wildcard_callback()
    {
        ?>
        <select id="use_wildcard" name="basic_settings_auto_install_free_ssl[use_wildcard]">
               <option value="0"<?php echo (isset($this->options['use_wildcard']) && false === $this->options['use_wildcard']) ? ' selected' : null; ?>>No</option>
               <option value="1"<?php echo (isset($this->options['use_wildcard']) && true === $this->options['use_wildcard']) ? ' selected' : null; ?>>Yes</option>
        </select>
        <label for="use_wildcard">
        	<?php echo esc_html__("Please note that wildcard SSL needs additional settings with DNS. You'll need to set DNS TXT records manually if your Domain registrar/ DNS service provider is other than cPanel, Godaddy, Namecheap, and Cloudflare. If you don't know what DNS TXT record is, we strongly recommend selecting 'No'.", 'auto-install-free-ssl'); ?>        	
        </label>
        <?php
    }
    
    /**
     * is_staging
     */
    public function is_staging_callback()
    {
        ?>
        <select id="is_staging" name="basic_settings_auto_install_free_ssl[is_staging]">
               <option value="0"<?php echo (isset($this->options['is_staging']) && false === $this->options['is_staging']) ? ' selected' : null; ?>>Yes</option>
               <option value="1"<?php echo (isset($this->options['is_staging']) && true === $this->options['is_staging']) ? ' selected' : null; ?>>No</option>
        </select>
        
        <?php
    }
    
    /**
     * admin_email
     */
    public function admin_email_callback()
    {
        //Get current user details
        global $current_user;
        get_currentuserinfo();
        
        printf(
         '<input type="email" id="admin_email" name="basic_settings_auto_install_free_ssl[admin_email]" required="required" value="%s" />',
            isset($this->options['admin_email']) ? esc_attr($this->options['admin_email'][0]) : $current_user->user_email
         );
    }
    
    /**
     * country_code
     */
    public function country_code_callback()
    {
        $countries = file_get_contents(__DIR__.'/country_code.json');
        $countries_array = json_decode($countries, true); ?>
        <select id="country_code" name="basic_settings_auto_install_free_ssl[country_code]">
               <?php foreach ($countries_array as $country) {
            ?>
                   <option value="<?php echo $country['Code']; ?>"<?php echo (isset($this->options['country_code']) && $this->options['country_code'] === $country['Code']) ? ' selected' : null; ?>><?php echo $country['Name']; ?></option>
               <?php
        } ?>
        </select>
        
        <?php
    }
    
    /**
     * state
     */
    public function state_callback()
    {
        printf(
            '<input type="text" id="state" name="basic_settings_auto_install_free_ssl[state]" required="required" value="%s" />',
            isset($this->options['state']) ? esc_attr($this->options['state']) : ''
            );
    }
    
    
    /**
     * organization
     */
    public function organization_callback()
    {
        printf(
            '<input type="text" id="organization" name="basic_settings_auto_install_free_ssl[organization]" value="%s" />',
            isset($this->options['organization']) ? esc_attr($this->options['organization']) : ''
            );
    }
    
    
    /**
     * homedir
     */
    public function homedir_callback()
    {
        printf(
            '<input type="text" id="homedir" name="basic_settings_auto_install_free_ssl[homedir]" required="required" value="%s" placeholder="/home/username" />',
            isset($this->options['homedir']) ? esc_attr($this->options['homedir']) : (isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '')
            );
        echo __("<p>Don't use a trailing slash. It should be a directory that is NOT accessible to the public. Your private keys will be saved here. This directory should be writable.</p>", 'auto-install-free-ssl');
    }
    
    
    /**
     * certificate_directory
     */
    public function certificate_directory_callback()
    {
        printf(
            '<input type="text" id="certificate_directory" name="basic_settings_auto_install_free_ssl[certificate_directory]" required="required" value="%s" placeholder="Don\'t use \'ssl\'" />',
            isset($this->options['certificate_directory']) ? esc_attr($this->options['certificate_directory']) : ''
            );
        echo __("<p>Don't include '/' before or after. This directory will be placed in the Home Directory of your web hosting. Don't use 'ssl' - it is a reserved directory by cPanel.</p>", 'auto-install-free-ssl');
    }
    
    
    /**
     * days_before_expiry_to_renew_ssl
     */
    public function days_before_expiry_to_renew_ssl_callback()
    {
        $day_selected = isset($this->options['days_before_expiry_to_renew_ssl']) ? $this->options['days_before_expiry_to_renew_ssl'] : 30; ?>
        <select id="days_before_expiry_to_renew_ssl" name="basic_settings_auto_install_free_ssl[days_before_expiry_to_renew_ssl]">
               <?php for ($day = 5; $day <= 30; ++$day) {
            ?>
                     <option<?php echo $day === $day_selected ? ' selected' : null; ?>><?php echo $day; ?></option>
               <?php
        } ?>
        </select>
        
        <?php
    }
    
    
    /**
     * is_cpanel
     */
    public function is_cpanel_callback()
    {
        ?>
        <select id="is_cpanel" name="basic_settings_auto_install_free_ssl[is_cpanel]">
               <option value="0"<?php echo (isset($this->options['is_cpanel']) && false === $this->options['is_cpanel']) ? ' selected' : null; ?>>No</option>
               <option value="1"<?php echo (isset($this->options['is_cpanel']) && true === $this->options['is_cpanel']) ? ' selected' : null; ?>>Yes</option>
        </select>
        
        <?php
    }
    
    
    /**
     * server_ip
     */
    public function server_ip_callback()
    {
        printf(
            '<input type="text" id="server_ip" name="basic_settings_auto_install_free_ssl[server_ip]" required="required" value="%s" />',
            isset($this->options['server_ip']) ? esc_attr($this->options['server_ip']) : (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '')
            );
    }
    
    
    /**
     * using_cdn
     */
    public function using_cdn_callback()
    {
        ?>
        <select id="using_cdn" name="basic_settings_auto_install_free_ssl[using_cdn]">
               <option value="0"<?php echo (isset($this->options['using_cdn']) && false === $this->options['using_cdn']) ? ' selected' : null; ?>>No</option>
               <option value="1"<?php echo (isset($this->options['using_cdn']) && true === $this->options['using_cdn']) ? ' selected' : null; ?>>Yes</option>
        </select>
        
        <?php
    }
    
    /**
     * key_size
     */
    public function key_size_callback()
    {
        ?>
        <select id="key_size" name="basic_settings_auto_install_free_ssl[key_size]">
               <option<?php echo (isset($this->options['key_size']) && 2048 === $this->options['key_size']) ? ' selected' : null; ?>>2048</option>
               <option<?php echo (isset($this->options['key_size']) && 3072 === $this->options['key_size']) ? ' selected' : null; ?>>3072</option>
               <option<?php echo (isset($this->options['key_size']) && 4096 === $this->options['key_size']) ? ' selected' : null; ?>>4096</option>
        </select>
        
        <?php
    }
    
    
    /**
     * agree_to_le_terms
     */
    public function agree_to_le_terms_callback()
    {
        ?>
        <input type="checkbox" id="agree_to_le_terms" name="basic_settings_auto_install_free_ssl[agree_to_le_terms]" required="required"<?php echo (isset($this->options['agree_to_le_terms']) && 'on' === $this->options['agree_to_le_terms']) ? ' checked' : null; ?> />
        
        <?php
    }
    
    
    /**
     * agree_to_freessl_tech_tos_pp
     */
    public function agree_to_freessl_tech_tos_pp_callback()
    {
        ?>
        <input type="checkbox" id="agree_to_freessl_tech_tos_pp" name="basic_settings_auto_install_free_ssl[agree_to_freessl_tech_tos_pp]" required="required"<?php echo (isset($this->options['agree_to_freessl_tech_tos_pp']) && 'on' === $this->options['agree_to_freessl_tech_tos_pp']) ? ' checked' : null; ?> />
        
        <?php
    }
    
    /**
     * required for successful redirect
     */
    public function do_output_buffer()
    {
        ob_start();
    }
}
