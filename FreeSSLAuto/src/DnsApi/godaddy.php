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

namespace AutoInstallFreeSSL\FreeSSLAuto\DnsApi;

use AutoInstallFreeSSL\FreeSSLAuto\Admin\Factory as AdminFactory;
use AutoInstallFreeSSL\FreeSSLAuto\Logger;

class godaddy
{
    private $api_base = 'https://api.godaddy.com';
    private $api_key;
    private $api_secret;

    /**
     * Initiates the godaddy class.
     *
     * @param array $provider
     */
    public function __construct($provider)
    {
        $adminFactory = new AdminFactory();

        $this->api_key = $provider['api_identifier'];
        $this->api_secret = $adminFactory->decryptText($provider['api_credential']);
        $this->logger = new Logger();
    }

    /**
     * Set DNS TXT record.
     *
     * @param string $domain
     * @param string $txt_name
     * @param string $txt_value
     *
     * @return array
     */
    public function setTxt($domain, $txt_name, $txt_value)
    {
        $data = [
            [
                'data' => $txt_value,
                'ttl' => 600,
            ],
        ];

        $curl = new DnsCurl('GoDaddy', $this->api_key, $this->api_secret);

        //Add or Replace (if $txt_name already exist) the DNS Records for the specified Domain with the specified Type and Name
        $result = $curl->connect('PUT', $this->api_base.'/v1/domains/'.$domain.'/records/TXT/'.$txt_name, $data);

        //Check status code
        if (200 === $result['http_code']) {
            //Success
            $this->logger->log('Congrats! TXT record added successfully.');
        }

        //Add new record -  but this does NOT replace
        //$result = $curl->connect('PATCH', $this->api_base."/v1/domains/".$domain."/records", $data);

        return $result;
    }

    /**
     * Fetch all DNS records.
     *
     * @param string $domain
     *
     * @return array
     */
    public function fetchAll($domain)
    {
        $curl = new DnsCurl('GoDaddy', $this->api_key, $this->api_secret);
        //Fetch all record
        return $curl->connect('GET', $this->api_base."/v1/domains/${domain}/records");
    }
}
