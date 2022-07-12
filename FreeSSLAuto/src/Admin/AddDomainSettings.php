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

/**
 * This plugin require domains details (if web hosting control panel is not cPanel) to issue/renew free SSL certificate.
 *
 */
class AddDomainSettings
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
        // First check with get_option if the option exists. Otherwise, add the option with add_option
        // Setting option with get_option and without this check results in missing values error for multidimentional array, for the first time entry.
        
        $this->options = get_option('all_domains_auto_install_free_ssl') ? get_option('all_domains_auto_install_free_ssl') : add_option('all_domains_auto_install_free_ssl');
        
        // Get basic settings if exists
        $basic_settings = get_option('basic_settings_auto_install_free_ssl');
        
        //hook if the web hosting control panel is not cPanel
        if (isset($basic_settings['is_cpanel']) && !$basic_settings['is_cpanel']) {
            add_action('admin_menu', array( $this, 'add_domain_menu' ));
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
            add_action('admin_init', array( $this, 'add_domain' ));
        }
                
        $this->factory =  new Factory();
        
        
        //GET request - fill the form with saved data if ID is set
        
        if (isset($_GET['id']) && isset($_GET['page']) && $_GET['page'] == "aifs_add_domain") {
            $id = absint($_GET['id']);
            
            $this->id = $id;
            
            $this->domain = $this->options['all_domains'][$id]['domain'];
            $this->serveralias = $this->options['all_domains'][$id]['serveralias'];
            $this->documentroot = $this->options['all_domains'][$id]['documentroot'];
        }
    }
    
    
    /**
     * Add the sub menu
     */
    public function add_domain_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Add Domain Page", 'auto-install-free-ssl'), esc_html__("Add New Domain", 'auto-install-free-ssl'), 'manage_options', 'aifs_add_domain', array( $this, 'create_add_domain_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_add_domain_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('aifs_domains').'&settings-updated=true', 301);
            exit;
        }
        
        
        echo '<div class="wrap">';
        
        echo '<h1>'.(isset($_GET['id']) ? esc_html__("Update", 'auto-install-free-ssl') : esc_html__("Add New", 'auto-install-free-ssl')). ' '.esc_html__("Domain", 'auto-install-free-ssl'). '</h1>';
        
        echo '<form method="post" action="options.php">';
             
        settings_fields('add_domain_aifs_group');
        do_settings_sections('add_domain_aifs_admin');
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
    public function add_domain()
    {
        register_setting(
            'add_domain_aifs_group', // Option group
            'all_domains_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'add_domain_section_id', // Section ID
            esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'),
            array( $this, 'print_section_info' ), // Callback
            'add_domain_aifs_admin' // Page
        );
        
        add_settings_field(
            'id',
            '',
            array( $this, 'id_callback' ),
            'add_domain_aifs_admin',
            'add_domain_section_id'
            );
               
        add_settings_field(
            'domain',
            __("Domain <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'domain_callback' ),
            'add_domain_aifs_admin',
            'add_domain_section_id'
            );
        
        add_settings_field(
            'serveralias',
            __("Server Alias, saperated by space <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'serveralias_callback' ),
            'add_domain_aifs_admin',
            'add_domain_section_id'
            );
        
        add_settings_field(
            'documentroot',
            __("Document Root <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'documentroot_callback' ),
            'add_domain_aifs_admin',
            'add_domain_section_id'
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
                    
        //Processing form data when form is submitted
                                                
        // SANITIZE user inputs
                                
        $domain = sanitize_text_field($input['domain']);
                
        $serveralias = sanitize_text_field($input['serveralias']);
                
        $serveralias = str_replace([',', ', ', '  ', '   ', '    '], ' ', $serveralias);
                
        $documentroot = sanitize_text_field($input['documentroot']);
                
                
        $all_domains_array = array();
                
        //push existing values too, otherwise all will be overwritten
                
        if (isset($this->options['all_domains'])) {
            foreach ($this->options['all_domains'] as $key => $all_domains_existing) {
                $all_domains_array[$key] = $all_domains_existing;
            }
        }
                
                
        if (\strlen($input['id'])) {
            $id = absint($input['id']);
                    
            $all_domains_array[$id] = [
                        'domain' => $domain,
                        'serveralias' => $serveralias,
                        'documentroot' => $documentroot,
                    ];
        } else {
            $all_domains_array[] = [
                        'domain' => $domain,
                        'serveralias' => $serveralias,
                        'documentroot' => $documentroot,
                    ];
        }
                
                    
        $new_input['all_domains'] = $all_domains_array;
                     
                   
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo esc_html__("This app needs domain details to auto-generate SSL certificate. Please provide your domain details and click 'Save Changes'", 'auto-install-free-ssl');
    }
    
    /**
     * domain
     */
    public function domain_callback()
    {
        echo '<div id="domain">';
        
        printf(
            '<input type="text" id="domain" name="all_domains_auto_install_free_ssl[domain]" value="%s" placeholder="e.g. speedupwebsite.info" required="required" />',
            isset($this->domain) ? esc_attr($this->domain) : ''
            );
        
        echo "<label>". __("It will be Common Name (CN) of the SSL", 'auto-install-free-ssl'). "</label></div>";
    }
    
    
    /**
     * serveralias
     */
    public function serveralias_callback()
    {
        echo '<div id="serveralias">';
        
        printf(
            '<textarea class="form-control" id="serveralias" name="all_domains_auto_install_free_ssl[serveralias]" required="required" rows="5" cols="70" placeholder="www.speedupwebsite.info mail.speedupwebsite.info" >%s</textarea>',
            isset($this->serveralias) ? esc_attr($this->serveralias) : ''
            );
        
        echo "<br /><label>". __("If you have multiple server alias pointing to the same document root. All of these must be accessible over HTTP. These will be Subject Alternative Name (SAN) of the SSL.", 'auto-install-free-ssl'). "</label></div>";
    }
    
    /**
     * documentroot
     */
    public function documentroot_callback()
    {
        echo '<div id="documentroot">';
        
        printf(
            '<input type="text" id="documentroot" name="all_domains_auto_install_free_ssl[documentroot]" value="%s" placeholder="/home/username/public_html" required="required" />',
            isset($this->documentroot) ? esc_attr($this->documentroot) : ''
            );
        
        echo "<label></label></div>";
    }
   
    
    /**
     * id
     */
    public function id_callback()
    {
        printf(
            '<input type="hidden" id="id" name="all_domains_auto_install_free_ssl[id]" value="%s" />',
            isset($this->id) ? esc_attr($this->id) : null
            );
    }
    
    /**
     * required for successful redirect
     */
    public function do_output_buffer()
    {
        ob_start();
    }
}
