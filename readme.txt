=== Free SSL Certificate Plugin for WordPress – Auto-Install Free SSL, Force HTTPS Redirect ===
Contributors: speedify, freessl
Donate link: https://www.paypal.me/site4author
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tags: ssl,https,free ssl,ssl certificate,mixed content
Requires at least: 4.1
Tested up to: 5.9
Stable tag: 2.2.4
Requires PHP: 5.6
Development location: https://freessl.tech

WordPress plugin to automate issue & install free SSL certificates (Let's Encrypt), one-click force HTTPS redirect, fix insecure mixed content.

== Description ==

### Auto-Install Free SSL

'Auto-Install Free SSL' automatically issues, renews and installs Let’s Encrypt™ Free SSL Certificate on WordPress website & also on all the websites hosted in a cPanel server.

https://www.youtube.com/watch?v=tYIOHsuY-HE

This WordPress plugin works in cPanel shared hosting. You don't need to have coding experience or server admin experience to set it up. Root access is NOT required. All you need is 8 minutes only. Install it, provide a few required information, and set up the daily cron job. **You can even set up the cron job from this plugin with a single click.** That's it!

This WordPress plugin works on other web hosting control panels also, except for the auto-installation feature.

`This plugin supports Linux hosting 
but doesn't work with Windows hosting.`

## 112,500+ downloads

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

* Linux hosting (windows hosting is not supported)
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

=== Support and Report a Bug ===

Please check the existing topics in the WordPress [support forum](https://wordpress.org/support/plugin/auto-install-free-ssl) before creating a new topic for support or reporting a bug.


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


=== Credits ===
* [Let's Encrypt™](https://letsencrypt.org)
* I developed this plugin based on the PHP client/app ['FreeSSL.tech Auto'](https://freessl.tech), which I developed with a massive rewrite of [Lescript](https://github.com/analogic/lescript).
* [cPanel](https://cpanel.com)


Let's Encrypt™ is a trademark of the Internet Security Research Group. All rights reserved.

=== Screenshots ===
1. Admin Menu of 'Auto-Install Free SSL'
2. The dashboard of the plugin
3. Basic Settings page
4. cPanel Settings page
5. Exclude Domains / Sub-domains page
6. DNS Service Providers (index)
7. Add New DNS Service Provider
8. Add Cron Job
9. Email confirmation when the plugin issue and install a free SSL certificate
10. Activate Force HTTPS and get the padlock with one-click
11. Automated email with a link to revert to HTTP
12. Revert to HTTP option in the plugin dashboard


== Changelog ==

= 2.2.3 =
* Disabled plugin updates.

= 2.2.3 =
* Fixed conflict with admin page CSS class of WordPress 5.9.

= 2.2.2 =
* Fixed conflict with 'Post SMTP Mailer/Email Log' plugin.
* Fixed an issue to make it translation ready.
* Announcement to restructure the features.

= 2.2.1 =
* Removes parameter type declaration of the function connect_over_ssl() to make the plugin compatible with PHP 5.6. This function has been added in the version 2.2.0.

= 2.2.0 =
* Adds validation with the Activate Force HTTPS option. Now it works only if a valid SSL installed on the website.
* Changed the support link that appears in the footer of the admin pages.

= 2.1.7 =
* Fixed a bug with 'Issue and install Free SSL certificate' option
* Improved the layout of 'Issue and install Free SSL certificate' option

= 2.1.6 =
* Adds video guide: How to Configure this Plugin and set up Automation

= 2.1.5 =
* Fixed minor error in the file DnsServiceProvidersSettings.php that throws PHP Notice: Undefined index: use_wildcard

= 2.1.4 =
* Improves 'Add Cron Job' option.
* Adds two video guides: 'How to add a Cron Job in a minute on cPanel shared hosting' and 'How to Install Free SSL Certificate on cPanel Shared Hosting'.
* Adds FAQ.

= 2.1.3 =
* Improves Force HTTPS feature. Regenerating dynamic CSS with premium themes will include HTTPS and remove the not secure warning in browsers.

= 2.1.2 =
* Fixed a bug with the dashboard of Auto-Install Free SSL.

= 2.1.1 =
* Fixed issue with the encryption key.
* Adds admin notification and sends an email to admin in case the encryption key was changed due to a previous update.

= 2.1.0 =
* Improves internal validation (HTTP-01 challenge) - Before the domain ownership validation with Let's Encrypt, if the payload content doesn't match with content of the challenge URI (in internal check), attempt for automatic fix with .htaccess rules in two different ways. 
* Improves cPanel Settings option.
* Improves Temporary SSL option.

= 2.0.1 =
* Fixed issues with PHP 5.6, 7.0 and 7.2

= 2.0.0 =
* Adds the option to activate force HTTPS and remove mixed content warning with a single click. This feature will make the padlock visible in the browser's address bar.
* Removes the option to choose Let's Encrypt ACME version. The plugin now uses ACME V2 only. Because V1 is reaching the end of life soon.

= 1.1.0 =
* Fixed issue with cron job

= 1.0.0 =
* Initial release
