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
 * Temporary free SSL certificate (without installation)
 *
 */
class TmpSSL
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
        
        if(!is_ssl())
            add_action('admin_menu', array( $this, 'issue_tmp_ssl_menu' ));
        
        /* if(isset($app_settings['homedir'])){
             //hook
            add_action('admin_menu', array( $this, 'issue_tmp_ssl_menu' ));
            
            //initialize the Acme Factory class
            $this->acmeFactory = new AcmeFactory($app_settings['homedir'].'/'.$app_settings['certificate_directory'], $app_settings['acme_version'], $app_settings['is_staging']);
            
            //get the path of SSL files
            $certificates_directory = $this->acmeFactory->getCertificatesDir();
            
            if(is_dir($certificates_directory)){
             
                //get the domains for which SSL is present in the directory
                $this->all_domains = $this->factory->getExistingSslList($certificates_directory);
                
                //remove the hook if there is SSL issued
                if (count($this->all_domains) > 0) {                    
                    remove_action('admin_menu', array( $this, 'issue_tmp_ssl_menu' ));
                }
            }
        } */
    }
    
    
    /**
     * Add the sub menu
     */
    public function issue_tmp_ssl_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Issue Temporary SSL Page", 'auto-install-free-ssl'), esc_html__("Issue Temporary SSL", 'auto-install-free-ssl'), 'manage_options', 'aifs_temporary_ssl', array( $this, 'issue_tmp_ssl_admin_page' ));
    }
       
    
    /**
     * Options page callback
     */
    public function issue_tmp_ssl_admin_page()
    {
        
        if (strpos($_SERVER['SERVER_NAME'], 'www.') === false || strpos($_SERVER['SERVER_NAME'], 'www.') != 0) {//No www. found at beginning
            $domain_with_www = 'www.'.$_SERVER['SERVER_NAME'];
            $domain = $_SERVER['SERVER_NAME'];
        }
        elseif(strpos($_SERVER['SERVER_NAME'], 'www.') == 0) {// www. found at the beginning
            
            $domain_with_www = $_SERVER['SERVER_NAME'];
            $domain = substr($_SERVER['SERVER_NAME'], 4);
        }
                  
        echo '<div class="wrap">';
            
        echo '<h1>'. sprintf(esc_html__("Issue free SSL for %s. Iinstall this SSL manually, for this time only.", 'auto-install-free-ssl'), $domain) . '</h1>';
            
        echo '<br />';
        
        //define AIFS_ISSUE_SSL true
        define('AIFS_ISSUE_SSL', true);
        //other constants should be false
        define('AIFS_KEY_CHANGE', false);
        define('AIFS_REVOKE_CERT', false);
                
        $factory = new Factory();
                
        //Get current user details
        global $current_user;
        get_currentuserinfo();
                
        $admin_email = [];
                
        $admin_email[] = $current_user->user_email;
                
        $appConfigTmp = [
                    //Acme version
                    //@value integer
                    'acme_version' => 2,
                    
                    //Don't use wildcard SSL
                    //@value boolean
                    'use_wildcard' => false,
                    
                    //We need real SSL
                    //@value boolean
                    'is_staging' => false,
                    
                    //Admin email
                    //@value array
                    'admin_email' => $admin_email,
                    
                    //Country code of the admin
                    //2 DIGIT ISO code
                    //@value string
                    'country_code' => '',
                    
                    //State of the admin
                    //@value string
                    'state' => '',
                    
                    //Organization of the admin
                    //@value string
                    'organization' => '',
                    
                    //Home directory of this server or WP root directory without / at the end.
                    //@value string
                    'homedir' => isset($_SERVER['HOME']) ? $_SERVER['HOME'] : substr(ABSPATH, 0, -1),
                    
                    //Certificate directory
                    //@value string
                    'certificate_directory' => 'tmp-cert',
                    
                    //How many days before the expiry date you want to renew the SSL?
                    //@value numeric
                    'days_before_expiry_to_renew_ssl' => 30,
                    
                    //Is your web hosting control panel cPanel? For this case we set it to false
                    //@value boolean
                    'is_cpanel' => false,
                    
                    //Are you using cloudflare or any other CDN?
                    //@value boolean
                    'using_cdn' => true,
                    
                    //Key size of the SSL
                    //@value integer
                    'key_size' => 2048,
                    
                    'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null,
                    
                    //Set this domain details below
                    //@value array
                    'all_domains' => [
                        [
                            'domain' => $domain,
                            'serveralias' => $serveralias,
                            'documentroot' => $_SERVER['DOCUMENT_ROOT'],
                        ],
                    ],
                    
                    // Exclution list
                    //@value array
                    'domains_to_exclude' => [],
                    
                    /* DNS provider details - required only if you want to issue Wildcard SSL.
                     *
                     * Please remember to set 'acme_version' => 2 and 'use_wildcard' => true as well
                     */
                    //@value array
                    'dns_provider' => [
                        [
                            'name' => false, //Supported providers are GoDaddy, Namecheap, Cloudflare (please write as is)
                            //Write false if your DNS provider if not supported. In that case, you'll need to add the DNS TXT record manually. You'll receive the TXT record details by automated email. PLEASE NOTE THAT in such case you must set 'dns_provider_takes_longer_to_propagate' => true  //@value string or boolean
                            'api_identifier' => '', //API Key or email id or user name   //@value string
                            'api_credential' => '', //API secret. Or key, if api_identifier is an email id   //@value string
                            'dns_provider_takes_longer_to_propagate' => true, //By default this app waits 2 minutes before attempt to verify DNS-01 challenge. But if your DNS provider takes more time to propagate out, set this true. Please keep in mind, depending on the propagation status of your DNS server, this settings may put the app waiting for hours.  //@value boolean
                            'domains' => [], //Domains registered with this DNS provider   //@value array
                            'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null
                        ],
                    ],
                ];
            
        $freeSsl = new FreeSSLAuto($appConfigTmp);
                
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
            echo '</div><br /><br />';
    }
}
