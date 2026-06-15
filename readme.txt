=== Free SSL Certificate Plugin for WordPress – Auto-Install Free SSL, Force HTTPS Redirect ===
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tags: ssl,https,free ssl,ssl certificate,mixed content
Requires at least: 4.1
Tested up to: 5.9
Stable tag: 2026.1.0
Requires PHP: 5.6

WordPress plugin to automate issue & install free SSL certificates (Let's Encrypt), one-click force HTTPS redirect, fix insecure mixed content.

== Description ==

### Auto-Install Free SSL

'Auto-Install Free SSL' automatically issues, renews and installs Let’s Encrypt™ Free SSL Certificate on WordPress website & also on all the websites hosted in a cPanel server.

This WordPress plugin works in cPanel shared hosting. You don't need to have coding experience or server admin experience to set it up. Root access is NOT required. All you need is 8 minutes only. Install it, provide a few required information, and set up the daily cron job. **You can even set up the cron job from this plugin with a single click.** That's it!

This WordPress plugin works on other web hosting control panels also, except for the auto-installation feature.

`This plugin supports Linux hosting but doesn't work with Windows hosting.`

### Key Features

* Automatic Free SSL Certificate issue, renewal, and installation in cPanel shared hosting. <- FREE of cost
* One-click Force SSL activation, i.e., HTTPS redirect, fix insecure links and mixed content warning, display the padlock in the address bar with only one click. <- FREE of cost

#### Features in detail

* 'Auto-Install Free SSL' works not only on your WordPress website's domain but on all the websites hosted on your cPanel / web hosting.
* This WordPress plugin is capable of issuing WildCard SSL certificate for free!
* If you have cPanel shared hosting, the plugin will install free SSL certificate automatically.
* Choose the interval to renew SSL certificates. The default is 30 days before the expiry.
* This WordPress plugin saves the SSL certificates and private keys files above the document root (i.e., 'public_html').
* You have the option to set the directory name in which this plugin saves SSL certificates and private keys.
* Set the key length of SSL certificate as per your wish. The default value is 2048 bytes/bit.
* The plugin saves the sensitive information (password/API secret) in your WordPress database encrypting with the open SSL.
* There is an option to create a daily cron job with one click — no need to log in your web hosting control panel.
* Do you need to issue wildcard SSL? You get four DNS service providers for which the plugin sets DNS TXT record automatically: Cloudflare, Godaddy, Namecheap, and cPanel. If your DNS provider is not supported, you have option to set the DNS TXT record yourself. At the right time, this WordPress plugin sends an email that provides the required data to set the DNS TXT record.
* If the WordPress plugin automatically sets the DNS TXT record, it waits for 2 minutes before it sends challenges to the API of Let’s Encrypt for verification of your domains. If your DNS provider takes more than 2 minutes to propagate the TXT records, you have the option to make the plugin wait beyond two minutes interval.
* If you set the DNS TXT record manually, the plugin waits until the TXT record propagation complete. Some web hosting company may terminate the cron job if the DNS service provider takes longer to complete propagation. In that situation, you may wait until the next run of the cron job or choose non-wildcard SSL for each sub-domain.
* If your website currently doesn't have an SSL certificate installed, this WordPress plugin provides an option to generate one free SSL certificate even before you configure the plugin. You get this option when you try to provide cPanel password or DNS API credentials over an insecure connection. So, no need to enter sensitive credentials on an insecure page.
* You can revoke any SSL certificate or change your Let's Encrypt™ account key at any time.
* Fix insecure links & mixed content warnings and display the padlock with a single click.
* One-click revert to HTTP if required.


#### Minimum System Requirements

* Linux hosting (Windows hosting is not supported)
* WordPress 4.1
* PHP 5.6
* OpenSSL extension
* Curl extension
* PHP directive allow_url_fopen = On
* For the automatic SSL certificate installation feature, your cPanel need to have the SSL installation feature enabled.

**Case 1:** This WordPress plugin needs cPanel API to auto-install the SSL certificate. If your web hosting control panel is any other than cPanel, the client can't install SSL certificate automatically. In that case, you can install the issued free SSL manually.

**Case 2:** Do you have cPanel, but the SSL certificate installation feature is DISABLED? You need to request your web hosting service provider to enable the feature, or you may contact them to install the free SSL issued by this plugin.

For any of the case 1 and 2, all other processes are automated. The plugin sends an automated email in the event of issue/renewal of free SSL certificate. The email tells you the path details of the SSL certificate, private key, and CA bundle. You need to install the SSL yourself (case 1) manually or with the help of your web hosting provider (case 2).

### Installation

=== Popular and most easy method ===

1. Click 'Plugins > Add New'.
1. Type this keyword in the search box: 'Auto-Install Free SSL'.
1. Once you found the plugin click 'Install Now' button.
1. Click 'Activate Plugin'.
1. Go to the "After activation" section below.

=== Manually upload  ===

1. Download the plugin from this page.
1. Upload the plugin archive to the `/wp-content/plugins/` directory and extract it.
1. Go to the 'Plugins' page in your WordPress backend and activate 'Auto-Install Free SSL'.
1. Go to the "After activation" section below.

=== After Activation ===

1. After activating the plugin you see the 'Settings' link. Click on it.
1. You get the dashboard of the plugin. You can also get this page from the admin menu at the bottom-left (below the default 'settings' menu) of your WordPress backend.
1. You get the 'Basic Settings' option only, at this point. Click it and provide a little information with the form.
1. Then you get other buttons like cPanel Settings, Exclude Domains, Add Cron Job. Click these buttons and fill in all the required information. Add the cron job.
1. On the first run of the cron job, the plugin issues and installs an SSL certificate automatically. You receive an email for the same.
1. Then go to the plugin's dashboard. Click the **'Activate Force HTTPS'** button (this button will be visible only after the plugin issue an SSL certificate). This one-click makes sure your website has no mixed content warning. Now the padlock appears in the browser's address bar.
1. Congratulations! You're all set.

== Frequently Asked Questions ==

= Why do you need my cPanel password when others Let's Encrypt clients don’t? =

cPanel username and password is required to install the free SSL certificate automatically with the cPanel API. Let's Encrypt SSL's lifetime is 90 days. You need to get and install another SSL certificate before the expiration of the current SSL. If you provide your cPanel username and password, this plugin will do this repeated job automatically. All your credentials remain safe in your database. Moreover, 'Auto-Install Free SSL'  encrypts the password before saving in your database.

All other Let's Encrypt clients who auto-install free SSL certificate, needs root access, which is a higher privilege than the cPanel user. In shared hosting, the root access belongs to the web hosting company. So those clients will not work on shared hosting.

= Does this WordPress plugin send the cPanel username or cPanel password to your server or to Let's Encrypt? =

We or Let's Encrypt don't collect any credentials. **This plugin’s source code is open for audit.** The team WordPress approved it after the audit. Please feel free to audit yourself too.

If you still hesitate for the password, please set the cPanel option to NO (in basic settings). You still get the SSL certificate and automated renewal. But you need to install the generated SSL manually. You need to provide all your domain information manually too.

= I installed 'Auto-Install Free SSL' and did everything. But the SSL certificate was not issued. What should I do? =

Please click the 'Cron Jobs' option in your cPanel. You'll get the 'Cron Jobs' page. Now look for the following text under 'Current Cron Jobs' section:
`wp-content/plugins/auto-install-free-ssl/cron.php`

**Case 1:** If you found this line, you have created the cron job successfully. This cron job will keep the plugin's daily job running. Now please wait 24 hours (max). You'll get an email notification that will tell you that the SSL certificate issued and installed automatically.

**Case 2:** If you don't see that text, no cron job was added. For this reason, no SSL certificate was issued. Please create a cron job manually.

= How do I create the cron job? =

'Auto-Install Free SSL' has an option to add the cron job with one click from your WordPress dashboard.

= I received the confirmation email but didn't receive the cron output. Why? =

Make sure you have provided your email in the 'Cron Email' section of the Cron Jobs page of cPanel.

Let's Encrypt™ is a trademark of the Internet Security Research Group. All rights reserved.

== Changelog ==

= 2026.1.0 =
* Fix: The CA bundle now always includes the root certificate. Previously, only the intermediate certificate was included in the CA bundle, which caused installation failures on hosting servers (including cPanel servers) where the root certificate was not present in the local trust store. This fix also ensures correct handling of Let's Encrypt's newer Generation Y chains, which can contain more than three certificates.
