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
use AutoInstallFreeSSL\FreeSSLAuto\cPanel\cPanel;

/**
 * This plugin require the cPanel hosting credentials
 * in order to perform automated task like installation of SSL certificate
 *
 */
class cPanelSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
    public $factory;
    
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
        
        // Set class property. This is the key for database transactions.
        $this->options = get_option('cpanel_settings_auto_install_free_ssl') ? get_option('cpanel_settings_auto_install_free_ssl') : add_option('cpanel_settings_auto_install_free_ssl');
        
        // Get basic settings if exists
        $basic_settings = get_option('basic_settings_auto_install_free_ssl');
        
        //hook if the web hosting control panel is cPanel
        if (isset($basic_settings['is_cpanel']) && $basic_settings['is_cpanel']) {
            add_action('admin_menu', array( $this, 'add_cpanel_settings_menu' ));
            add_action('admin_init', array( $this, 'cpanel_settings_page_init' ));
        }
        
        $this->factory =  new Factory();
    }
    
    /**
     * Add the sub menu
     */
    public function add_cpanel_settings_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("cPanel Settings Page", 'auto-install-free-ssl'), esc_html__("cPanel Settings", 'auto-install-free-ssl'), 'manage_options', 'aifs_cpanel_settings', array( $this, 'create_cpanel_settings_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_cpanel_settings_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            
            /**
             * Try to connect to the cPanel with the provided data. If not connected, display error msg.
             *
             * @since 2.1.0
             */
            
            $app_settings = aifs_get_app_settings();
            
            $cPanel = new cPanel($app_settings['cpanel_host'], $app_settings['username'], $app_settings['password']);
            
            $request_uri = "https://".$app_settings['cpanel_host'].":2083/execute/DomainInfo/domains_data?format=hash";
            
            $domains_data = $cPanel->connectUapi($request_uri);
            
            //Validate output
            if (empty($domains_data)) {
                ?>
                <div style="background: red; color: #ffffff; padding: 2px; margin-top: 6%;">
                  	<p style="margin-left: 15px;">             
                  		<?= esc_html__("Oops! We can't connect with your cPanel. Please re-check the cPanel credentials and try again.", 'auto-install-free-ssl') ?>             		
                  	</p>          	
              	</div> 
              	
                <?php
                
            }
            else{
                //cPanel credentials are valid. So redirect                
                wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
                exit;
            }
        }
        
        echo '<div class="wrap">';
        echo '<h1>'.esc_html__("cPanel Settings", 'auto-install-free-ssl').'</h1>';
                
        
        if (!is_ssl()) {
            
            global $wp_version;
            
            $version_parts = explode(".", $wp_version);
            
            $version_base = (int) $version_parts[0];
                            
                if($version_base === 5){
                    $style = 'class="notice notice-error"';
                }
                else{
                    $style = 'id="message" class="error below-h2"';
                }   
            
            echo '<div '.$style.'>';
                
            echo '<p>'. sprintf(__('This page is NOT protected with HTTPS and you are going to provide your cPanel credentials over an unencrypted connection. We recommend generating a <a href="%s" target="_blank">Free SSL Certificate for <strong>%s</strong> with a single click</a>, installing it on this server manually and then continue with this page.<br /><br />
                  	You need approx 15 seconds to generate this free SSL. <a href="%s" target="_blank">Please click here!</a>', 'auto-install-free-ssl'), menu_page_url('aifs_temporary_ssl', false), $_SERVER['SERVER_NAME'], menu_page_url('aifs_temporary_ssl', false)) .'</p>';
                
            echo '</div><br />';
        }
            
        echo '<form method="post" action="options.php">';
                
        settings_fields('cpanel_settings_ais_group');
        do_settings_sections('cpanel_settings_ais_admin');
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
    public function cpanel_settings_page_init()
    {
        register_setting(
            'cpanel_settings_ais_group', // Option group
            'cpanel_settings_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'cpanel_settings_section_id', // Section ID
            esc_html__("This app will use cPanel API to fetch all of your domain details and to Auto-Install Free SSL certificates", 'auto-install-free-ssl'),
            array( $this, 'print_section_info' ), // Callback
            'cpanel_settings_ais_admin' // Page
        );
        
        add_settings_field(
            'cpanel_host',
            __("Your cPanel host/login URL <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'cpanel_host_callback' ),
            'cpanel_settings_ais_admin',
            'cpanel_settings_section_id'
            );
                       
        add_settings_field(
            'username',
            __("The username to log in your cPanel <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'username_callback' ),
            'cpanel_settings_ais_admin',
            'cpanel_settings_section_id'
            );
        
        add_settings_field(
            'password',
            __("Password of your cPanel <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'password_callback' ),
            'cpanel_settings_ais_admin',
            'cpanel_settings_section_id'
            );
        
        add_settings_field(
            'confirm_password',
            __("Confirm Password <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'confirm_password_callback' ),
            'cpanel_settings_ais_admin',
            'cpanel_settings_section_id'
            );
        
        add_settings_field(
            'notification',
            "",
            array( $this, 'notification_callback' ),
            'cpanel_settings_ais_admin',
            'cpanel_settings_section_id'
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
        
        if (isset($input['cpanel_host'])) {
            $cpanel_host = sanitize_text_field($input['cpanel_host']);
        }
        
        //use parse_url only if https:// and/or :2083 exist in url
        if (false !== strpos($cpanel_host, 'https://') || false !== strpos($cpanel_host, 'http://') || false !== strpos($cpanel_host, ':2083')) {
            $cpanel_host = parse_url($cpanel_host);
            $cpanel_host = $cpanel_host['host'];
        }
            
        $new_input['cpanel_host'] = $cpanel_host;
        
        if (isset($input['username'])) {
            $new_input['username'] = sanitize_text_field($input['username']);
        }
        
        //Password
        if (isset($input['password'])) {
            $password = sanitize_text_field($input['password']);
        }
        
        if (isset($input['confirm_password'])) {
            $confirm_password = sanitize_text_field($input['confirm_password']);
        }
                    
        $new_input['password'] = $this->factory->encryptText($password);
        
        if (isset($input['send_security_notification'])) {
            $new_input['send_security_notification'] = (bool) filter_var($input['send_security_notification'], FILTER_SANITIZE_NUMBER_INT);
        }
                
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo esc_html__("Please provide your cPanel login details and click 'Save Changes' button", 'auto-install-free-ssl').".<br /><br />";
        
        $cpanel_login_url = get_site_url(). ":2083";        
        echo "<span style='color: green;'>". sprintf(__("In case you don't know your cPanel login URL, please <a href='%s' target='_blank'>click here</a> and copy it from the address bar. <a href='%s' target='_blank'>This link</a> will open in a new window and may redirect to the cPanel login page.", 'auto-install-free-ssl'), $cpanel_login_url, $cpanel_login_url)."</span> ".esc_html__("If you face issue identifying the cPanel login URL, please contact your web hosting service provider.", 'auto-install-free-ssl')."<br />";
    }
    
    
    /**
     * cpanel_host
     */
    public function cpanel_host_callback()
    {
        printf(
         '<input type="text" id="cpanel_host" name="cpanel_settings_auto_install_free_ssl[cpanel_host]" required="required" value="%s" placeholder="e.g: https://speedify.tech:2083" />',
            isset($this->options['cpanel_host']) ? 'https://'.esc_attr($this->options['cpanel_host']).':2083' : ''
         );
    }
        
    
    /**
     * username
     */
    public function username_callback()
    {
        printf(
            '<input type="text" id="username" name="cpanel_settings_auto_install_free_ssl[username]" required="required" value="%s" placeholder="username" />',
            isset($this->options['username']) ? esc_attr($this->options['username']) : ''
            );
    }
    
    
    /**
     * password
     */
    public function password_callback()
    {
        printf(
            '<input type="password" id="password" name="cpanel_settings_auto_install_free_ssl[password]" required="required" value="%s" />',
            isset($this->options['password']) ? esc_attr($this->factory->decryptText($this->options['password'])) : ''
            );
    }
    
    /**
     * confirm_password
     */
    public function confirm_password_callback()
    {
        printf(
            '<input type="password" id="confirm_password" name="cpanel_settings_auto_install_free_ssl[confirm_password]" required="required" value="%s" />',
            isset($this->options['password']) ? esc_attr($this->factory->decryptText($this->options['password'])) : ''
            );
    }
    
    /**
     * notification
     */
    public function notification_callback()
    {
        //If this form called over unsecured HTTP, set a hidden field ‘send_security_notification’ = true
        
        $send_security_notification = is_ssl() ? 0 : 1;
        
        printf(
            '<input type="hidden" id="notification" name="cpanel_settings_auto_install_free_ssl[send_security_notification]" value="%s" />',
            $send_security_notification
            );
    }
}
