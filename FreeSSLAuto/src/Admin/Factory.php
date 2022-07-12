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

use InvalidArgumentException;
use AutoInstallFreeSSL\FreeSSLAuto\Acme\Factory as AcmeFactory;
use AutoInstallFreeSSL\FreeSSLAuto\Controller;
use DateTime;

class Factory
{
    public function __construct()
    {
    }

    /**
     * Sanitize string.
     *
     * @param string $data
     *
     * @return mixed
     */
    public function sanitize_string($data)
    {
        //remove space before and after
        $data = trim($data);

        //remove slashes
        $data = stripslashes($data);

        return filter_var($data, FILTER_SANITIZE_STRING);
    }

    /**
     * redirect to another page.
     *
     * @param string $url
     */
    public function redirect($url)
    {
        if (!headers_sent()) {
            header('Location: '.$url);
            exit;
        }
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.$url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>';
        exit;
    }

    /**
     * Random encryption token generator: 64 characters.
     */
    public function encryptionTokenGenerator()
    {
        $chars_lower_case = 'abcdefghijklmnopqrstuvwxyz';
        $chars_upper_case = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_numbers = '0123456789';
        $chars_special = '!@#$%^&*(){}[]_-=+;:,?';

        $password = substr(str_shuffle($chars_lower_case), 0, 25);
        $password .= substr(str_shuffle($chars_upper_case), 0, 14);
        $password .= substr(str_shuffle($chars_special), 0, 15);
        $password .= substr(str_shuffle($chars_numbers), 0, 10);

        return str_shuffle($password);
    }

    /**
     * Random Password RESET TOKEN Generator: 32 characters.
     */
    public function passwordResetTokenGenerator()
    {
        $chars_lower_case = 'abcdefghijklmnopqrstuvwxyz';
        $chars_numbers = '0123456789';

        $password = substr(str_shuffle($chars_lower_case), 0, 24);
        $password .= substr(str_shuffle($chars_numbers), 0, 8);

        return str_shuffle($password);
    }

    /**
     * CSRF token generator: 64 bits.
     *
     * @param string $form_name
     * @param bool   $unset_previous
     *
     * @return string
     */
    public function getCsrfToken($form_name, $unset_previous = false)
    {
        //start session
        if (!session_id()) {
            session_start();
        }

        if ($unset_previous) {
            //This option will enable us to generate new token for every load (GET only) of a form
            unset($_SESSION['token'], $_SESSION['token_timestamp']);
        }

        if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
            if (\function_exists('random_bytes')) {
                $token = bin2hex(random_bytes(32));
            }  else {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
            }

            $_SESSION['token'] = $token;

            $_SESSION['token_timestamp'] = time();
        }

