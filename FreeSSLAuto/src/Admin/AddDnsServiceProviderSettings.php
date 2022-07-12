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
 * This plugin require DNS service provider details to set DNS TXT record.
 * This is applicable for wildcard SSL only.
 *
 */
class AddDnsServiceProviderSettings
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
        
        $this->options = get_option('dns_provider_auto_install_free_ssl') ? get_option('dns_provider_auto_install_free_ssl') : add_option('dns_provider_auto_install_free_ssl');
        
        // Get basic settings if exists
        $basic_settings = get_option('basic_settings_auto_install_free_ssl');
        
        //hook if the ACME version is 2 and if wildcard SSL required
        if (isset($basic_settings['acme_version']) && $basic_settings['acme_version'] == 2 && isset($basic_settings['use_wildcard']) && $basic_settings['use_wildcard']) {
            add_action('admin_menu', array( $this, 'add_dns_service_provider_menu' ));
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
            add_action('admin_init', array( $this, 'add_dns_service_provider' ));
        }
        
        $this->factory =  new Factory();
        
        
        //GET request
        
        if (isset($_GET['id']) && isset($_GET['page']) && $_GET['page'] == "aifs_add_dns_service_provider") {
            $id = (int) filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
            
            $this->id = $id;
            
            $this->name = $this->options['dns_provider'][$id]['name'];
            
            if (false !== $this->name) {
                if ('cPanel' !== $this->name) {
                    $this->api_identifier = $this->options['dns_provider'][$id]['api_identifier'];
                    $this->api_credential = $this->factory->decryptText($this->options['dns_provider'][$id]['api_credential']);
                }
                
                $this->dns_provider_takes_longer_to_propagate = $this->options['dns_provider'][$id]['dns_provider_takes_longer_to_propagate'];
            }
            
            $this->domains = implode(', ', $this->options['dns_provider'][$id]['domains']);
        }
    }
    
    
    /**
     * Add the sub menu
     */
    public function add_dns_service_provider_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Add DNS Service Provider Page", 'auto-install-free-ssl'), esc_html__("Add New DNS Service Provider", 'auto-install-free-ssl'), 'manage_options', 'aifs_add_dns_service_provider', array( $this, 'create_add_dns_service_provider_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_add_dns_service_provider_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('aifs_dns_service_providers').'&settings-updated=true', 301);
            exit;
        }
        
        echo '<div class="wrap">';
        
        echo '<h1>'.(isset($_GET['id']) ? esc_html__("Update", 'auto-install-free-ssl') : esc_html__("Add New", 'auto-install-free-ssl')) . ' ' . esc_html__("DNS Service Provider", 'auto-install-free-ssl') . '</h1>';
        
        if ($_SERVER['SERVER_PORT'] != 443) {
            
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
                                
            echo '<p>'. sprintf(__('This page is NOT protected with HTTPS and you are going to provide your cPanel credentials over an unencrypted connection. We recommend generating a <a href="%s" target="_blank">Free SSL Certificate for <strong>%s</strong> with a single click</a>, installing it on this server and then continue with this page.<br /><br />
                      	You need approx 15 seconds to generate this free SSL. <a href="%s" target="_blank">Please click here!</a>', 'auto-install-free-ssl'), menu_page_url('aifs_temporary_ssl', false), $_SERVER['SERVER_NAME'], menu_page_url('aifs_temporary_ssl', false)) .'</p>';
                    
            echo '</div><br />';
        }
                
        echo '<form method="post" action="options.php">';
            
        settings_fields('add_dns_service_provider_aifs_group');
        do_settings_sections('add_dns_service_provider_aifs_admin');
        submit_button();
        echo '<a href="#" class="page-title-action button" onclick="window.history.go(-1); return false;">'.esc_html__("Cancel", 'auto-install-free-ssl').'</a>';
                
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
    public function add_dns_service_provider()
    {
        register_setting(
            'add_dns_service_provider_aifs_group', // Option group
            'dns_provider_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'add_dns_service_provider_section_id', // Section ID
            esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'), //
            array( $this, 'print_section_info' ), // Callback
            'add_dns_service_provider_aifs_admin' // Page
        );
        
        add_settings_field(
            'id',
            '',
            array( $this, 'id_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
        
        add_settings_field(
            'name',
            __("Name of the DNS Service Provider <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'name_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
                       
        add_settings_field(
            'api_identifier',
            __("API Identifier <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'api_identifier_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
        
        add_settings_field(
            'api_credential',
            __("API Credential <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'api_credential_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
        
        add_settings_field(
            'confirm_api_credential',
            __("Confirm API Credential <sup>(required)</sup>", 'auto-install-free-ssl'),
            array( $this, 'confirm_api_credential_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
        
        add_settings_field(
            'dns_provider_takes_longer_to_propagate',
            __("Does this DNS Service Provider take longer than 2 minutes to propagate?", 'auto-install-free-ssl'),
            array( $this, 'dns_provider_takes_longer_to_propagate_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
            );
        
        add_settings_field(
            'domains',
            __("Domain names managed by this DNS Service Provider", 'auto-install-free-ssl'),
            array( $this, 'domains_callback' ),
            'add_dns_service_provider_aifs_admin',
            'add_dns_service_provider_section_id'
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
                    
        //Process form data when form is submitted
                                                
        // SANITIZE user inputs
                                
        $name = sanitize_text_field($input['name']);
        $name = ('0' == $name) ? false : $name;
                
        $domains = str_replace(' ', '', sanitize_text_field($input['domains']));
                
        $dns_provider_takes_longer_to_propagate = (bool) filter_var($input['dns_provider_takes_longer_to_propagate'], FILTER_SANITIZE_NUMBER_INT);
                                
        //Validate api_identifier and api_credential if $name !== false
                
        if ($name !== false) {
            if ($name !== 'cPanel') {
                        
                        //DNS provider is NOT cPanel
                $api_identifier = sanitize_text_field($input['api_identifier']);
                        
                $api_credential = sanitize_text_field($input['api_credential']);
                        
                $confirm_api_credential = sanitize_text_field($input['confirm_api_credential']);
                        
                $dns_provider = [
                            'name' => $name,
                            'api_identifier' => $api_identifier,
                            'api_credential' => $this->factory->encryptText($api_credential),
                            'dns_provider_takes_longer_to_propagate' => $dns_provider_takes_longer_to_propagate,
                            'domains' => explode(',', $domains),
                        ];
            } else {
                //DNS service provider is cPanel
                $dns_provider = [
                            'name' => $name,
                            'dns_provider_takes_longer_to_propagate' => $dns_provider_takes_longer_to_propagate,
                            'domains' => explode(',', $domains),
                        ];
            }
        } else {
            $dns_provider = [
                        'name' => $name,
                        'dns_provider_takes_longer_to_propagate' => true,
                        'domains' => explode(',', $domains),
                    ];
        }
                
        $dns_provider_array = array();
                
        //push existing values too, otherwise all will be overwritten
                
        if (isset($this->options['dns_provider'])) {
            foreach ($this->options['dns_provider'] as $key => $dns_provider_existing) {
                $dns_provider_array[$key] = $dns_provider_existing;
            }
        }
                                
        //Save only if there is NO error
                
        if (\strlen($input['id'])) {
            $id = absint($input['id']);
            $dns_provider_array[$id] = $dns_provider;
        } else {
            $dns_provider_array[] = $dns_provider;
        }
                    
        $new_input['dns_provider'] = $dns_provider_array;
                    
                   
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo esc_html__("Please provide your DNS Service Provider details and click 'Save Changes'", 'auto-install-free-ssl').'.<br /><br />';
        
        echo esc_html__("This app needs your DNS Service Provider details to set DNS TXT record automatically.", 'auto-install-free-ssl');
        
        echo ' <strong>'.esc_html__("This step is mandatory to verify your domains to issue wildcard SSL.", 'auto-install-free-ssl').'</strong> ';
        
        echo esc_html__("Supported DNS API: cPanel, GoDaddy, Namecheap, and Cloudflare.", 'auto-install-free-ssl'). '<br /><br />';
        
        echo esc_html__("If your DNS Service provider is other than cPanel, GoDaddy, Namecheap and Cloudflare, you may skip making the entry here. In that case, this app will send you an automated email with DNS TXT record details, and you need to set it manually.", 'auto-install-free-ssl');
    }
    
    
    /**
     * name
     */
    public function name_callback()
    {
        ?>
        
        <select name="dns_provider_auto_install_free_ssl[name]" id="name" required="required" >
		  <option<?php echo (isset($this->name) && 'cPanel' === $this->name) ? ' selected' : null; ?> value="cPanel">cPanel</option>
          <option<?php echo (isset($this->name) && 'GoDaddy' === $this->name) ? ' selected' : null; ?> value="GoDaddy">GoDaddy</option>
          <option<?php echo (isset($this->name) && 'Namecheap' === $this->name) ? ' selected' : null; ?> value="Namecheap">Namecheap</option>
          <option<?php echo (isset($this->name) && 'Cloudflare' === $this->name) ? ' selected' : null; ?> value="Cloudflare">Cloudflare</option>
          <option<?php echo (isset($this->name) && false === $this->name) ? ' selected' : null; ?> value="0">Others</option>
       </select>
        
        <?php
    }
        
    
    /**
     * api_identifier
     */
    public function api_identifier_callback()
    {
        echo '<div id="api_cpanel">'.esc_html__("You don't need to provide API Identifier and API Credential for cPanel. We'll get these from your cPanel settings.", 'auto-install-free-ssl').'</div>';
        
        echo '<div id="api_others">'.esc_html__("You don't need to provide API Identifier and API Credential for others.", 'auto-install-free-ssl').'</div>';
        
        echo '<div id="api_identifier">';
        
        printf(
            '<input type="text" id="api_identifier" name="dns_provider_auto_install_free_ssl[api_identifier]" value="%s" placeholder="" />',
            isset($this->api_identifier) ? esc_attr($this->api_identifier) : ''
            );
        
        echo "<label>".esc_html__("API key or API email or API user name", 'auto-install-free-ssl')."</label></div>";
    }
    
    
    /**
     * api_credential
     */
    public function api_credential_callback()
    {
        echo '<div id="api_credential">';
        
        printf(
            '<input type="password" id="api_credential" name="dns_provider_auto_install_free_ssl[api_credential]" value="%s" />',
            isset($this->api_credential) ? esc_attr($this->api_credential) : ''
            );
        
        echo "<label>".esc_html__("API secret. Or key, if API Identifier is an email id.", 'auto-install-free-ssl')."</label></div>";
    }
    
    /**
     * confirm_api_credential
     */
    public function confirm_api_credential_callback()
    {
        echo '<div id="confirm_api_credential">';
        
        printf(
            '<input type="password" id="confirm_api_credential" name="dns_provider_auto_install_free_ssl[confirm_api_credential]" value="%s" />',
            isset($this->api_credential) ? esc_attr($this->api_credential) : ''
            );
        
        echo "<label>".esc_html__("Retype API secret or key, if API Identifier is an email id.", 'auto-install-free-ssl')."</label></div>";
    }
    
    /**
     * dns_provider_takes_longer_to_propagate
     */
    public function dns_provider_takes_longer_to_propagate_callback()
    {
        ?>
        
        <select name="dns_provider_auto_install_free_ssl[dns_provider_takes_longer_to_propagate]" id="dns_provider_takes_longer_to_propagate">
          <option value="0"<?php echo (isset($this->dns_provider_takes_longer_to_propagate) && false === $this->dns_provider_takes_longer_to_propagate) ? ' selected' : null; ?>>No</option>
          <option value="1"<?php echo (isset($this->dns_provider_takes_longer_to_propagate) && true === $this->dns_provider_takes_longer_to_propagate) ? ' selected' : null; ?>>Yes</option>
        </select>
        
        <?php
        
        echo "<label>".esc_html__("By default this app waits 2 minutes before an attempt to verify DNS-01 challenge. But if your DNS Service provider takes more time to propagate out, set this Yes.", 'auto-install-free-ssl')."<br />";
        
        echo esc_html__("Please keep in mind, depending on the propagation status of the DNS TXT record, this settings may put the app waiting for hours.", 'auto-install-free-ssl')."</label>";
    }
    
    /**
     * domains
     */
    public function domains_callback()
    {
        printf(
            '<input type="text" id="domains" name="dns_provider_auto_install_free_ssl[domains]" required="required" value="%s" placeholder="" />',
            isset($this->domains) ? esc_attr($this->domains) : ''
            );
        
        echo "<label>".esc_html__("Separated by comma. Don't include 'www.' or any sub-domain", 'auto-install-free-ssl')."</label>";
    }
    
    /**
     * id
     */
    public function id_callback()
    {
        printf(
            '<input type="hidden" id="id" name="dns_provider_auto_install_free_ssl[id]" value="%s" />',
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
