<?php
/**
 * @package Auto-Install Free SSL
 * 
 * Plugin Name: Auto-Install Free SSL
 * Plugin URI:  https://freessl.tech
 * Description: This plugin automatically issues and installs free SSL certificates in cPanel shared hosting. You need only a few clicks to set it up. Cost $000
 * Version:     2.2.3
 * Author:      Free SSL Dot Tech
 * Author URI:  https://freessl.tech
 * License:     GNU General Public License, version 3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: auto-install-free-ssl
 * Domain Path: /languages/
 * Network:     true
 * Tags:        free ssl certificate,lets encrypt,ssl,https,free ssl,ssl certificate,force ssl,mixed content,insecure content
 * 
 * @author      Free SSL Dot Tech
 * @category    Plugin
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3 or higher
 * 
 * @copyright  Copyright (C) 2019-2022, Anindya Sundar Mandal - anindya@SpeedUpWebsite.info
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


/* Deny direct access */
if (! defined('ABSPATH')) {
    die('Nothing with direct access!');
}


/* Check if the OS is windows, die otherwise */
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    wp_die(__("Unfortunately, this app is not compatible with Windows. It works on Linux hosting.", 'auto-install-free-ssl'));
}

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
    wp_die(__("You need at least PHP 5.4.0\n", 'auto-install-free-ssl'));
}

if (!extension_loaded('openssl')) {
    wp_die(__("You need OpenSSL extension enabled with PHP\n", 'auto-install-free-ssl'));
}

if (!extension_loaded('curl')) {
    wp_die(__("You need Curl extension enabled with PHP\n", 'auto-install-free-ssl'));
}

if (!ini_get('allow_url_fopen')) {
    wp_die(__("You need to set PHP directive allow_url_fopen = On. Please contact your web hosting company for help.", 'auto-install-free-ssl'));
}

// Define Directory Separator to make the default DIRECTORY_SEPARATOR short
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');
$plugin_data = get_plugin_data(__FILE__);

define( 'AIFS_VERSION', $plugin_data['Version'] );
define( 'AIFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIFS_URL', plugin_dir_url( __FILE__ ) );
define( 'AIFS_NAME', $plugin_data['Name'] );
define( 'AIFS_DEFAULT_LE_ACME_VERSION', 2);

if(file_exists(__DIR__.DS.'aifs-config.php')){
    require_once __DIR__.DS.'aifs-config.php';
}

if (!defined('AIFS_ENC_KEY')) {
    define( 'AIFS_ENC_KEY', SECURE_AUTH_KEY); //@since 2.1.1
}


use AutoInstallFreeSSL\FreeSSLAuto\Admin\ForceSSL;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\HomeOptions;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\BasicSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\cPanelSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\cPanelExcludeDomainsSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\AddDnsServiceProviderSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\DnsServiceProvidersSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\DomainsSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\AddDomainSettings;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\Factory;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\AddCronJob;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\IssueFreeSSL;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\ChangeLeKey;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\RevokeSslCert;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\TmpSSL;

if (version_compare(phpversion(), '5.3.0') >= 0 && ! class_exists('AutoInstallFreeSSL\FreeSSLAuto\FreeSSLAuto')) {
    if (file_exists(__DIR__.DS.'vendor'.DS.'autoload.php')) {
        require_once __DIR__.DS.'vendor'.DS.'autoload.php';
    }
}

/**
 * Force SSL on frontend and backend
 */

new ForceSSL();

