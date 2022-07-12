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
 * Change Let's Encrypt Account Key
 *
 */
class ChangeLeKey
{
    
    
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
                
                //hook if any SSL certificate exists
                if (count($this->all_domains) > 0) {
                    add_action('admin_menu', array( $this, 'change_le_account_key_menu' ));
               }            
           }        
        }
    }
    
    
    /**
     * Add the sub menu
     */
    public function change_le_account_key_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Change Let's Encrypt Account Key Page", 'auto-install-free-ssl'), esc_html__("Change Let's Encrypt Account Key", 'auto-install-free-ssl'), 'manage_options', 'aifs_change_le_account_key', array( $this, 'change_le_account_key_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function change_le_account_key_page()
    {
        echo '<div class="wrap">';
       
        echo '<h1>'.esc_html__("Change Let's Encrypt Account Key", 'auto-install-free-ssl').'</h1>';
       
        echo '<br /><br />';
        
        //define AIFS_KEY_CHANGE true
        define('AIFS_KEY_CHANGE', true);
        //other constants should be false
        \define('AIFS_ISSUE_SSL', false);
        \define('AIFS_REVOKE_CERT', false);
            
        $app_settings = aifs_get_app_settings();
            
        $freeSsl = new FreeSSLAuto($app_settings);
            
        //Run the App
        $freeSsl->run(); ?>
            
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
}
