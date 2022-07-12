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

        
    // Load the WordPress library if aifs_get_app_settings() does not exist
        
    if (!function_exists('aifs_get_app_settings')) {
        require_once __DIR__ . '/../../../wp-load.php';
    }
    
    if (!function_exists('aifs_get_app_settings')) {
        //This is useful if autoload fails to detect the plugin's root file
        require_once __DIR__ . DS . 'auto-install-free-ssl.php';
    }
   
     define('WP_USE_THEMES', false);
     
    // Composer autoloading
    if (version_compare(phpversion(), '5.3.0') >= 0 && ! class_exists('AutoInstallFreeSSL\FreeSSLAuto\FreeSSLAuto')) {
        if (file_exists(__DIR__.DS.'vendor'.DS.'autoload.php')) {
            require_once __DIR__.DS.'vendor'.DS.'autoload.php';
        }
    }

    if(!function_exists('findRegisteredDomain') && !function_exists('getRegisteredDomain') && !function_exists('validDomainPart')){
        require_once __DIR__.DS.'vendor'.DS.'usrflo'.DS.'registered-domain-libs'.DS.'PHP'.DS.'effectiveTLDs.inc.php';
        require_once __DIR__.DS.'vendor'.DS.'usrflo'.DS.'registered-domain-libs'.DS.'PHP'.DS.'regDomain.inc.php';
    }
    
    //define AIFS_ISSUE_SSL true
    define('AIFS_ISSUE_SSL', true);
    //other constants should be false
    define('AIFS_KEY_CHANGE', false);
    define('AIFS_REVOKE_CERT', false);

    use AutoInstallFreeSSL\FreeSSLAuto\FreeSSLAuto;

    //Retrieve settings from db
    
    $appConfig = aifs_get_app_settings();


    $freeSsl = new FreeSSLAuto($appConfig);

    //Run the App
    $freeSsl->run();
