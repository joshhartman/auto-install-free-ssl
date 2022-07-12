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
class DnsServiceProvidersSettings
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
        if (isset($basic_settings['acme_version']) && $basic_settings['acme_version'] === 2 && $basic_settings['use_wildcard']) {
            add_action('admin_menu', array( $this, 'dns_service_providers_menu' ));
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
        }
        
        // Get cPanel settings if exists
        $this->cpanel_settings = get_option('cpanel_settings_auto_install_free_ssl');
        
        $this->factory =  new Factory();
    }
    
    
    /**
     * Add the sub menu
     */
    public function dns_service_providers_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("DNS Service Providers Page", 'auto-install-free-ssl'), esc_html__("DNS Service Providers", 'auto-install-free-ssl'), 'manage_options', 'aifs_dns_service_providers', array( $this, 'create_dns_service_providers_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_dns_service_providers_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            
            global $wp_version;
            
            $version_parts = explode(".", $wp_version);
            
            $version_base = (int) $version_parts[0];
            
            if($version_base === 5){
                $style = 'class="notice notice-success is-dismissible"';
            }
            else{
                $style = 'id="message" class="updated below-h2"';
            }
            
            echo '<div '.$style.'><p>';
                        
            echo esc_html__("The DNS Service Provider settings have been updated successfully!", 'auto-install-free-ssl');
            
            echo '</p></div>';
        }
            
        echo '<div class="wrap">';
            
        echo '<h1>'.esc_html__("DNS Service Providers", 'auto-install-free-ssl').'</h1>';
        echo '<h3>'.esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl').'</h3>';
            
        echo '<p><strong>'.esc_html__("DNS Service Provider details required only if you want to issue Wildcard SSL certificate.", 'auto-install-free-ssl').'</strong> ';
        echo esc_html__("You may add multiple DNS Service Providers, if applicable.", 'auto-install-free-ssl').'</p><br />';
            
        echo sprintf('<a href="%s" class="page-title-action button-primary">', menu_page_url('aifs_add_dns_service_provider', false)) .esc_html__("Add New DNS Service Provider", 'auto-install-free-ssl') . '</a>';
            
        echo '</div><br /><br />';
            
        if (isset($this->options['dns_provider']) && \count($this->options['dns_provider']) > 0) {
                
            //Delete start
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
                $id = absint($_GET['id']);
                
                //Delete the entry which key is $id
                unset($this->options['dns_provider'][$id]);
                
                
                //'update_option' wp function calling 'sanitize' of AddDnsServiceProviderSettings,
                // which is NOT appropriate here and causing issues.
                                
                //if(update_option( 'dns_provider_auto_install_free_ssl', $this->options )){
                
                //So, use database directly
                global $wpdb;
                
                $serialized_value = maybe_serialize($this->options);
                
                $update_args = array(
                    'option_value' => $serialized_value,
                );
                
                $result = $wpdb->update($wpdb->options, $update_args, array( 'option_name' => 'dns_provider_auto_install_free_ssl' ));
                
                if ($result) {
                    //Success, redirect
                    wp_redirect(menu_page_url('aifs_dns_service_providers').'&settings-updated=true', 301);
                    exit;
                } else {
                    
                    //Update failed
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p>'.esc_html__("Oops! We are unable to delete the DNS Service Provider entry. Please try again later.", 'auto-install-free-ssl').'</p>';
                    echo '</div>';
                }
            }
            //Delete end ?>
                
        <!-- List all DNS service providers -->
        <table class="wp-list-table widefat fixed striped pages">
        <thead>
        <tr>
            <th><?= esc_html__("DNS Service Provider", 'auto-install-free-ssl') ?></th>
            <th></th>
            <th><?= esc_html__("Domain Names", 'auto-install-free-ssl') ?></th>
            <th><?= esc_html__("API Identifier", 'auto-install-free-ssl') ?></th>
        </tr>
        </thead>
        
        <tbody id="the-list">
        <?php foreach ($this->options['dns_provider'] as $key => $provider) {
                ?>
        
			<tr id="post-3" class="iedit author-self level-0 post-3 type-page status-draft hentry">
				<td><?php echo (false === $provider['name']) ? 'Others' : $provider['name']; ?></td>
    			<td>                 
                        <a href="<?php menu_page_url('aifs_add_dns_service_provider'); ?>&id=<?php echo $key; ?>"><?= esc_html__("Edit", 'auto-install-free-ssl') ?></a> | 
                        <a href="<?php menu_page_url('aifs_dns_service_providers'); ?>&id=<?php echo $key; ?>&action=delete" onclick="return confirm('<?= esc_html__("Do you really want to DELETE the DNS Service Provider", 'auto-install-free-ssl') ?>?');"><?= esc_html__("Delete", 'auto-install-free-ssl') ?></a>
                      
                </td>
    			<td><?php echo implode(', ', $provider['domains']); ?></td>
    			<td><?php echo (false === $provider['name']) ? null : ('cPanel' === $provider['name'] ? 'https://'.$this->cpanel_settings['cpanel_host'].':2083 <br />username: '.$this->cpanel_settings['username'] : $provider['api_identifier']); ?></td>
			</tr>
        <?php
            } ?>			
		</tbody>
        
        </table>
        
        <?php
        } else {
            //no record
            
            echo '<div class="notice notice-error is-dismissible">';
            
            echo '<p>'.sprintf(__('You don\'t have any DNS Service Provider entry. Please <a href="%s">add DNS Service Provider</a> to issue wildcard SSL certificates.', 'auto-install-free-ssl'), menu_page_url('aifs_add_dns_service_provider', false)).'</p>';
            
            echo '</div>';
        } ?>
                
        <br /><br />
        
        <?php echo '<a href="'.menu_page_url('auto_install_free_ssl', false).'" class="page-title-action button">'.esc_html__("Go Back", 'auto-install-free-ssl').'</a>'; ?>
        
        <br /><br />
        
        <!-- Powered by -->
            <br />
            <div class="header-footer">
              	<p>             
              		<?php echo esc_html__("Need help", 'auto-install-free-ssl'); ?>? <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#help" target="_blank">Click here!</a> <span style="margin-left: 15%;"><?php echo esc_html__("For documentation", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#documentation" target="_blank">click here</a>.</span>
              	</p>          	
          	</div> <!-- End Powered by -->
                
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
