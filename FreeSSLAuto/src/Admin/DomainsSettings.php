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
 * List domains - This plugin require domains details (if web hosting control panel is not cPanel) to issue/renew free SSL certificate.
 *
 */
class DomainsSettings
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
        
        //hook if the conditions match
        if (isset($basic_settings['is_cpanel']) && !$basic_settings['is_cpanel']) {
            add_action('admin_menu', array( $this, 'all_domains_menu' ));
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
        }
                
        $this->factory =  new Factory();
    }
    
    
    /**
     * Add the sub menu
     */
    public function all_domains_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Domains Page", 'auto-install-free-ssl'), esc_html__("Domains", 'auto-install-free-ssl'), 'manage_options', 'aifs_domains', array( $this, 'create_domains_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function create_domains_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            
            echo '<p>'.esc_html__("The Domains settings have been updated successfully", 'auto-install-free-ssl').'</p>';
            
            echo '</div>';
        }
        
        echo '<div class="wrap">';
            
        echo '<h1>'.esc_html__("Your Domains", 'auto-install-free-ssl').'</h1>';
        echo '<h3>'.esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl').'</h3>';
        
        echo sprintf('<br /><a href="%s" class="page-title-action button-primary">', menu_page_url('aifs_add_domain', false)) . esc_html__("Add New Domain", 'auto-install-free-ssl').'</a>';
            
        echo '</div><br /><br />';
            
        if (isset($this->options['all_domains']) && \count($this->options['all_domains']) > 0) {
        
        
            //Delete start
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
                $id = absint($_GET['id']);
                
                //Delete the entry which key is $id
                unset($this->options['all_domains'][$id]);
                                                
                //Use database directly
                global $wpdb;
                
                $serialized_value = maybe_serialize($this->options);
                
                $update_args = array(
                    'option_value' => $serialized_value,
                );
                
                $result = $wpdb->update($wpdb->options, $update_args, array( 'option_name' => 'all_domains_auto_install_free_ssl' ));
                
                if ($result) {
                    //Success, redirect
                    wp_redirect(menu_page_url('aifs_domains').'&settings-updated=true', 301);
                    exit;
                } else {
                    //Update failed
                    
                    echo '<div class="notice notice-error is-dismissible">';
                    
                    echo '<p>'.esc_html__("Oops! We are unable to delete the domain entry. Please try again later.", 'auto-install-free-ssl').'</p>';
                    
                    echo '</div>';
                }
            }
            //Delete end ?>
                
        <!-- List all DNS service providers -->
        <table class="wp-list-table widefat fixed striped pages">
        <thead>
        <tr>
            <th><?= esc_html__("Domain", 'auto-install-free-ssl') ?> (CN)</th>
            <th></th>
            <th><?= esc_html__("Server Alias", 'auto-install-free-ssl') ?> (SAN)</th>
            <th><?= esc_html__("Document Root", 'auto-install-free-ssl') ?></th>
        </tr>
        </thead>
        
        <tbody id="the-list">
        <?php foreach ($this->options['all_domains'] as $key => $domain) {
                ?>
        
			<tr id="post-3" class="iedit author-self level-0 post-3 type-page status-draft hentry">
				<td><?php echo $domain['domain']; ?></td>
    			<td>                 
                        <a href="<?php menu_page_url('aifs_add_domain'); ?>&id=<?php echo $key; ?>"><?= esc_html__("Edit", 'auto-install-free-ssl') ?></a> | 
                        <a href="<?php menu_page_url('aifs_domains'); ?>&id=<?php echo $key; ?>&action=delete" onclick="return confirm('<?= esc_html__("Do you really want to DELETE the domain?", 'auto-install-free-ssl') ?>');"><?= esc_html__("Delete", 'auto-install-free-ssl') ?></a>
                      
                </td>
    			<td><?php echo $domain['serveralias']; ?></td>
    			<td><?php echo $domain['documentroot']; ?></td>
			</tr>
        <?php
            } ?>			
		</tbody>
        
        </table>
        
        <?php
        } else {
            //no record
            
            echo '<div class="notice notice-error is-dismissible">';
            
            echo '<p>'.sprintf(__('You don\'t have any Domain. Please <a href="%s">add domain</a> to auto-generate free SSL certificates.', 'auto-install-free-ssl'), menu_page_url('aifs_add_domain', false)).'</p>';
            
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