        return hash_hmac('sha256', $form_name, $_SESSION['token']);
    }

    /**
     * Verify CSRF token: 64 bits.
     *
     * @param string $form_name
     * @param string $token_returned
     *
     * @return bool
     */
    public function verifyCsrfToken($form_name, $token_returned)
    {
        $generated_token = $this->getCsrfToken($form_name);

        //verify against time
        $currentTime = time();

        if ($currentTime - $_SESSION['token_timestamp'] > 15 * 60) {
            //15 minutes exceeded, i.e., token expired
            unset($_SESSION['token'], $_SESSION['token_timestamp']);

            return false;
        }
        if (hash_equals($generated_token, $token_returned)) {
            // verified
            unset($_SESSION['token'], $_SESSION['token_timestamp']);

            return true;
        }

        return false;
        //Sorry! This form's security token expired or not valid. Please submit the form again.
    }

    /**
     * Encryption with open SSL.
     *
     * @param string $plaintext
     *
     * @return string
     */
    public function encryptText($plaintext)
    {
        //$key previously generated safely, ie: openssl_random_pseudo_bytes

        $ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, AIFS_ENC_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, AIFS_ENC_KEY, $as_binary = true);

        return base64_encode($iv.$hmac.$ciphertext_raw);
    }

    /**
     * decrypt with open SSL.
     *
     * @param string $ciphertext
     *
     * @return bool|string
     */
    public function decryptText($ciphertext)
    {
        $c = base64_decode($ciphertext, true);
        $ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, AIFS_ENC_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, AIFS_ENC_KEY, $as_binary = true);

        if (hash_equals($hmac, $calcmac)) {//PHP 5.6+ timing attack safe comparison; now backported with hash_equals.php
            return $original_plaintext;
        }

        return false;
    }

    /**
     * get sub-directories in the given directory.
     *
     * @param string $dirPath
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getSubDirectories($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("${dirPath} must be a directory");
        }
        if ('/' !== substr($dirPath, \strlen($dirPath) - 1, 1)) {
            $dirPath .= '/';
        }

        $dirs = [];

        $files = glob($dirPath.'*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirs[] = $file;
            }
        }

        return $dirs;
    }

    /**
     * get existing SSLs in the given directory.
     *
     * @param string $dirPath
     *
     * @return array
     */
    public function getExistingSslList($dirPath)
    {
        $dirs = $this->getSubDirectories($dirPath);

        $ssl_domains = [];

        foreach ($dirs as $dir) {
            $domain = basename($dir);

            if ('_account' !== $domain) {
                $ssl_domains[] = $domain;
            }
        }

        return $ssl_domains;
    }
    
    
    /**
     * Check if SSL cert was generated for a given domain, by searching in the plugin's certificate directory
     * 
     * @param string $domain
     * @return boolean
     * 
     * @since 2.1.0
     */
    public function is_ssl_issued_and_valid($domain_as_is){
        
        if(!function_exists('findRegisteredDomain') && !function_exists('getRegisteredDomain') && !function_exists('validDomainPart')){
            require_once AIFS_DIR.DS.'vendor'.DS.'usrflo'.DS.'registered-domain-libs'.DS.'PHP'.DS.'effectiveTLDs.inc.php';
            require_once AIFS_DIR.DS.'vendor'.DS.'usrflo'.DS.'registered-domain-libs'.DS.'PHP'.DS.'regDomain.inc.php';
        }
              
        $app_settings = aifs_get_app_settings();
                
        if ((isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) && isset($app_settings['homedir'])) {
        
            $acmeFactory = new AcmeFactory($app_settings['homedir'].'/'.$app_settings['certificate_directory'], $app_settings['acme_version'], $app_settings['is_staging']);
        
            //get the path of SSL files
            $certificates_directory = $acmeFactory->getCertificatesDir();
                        
            if(is_dir($certificates_directory)){
                
                if (strpos($domain_as_is, 'www.') === false || strpos($domain_as_is, 'www.') != 0) {//No www. found at beginning                   
                    $domain_with_www = 'www.'.$domain_as_is;
                    $domain = $domain_as_is;
                } 
                elseif(strpos($domain_as_is, 'www.') == 0) {// www. found at the beginning
                    
                    $domain_with_www = $domain_as_is;
                    $domain = substr($domain_as_is, 4);                    
                }
                 
                //Search # 1
                if(is_dir($certificates_directory."/".$domain)){
                    
                    if($this->is_cert_file_has_ssl_for($domain_as_is, $certificates_directory."/".$domain)){
                        return true;
                    }
                }
                
                //Search # 2
                if(strpos($domain_as_is, 'www.') == 0) {// www. found at the beginning
                    $wildcard = "*.".substr($domain_as_is, 4);
                    
                    if(is_dir($certificates_directory."/".$wildcard)){
                        
                        if($this->is_cert_file_has_ssl_for($wildcard, $certificates_directory."/".$wildcard)){
                            return true;
                        }
                    }
                }
                
                
                //Search # 3
                $controller = new Controller();
                
                //Try again with the wildcard version                
                $wildcard_domain_1 = $controller->getWildcardBase($domain_as_is);
                                        
                 if(is_dir($certificates_directory."/".$wildcard_domain_1)){
                     
                     if($this->is_cert_file_has_ssl_for($wildcard_domain_1, $certificates_directory."/".$wildcard_domain_1)){
                         return true;
                     }
                 }
                        
                 //Search # 4
                 $wildcard_domain_2 = $controller->getWildcardBase(str_replace("*.", "", $wildcard_domain_1));
                                 
                 if(is_dir($certificates_directory."/".$wildcard_domain_2)){
                     
                     if($this->is_cert_file_has_ssl_for($domain_as_is, $certificates_directory."/".$wildcard_domain_2)){
                         return true;
                     }
                 }
                
                 $wildcard_domain_3 = $controller->getWildcardBase(str_replace("*.", "", $wildcard_domain_2));
                                 
            }
            
        }
        
        return false;
    }
    
    /**
     * 
     * @param string $domain_as_is
     * @param string $cert_dir
     * @return boolean
     * 
     * @since 2.1.0
     */
    private function is_cert_file_has_ssl_for($domain_as_is, $cert_dir){
        
        $ssl_cert_file = $cert_dir.'/certificate.pem';
        
        if (!file_exists($ssl_cert_file)) {
            // We don't have a SSL certificate
            return false;
        }
        else{
            // We have a SSL certificate.
            $cert_array = openssl_x509_parse(openssl_x509_read(file_get_contents($ssl_cert_file)));
            
            //Get SAN array
            $subjectAltName = explode(',', str_replace('DNS:', '', $cert_array['extensions']['subjectAltName']));
            
            //remove space and cast as string
            $sansArrayFiltered = array_map(function ($piece) {
                return (string) trim($piece);
            }, $subjectAltName);
            
            if(in_array($domain_as_is, $sansArrayFiltered, true)){
                
                $now = new DateTime();
                $expiry = new DateTime('@'.$cert_array['validTo_time_t']);
                $interval = (int) $now->diff($expiry)->format('%R%a');
                                
                if ($interval > 1) {
                    return true;
                }
                else{
                    return false;
                }
          }
          else{
              return false;
          }
        }
    }
    
    
    /**
     * Check if a valid SSL installed on this website
     * 
     * @return mixed
     */
    public function is_ssl_installed_on_this_website(){
                
        $expected_status_codes = [200, 201, 202, 204];
        
        $domain_site = aifs_get_domain(false);
                
        $test_1  = $this->connect_over_ssl($domain_site);
        
        if($test_1['error_number'] != 0 || !in_array($test_1['http_status_code'], $expected_status_codes)){
            
            if(strpos($domain_site, 'www.') !== false && strpos($domain_site, 'www.') == 0){ //If www. found at the beginning
                
                $domain_other_version = substr($domain_site, 4);
            }
            else{
                $domain_other_version = 'www.'.$domain_site;
            }
            
            $ssl_details['domain_site'] = array(                
                'ssl_installed' => false,
                'url' => $domain_site,
                'error_cause' => $test_1['error_cause']
            );

            $test_2  = $this->connect_over_ssl($domain_other_version);
        
            $ssl_details['domain_other_version'] = array(                
                'url' => $domain_other_version,
                'error_cause' => $test_2['error_cause']
            );
            
            //check other version
            if($test_2['error_number'] == 0){
                
                if(in_array($test_2['http_status_code'], $expected_status_codes) || $test_2['http_status_code'] == 301){
                    
                    $ssl_details['domain_other_version']['ssl_installed'] = true;
                }
                else{
                     // Unknown status code
                    $ssl_details['domain_other_version']['ssl_installed'] = false;
                }
                                
            }
            else{
                //SSL NOT installed on $domain_site && $domain_other_version
                $ssl_details['domain_other_version']['ssl_installed'] = false;
            }
            
            return $ssl_details;
            
        }
        else{
            return true; //SSL installed on $domain_site
        }
        
    }
    
    
    /**
     * Connect with the given domain over HTTPS and return 
     * http status code and error details, if any
     * 
     * @param string $domain
     * @return array
     */
    private function connect_over_ssl($domain){
        
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, 'https://'.$domain);        
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
                
        $response = curl_exec($handle);

        return array(
            'error_number' => curl_errno($handle),
            'error_cause' => curl_error($handle),
            'http_status_code' => curl_getinfo($handle, CURLINFO_HTTP_CODE)
        );
        
    }
        
    
}