/** Create the menu */
function aifs_home_menu()
{
    
    /** Top level menu */
    add_menu_page(esc_html__("Auto-Install SSL Dashboard", 'auto-install-free-ssl'), esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'), 'manage_options', 'auto_install_free_ssl', 'aifs_home_options');
}


/** Register the above function using the admin_menu action hook and attach all other options  */
    
    if (is_admin()) {
        // activation hook
        register_activation_hook(__FILE__, 'activate_auto_install_free_ssl');
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, 'deactivate_auto_install_free_ssl');
        
        /** Add 'Settings' option */
        add_action('plugin_action_links_'.plugin_basename(__FILE__), 'aifs_add_settings_option_in_plugins_page');
        
        /** AIFS Home page */
        add_action('admin_menu', 'aifs_home_menu');
        
        /** AIFS daily cron job */
        //add_action('auto_install_free_ssl_daily_event', 'aifs_do_this_daily');
        
        /** Implementing Translations - load textdomain */
        add_action('init', 'aifs_load_textdomain');
        
        /** Basic Settings */
        new BasicSettings();
        
        /** Domains Settings / index */
        new DomainsSettings();
        
        /** Add Domain Settings */
        new AddDomainSettings();
        
        /** cPanel Settings */
        new cPanelSettings();
        
        /** cPanel Exclude Domains Settings */
        new cPanelExcludeDomainsSettings();
        
        /** DNS Service Providers Settings / index */
        new DnsServiceProvidersSettings();
        
        /** Add DNS Service Provider Settings */
        new AddDnsServiceProviderSettings();
        
        //Add the JS
        add_action('admin_enqueue_scripts', 'aifs_add_js_enqueue');
        
        /** Add Cron Job */
        new AddCronJob();
        
        /** Issue Free SSL certificate option */
        new IssueFreeSSL();
        
        /** Change Let's Encrypt Account Key */
        new ChangeLeKey();
        
        /** Revoke SSL certificate */
        new RevokeSslCert();
        
        /** Temporary free SSL certificate (without installation) */
        new TmpSSL();
    }

    /** Add 'Settings' option */
    function aifs_add_settings_option_in_plugins_page( $links ) {
        $links[] = '<a href="' .
            admin_url( 'admin.php?page=auto_install_free_ssl' ) .
            '">' . __('Settings') . '</a>';
            return $links;
    }

    /** Attach the home page */
    function aifs_home_options()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'auto-install-free-ssl'));
        }
                
        new HomeOptions();
    }
    
    /** Implementing Translations - load textdomain */
    function aifs_load_textdomain()
    {
        load_plugin_textdomain('auto-install-free-ssl', false, basename( dirname( __FILE__ ) ) . '/languages/');
    }
    
    /**
     * This function will be called during the plugin activation
     * */
    function activate_auto_install_free_ssl()
    {
        
        //make entry of AIFS_ENC_KEY in wp-config.php
        /* if (!defined('AIFS_ENC_KEY')) {
            $factory = new Factory();
            $key = $factory->encryptionTokenGenerator();
            
            $entry = <<<ENTRY

/** Auto-Install Free SSL encryption key */
/* define('AIFS_ENC_KEY', '${key}');

ENTRY;
            
            file_put_contents(__DIR__.DS.'aifs-config.php', $entry, FILE_APPEND);
        } */
        
        //Register the cron job
        
        /* if (!wp_next_scheduled('auto_install_free_ssl_daily_event')) {
            
            //Round monutes is being used to make the cron on top of the minutes. This may be helpful for testing.
            $timestamp = (round(time()/60) + 2) * 60;
            wp_schedule_event($timestamp, 'daily', 'auto_install_free_ssl_daily_event');
        } */
    }
    
    
    /**
     * This function will be called during the plugin deactivation
     * */
    function deactivate_auto_install_free_ssl()
    {
        
        //Remove the cron job
        /* if (wp_next_scheduled('auto_install_free_ssl_daily_event')) {
            wp_clear_scheduled_hook('auto_install_free_ssl_daily_event');
        } */
    }
    
    
    /**
     * Merge all the options in a single array
     * */
    function aifs_get_app_settings()
    {
        $app_settings = array();
        
        if (get_option('basic_settings_auto_install_free_ssl')) {
            $app_settings = get_option('basic_settings_auto_install_free_ssl');
        } else {
            return false;
        }
        
        if (get_option('cpanel_settings_auto_install_free_ssl')) {
            $app_settings = array_merge($app_settings, get_option('cpanel_settings_auto_install_free_ssl'));
        }
        
        if (get_option('exclude_domains_auto_install_free_ssl')) {
            $app_settings = array_merge($app_settings, get_option('exclude_domains_auto_install_free_ssl'));
        }
        
        if (get_option('dns_provider_auto_install_free_ssl')) {
            $app_settings = array_merge($app_settings, get_option('dns_provider_auto_install_free_ssl'));
        }
        
        if (get_option('all_domains_auto_install_free_ssl')) {
            $app_settings = array_merge($app_settings, get_option('all_domains_auto_install_free_ssl'));
        }
        
        if (get_option('domains_to_revoke_cert_auto_install_free_ssl')) {
            $app_settings = array_merge($app_settings, get_option('domains_to_revoke_cert_auto_install_free_ssl'));
        }
        
        return $app_settings;
    }
    
    /**
     * Get the domain of this WordPress website
     * 
     * @param bool $remove_www
     * @return string
     * 
     * @since 1.0.0
     */
        /* function aifs_get_domain(bool $remove_www = true){ 
         *  Removing parameter type hint to make compatible with PHP 5.6. Using scalar type hints like string is supported since PHP 7. */
        
      function aifs_get_domain($remove_www = true){
        
        $site_url = get_site_url();
        
        $site_url = parse_url($site_url);
        
        $domain = $site_url['host'];
        
        if($remove_www && strpos($domain, 'www.') !== false && strpos($domain, 'www.') == 0){ //If www. found at the beginning
            $domain = substr($domain, 4);
        }
        
        return $domain;
    }
    
    //Attach the JS
    function aifs_add_js_enqueue($hook)
    {
        // Only add to this admin.php admin page -> page=aifs_add_dns_service_provider
        if (!isset($_GET['page']) || ('admin.php' !== $hook && $_GET['page'] !== 'aifs_add_dns_service_provider')) {
            return;
        }
        
        wp_enqueue_script('aifs_custom_script', AIFS_URL . 'assets/js/script.js', array('jquery'));
    }
    
    
    /**
     * Set review option to 1 to display the review request
     *
     * @since 1.1.0
     */
    function aifs_set_display_review_option()
    {
        update_option( 'aifs_display_review', 1 );
    }
    add_action('aifs_display_review_init', 'aifs_set_display_review_option');
    
    
    /**
     * Set announcement option to 1 to display the announcement request again
     *
     * @since 2.2.2
     */
    function aifs_set_display_announcement_option()
    {
        update_option( 'aifs_display_announcement', 1 );
    }
    add_action('aifs_display_announcement_init', 'aifs_set_display_announcement_option');
    
    
    /**
     * If there are admin notices in the option table, display them and remove from the option table to prevent them being displayed forever
     *
     * @since 2.0.0
     */
    function aifs_display_flash_notices()
    {
        $notices = get_option('aifs_flash_notices');
       
        if($notices != false && count($notices) > 0){
            // Iterate through the notices to display them, if exist in option table
            foreach ($notices as $notice) {
                
                $style = ($notice['type'] == "success") ? 'style="color: #46b450;"' : '';
                
                printf('<div class="notice notice-%1$s %2$s" %3$s><p>%4$s</p></div>',
                    $notice['type'],
                    $notice['dismissible'],
                    $style,
                    $notice['notice']
                );
            }
            
            // Now delete the option
            delete_option('aifs_flash_notices');
        }
    }
    // Add the above function to admin_notices
    add_action('admin_notices', 'aifs_display_flash_notices', 12);
    
    
    /**
     * Add a flash notice to the options table which will be displayed upon page refresh or redirect
     *
     * @param string $notice (The notice text)
     * @param string $type (This can be "success", "info", "warning", "error". "success" is default.)
     * @param boolean $is_dismissible (Set this TRUE to add is-dismissible functionality)
     * 
     * @since 2.0.0
     */    
        /* function aifs_add_flash_notice(string $notice, string $type = "success", bool $is_dismissible = true ) {
         * Removing parameter type hint to make compatible with PHP 5.6. Using scalar type hints like string is supported since PHP 7. */
      
      function aifs_add_flash_notice($notice, $type = "success", $is_dismissible = true ) {
        // Get the notices already saved in the option table, if any, or return an empty array
        $notices = get_option('aifs_flash_notices', array());
        
        $dismissible_text = ($is_dismissible) ? "is-dismissible" : "";
        
        // Add the new notice
        array_push($notices, array(
            "notice" => $notice,
            "type" => $type,
            "dismissible" => $dismissible_text
        ) );
        
        // Now update the option with the notices
        update_option('aifs_flash_notices', $notices);
    }
    
    
    
    
    //Daily cron - but this removed once the cPanel cron job added with the 'Add cron job' option
    /* function aifs_do_this_daily()
    {
        if (isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) {
            require_once __DIR__.DS.'cron.php';
        }
    } */
