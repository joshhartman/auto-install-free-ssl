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

/**
 * Issue and install free SSL certificate
 *
 */
class IssueFreeSSL
{
    
    
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
        
        // Get app settings
        $app_settings = aifs_get_app_settings();
        
        //hook if the admin doesn't need wildcard SSL
        if ((isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) && isset($app_settings['use_wildcard']) && !$app_settings['use_wildcard']) {
            add_action('admin_menu', array( $this, 'issue_free_ssl_menu' ));
        }
    }
    
    
    /**
     * Add the sub menu
     */
    public function issue_free_ssl_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Issue Free SSL Page", 'auto-install-free-ssl'), esc_html__("Issue &amp; install Free SSL", 'auto-install-free-ssl'), 'manage_options', 'aifs_issue_free_ssl', array( $this, 'issue_free_ssl_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function issue_free_ssl_admin_page()
    {
        echo '<div class="wrap">';
        
        echo '<h1>'. esc_html__("Issue and install Free SSL certificate", 'auto-install-free-ssl').'</h1>';
        
        echo '<br />';
        
        echo '<div style="background-color: #000000; color: #ffffff; padding: 2%; margin-left: 2%; margin-right: 10%; height: 410px; overflow-y: scroll;">';
        
        require_once __DIR__.'/../../..'.DS.'cron.php'; 
        
        echo '</div>';
         ?>                
        
            <!-- Powered by -->
            <br />
            <div class="header-footer-issue-ssl">
              	<p>             
              		<?php echo esc_html__("Need help", 'auto-install-free-ssl'); ?>? <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#help" target="_blank">Click here!</a> <span style="margin-left: 15%;"><?php echo esc_html__("For documentation", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#documentation" target="_blank">click here</a>.</span>
              	</p>          	
          	</div> <!-- End Powered by -->
          	          
        <?php
        echo '</div>';
    }
}
