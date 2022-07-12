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
use AutoInstallFreeSSL\FreeSSLAuto\FreeSSLAuto;

/**
 * Revoke SSL certificate
 *
 */
class RevokeSslCert
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    
    public $factory;
    
    public $acmeFactory;
    
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
        
        $this->factory =  new Factory();
                
        // Get app settings if exists
        $app_settings = aifs_get_app_settings();
        
        if(isset($app_settings['homedir'])){
            
            //initialize the Acme Factory class
            $this->acmeFactory = new AcmeFactory($app_settings['homedir'].'/'.$app_settings['certificate_directory'], $app_settings['acme_version'], $app_settings['is_staging']);
            
            //get the path of SSL files
            $certificates_directory = $this->acmeFactory->getCertificatesDir();
            
            if(is_dir($certificates_directory)){
            
                //get the domains for which SSL is present in the certificate directory
                $this->all_domains = $this->factory->getExistingSslList($certificates_directory);
                
                //hook if any SSL cert exists
                if (count($this->all_domains) > 0) {
                    add_action('admin_menu', array( $this, 'revoke_ssl_certificate_menu' ));
                    add_action('admin_init', array( $this, 'revoke_ssl_certificate_page_init' )); //This is interfaring with basic settings. But why? Changing basic_settings_ais_admin to revoke_ssl_certificate_aifs_admin fixed the issue. However, renaming all the parameters is a best practice.
                    add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
                }
            }        
        }
        
        $this->revoke_button_text = esc_html__("Revoke SSL", 'auto-install-free-ssl');
    }
    
    /**
     * Add the sub menu
     */
    public function revoke_ssl_certificate_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Revoke SSL Cert Page", 'auto-install-free-ssl'), esc_html__("Revoke SSL Cert", 'auto-install-free-ssl'), 'manage_options', 'aifs_revoke_ssl_certificate', array( $this, 'revoke_ssl_certificate_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function revoke_ssl_certificate_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
            exit;
        }
        
        echo '<div class="wrap">';
        
        echo '<h1>'.esc_html__("Revoke SSL Certificate", 'auto-install-free-ssl').'</h1>';
        
        
        $display_form = true;
            
        //Processing form data when form is submitted
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (isset($_POST['revoke_ssl_certificate_auto_install_free_ssl'])) {
                    
                    // SANITIZE user inputs
                $domains_array = [];
                    
                foreach ($_POST['revoke_ssl_certificate_auto_install_free_ssl']['domains'] as $domain) {
                    $domains_array[] = sanitize_text_field($domain);
                }
                    
                $app_settings = aifs_get_app_settings();
                    
                $app_settings['domains_to_revoke_cert'] = $domains_array;
                    
                echo esc_html__("First step was executed successfully.", 'auto-install-free-ssl'). '<br /><br />';
                    
                    
                //define AIFS_REVOKE_CERT true
                \define('AIFS_REVOKE_CERT', true);
                //other constants should be false
                \define('AIFS_KEY_CHANGE', false);
                \define('AIFS_ISSUE_SSL', false);
                    
                $freeSsl = new FreeSSLAuto($app_settings);
                    
                //Run the App
                $freeSsl->run();
                    
                $display_form = false;
            } else {
                //No domain selected
                    
                $error_message = esc_html__("You haven't selected any domain", 'auto-install-free-ssl');
                    
                $error_message = <<<ERROR
            <div class="notice notice-error is-dismissible">
              	<p>
              	 ${error_message}
              	</p>
          	</div>
ERROR;
                echo $error_message;
            }
        }
            
        if ($display_form) {
            echo '<form method="post" action="">';
                    
            settings_fields('revoke_ssl_certificate_aifs_group');
            do_settings_sections('revoke_ssl_certificate_aifs_admin');
            submit_button($this->revoke_button_text);
            echo '<a href="'.menu_page_url('auto_install_free_ssl', false).'" class="page-title-action button">'.esc_html__("Cancel", 'auto-install-free-ssl').'</a>';
                    
            echo '</form>';
        } ?>
            <br />
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
    public function revoke_ssl_certificate_page_init()
    {
        register_setting(
            'revoke_ssl_certificate_aifs_group', // Option group
            'revoke_ssl_certificate_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'revoke_ssl_certificate_section_id', // Section ID
            esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'),
            array( $this, 'print_section_info' ), // Callback
            'revoke_ssl_certificate_aifs_admin' // Page
        );
        
        add_settings_field(
            'domains',
            esc_html__("Revoke SSL Cert", 'auto-install-free-ssl'),
            array( $this, 'domains_callback' ),
            'revoke_ssl_certificate_aifs_admin',
            'revoke_ssl_certificate_section_id'
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
        
        $domains_array = [];
                
        if (isset($input['domains'])) {
            foreach ($input['domains'] as $domain) {
                $domains_array[] = sanitize_text_field($domain);
            }
        }
        
        $new_input['domains'] = $domains_array;
                
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo '<h4>'. sprintf(esc_html__("Please select the domains/sub-domains for which you want to revoke SSL certificate and click '%s' button.", 'auto-install-free-ssl'), $this->revoke_button_text).'</h4>';
        
        echo sprintf(esc_html__("This app issued SSL certificate for the following domains/sub-domains. If you want to revoke any of these SSL certificates, please select and click '%s' button.", 'auto-install-free-ssl'), $this->revoke_button_text);
    }
    
    
    /**
     * domains
     */
    public function domains_callback()
    {
        foreach ($this->all_domains as $domain) {
            ?>
                    
              <input type="checkbox" id="domains" name="revoke_ssl_certificate_auto_install_free_ssl[domains][]" value="<?php echo $domain; ?>" <?php echo (isset($this->domains_array) && \in_array($domain, $this->domains_array, true)) ? ' checked' : null; ?> />

              <label for="domains"><?php echo $domain; ?></label><br /><br />
                      
        <?php
        }
    }
    
    /**
     * required for successful redirect
     */
    public function do_output_buffer()
    {
        ob_start();
    }
}
