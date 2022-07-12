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
 * If the admin don't need SSL cert for any domain that exists in the cPanel
 *
 */
class cPanelExcludeDomainsSettings
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
        $this->options = get_option('exclude_domains_auto_install_free_ssl') ? get_option('exclude_domains_auto_install_free_ssl') : add_option('exclude_domains_auto_install_free_ssl');
        
        // Get basic settings if exists
        $basic_settings = get_option('basic_settings_auto_install_free_ssl');
        
        //hook if the web hosting control panel is cPanel
        if (isset($basic_settings['is_cpanel']) && $basic_settings['is_cpanel']) {
            add_action('admin_menu', array( $this, 'add_exclude_domains_menu' ));
            add_action('admin_init', array( $this, 'exclude_domains_page_init' )); //This is interfaring with basic settings. But why? Changing basic_settings_ais_admin to exclude_domains_ais_admin fixed the issue. However, renaming all the parameters is a best practice.
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
        }
        
        $this->factory =  new Factory();
    }
    
    /**
     * Add the sub menu
     */
    public function add_exclude_domains_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Exclude Domains Page", 'auto-install-free-ssl'), esc_html__("Exclude Domains", 'auto-install-free-ssl'), 'manage_options', 'aifs_exclude_domains', array( $this, 'create_exclude_domains_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_exclude_domains_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
            exit;
        }
        
        echo '<div class="wrap">';
        
        echo '<h1>'.esc_html__("Exclude Domains / Sub-domains", 'auto-install-free-ssl').'</h1>';
        
        // Get cpanel settings if exists
        $cpanel_settings = get_option('cpanel_settings_auto_install_free_ssl');
        
        if(isset($cpanel_settings['cpanel_host'])){
            echo '<form method="post" action="options.php">';
            
            settings_fields('exclude_domains_ais_group');
            do_settings_sections('exclude_domains_ais_admin');
            submit_button();
            
            echo '</form>';
            
        }
        else{
            echo '<br /><h3>Please provide your cPanel login details, then you\'ll be able to exclude domains. <a href="'.menu_page_url('aifs_cpanel_settings', false).'">Click here to go to the cPanel Settings</a> page.</h3><br />';
        }
            
        
        echo '<a href="'.menu_page_url('auto_install_free_ssl', false).'" class="page-title-action button">'.esc_html__("Cancel", 'auto-install-free-ssl').'</a><br /><br />';
        
            ?>
        
        
                  
            
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
    public function exclude_domains_page_init()
    {
        register_setting(
            'exclude_domains_ais_group', // Option group
            'exclude_domains_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'exclude_domains_section_id', // Section ID
            esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'),
            array( $this, 'print_section_info' ), // Callback
            'exclude_domains_ais_admin' // Page
        );
        
        add_settings_field(
            'domains_to_exclude',
            esc_html__("Exclude Domains", 'auto-install-free-ssl'),
            array( $this, 'domains_to_exclude_callback' ),
            'exclude_domains_ais_admin',
            'exclude_domains_section_id'
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
        
        $new_input['domains_to_exclude'] = $domains_array;
                
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo esc_html__("Following domains/add-on domains or sub-domains are being hosted on your cPanel. If you want SSL certificate for all of them, please skip these settings.", 'auto-install-free-ssl')."<br /><br />";
        echo "<strong>". esc_html__("Do you have any domain that currently not pointed to this hosting? Please either delete it from the cPanel or exclude it by selecting here. Otherwise, this app will throw an error.", 'auto-install-free-ssl')."</strong>";
    }
    
    
    /**
     * domains_to_exclude
     */
    public function domains_to_exclude_callback()
    {
        // Get cpanel settings if exists
        $cpanel_settings = get_option('cpanel_settings_auto_install_free_ssl');
        
        $cpanel = new cPanel($cpanel_settings['cpanel_host'], $cpanel_settings['username'], $cpanel_settings['password']);
        
        //Fetch all domains in the cPanel
        $all_domains = $cpanel->allDomains();
        
        foreach ($all_domains as $domain) {
            ?>
                    
              <input type="checkbox" id="domains" name="exclude_domains_auto_install_free_ssl[domains][]" value="<?php echo $domain['domain']; ?>" <?php echo (isset($this->options['domains_to_exclude']) && \in_array($domain['domain'], $this->options['domains_to_exclude'], true)) ? ' checked' : null; ?> />
                      	
              <label for="domains"><?php echo $domain['domain']; ?>, <?php echo str_replace(' ', ', ', $domain['serveralias']); ?> </label><br /><br />
                      
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
