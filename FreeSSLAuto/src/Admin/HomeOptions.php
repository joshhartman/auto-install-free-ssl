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
use AutoInstallFreeSSL\FreeSSLAuto\cPanel\cPanel;
use AutoInstallFreeSSL\FreeSSLAuto\Admin\ForceSSL;
use AutoInstallFreeSSL\FreeSSLAuto\Email;

/**
 * Home page options
 *
 */
class HomeOptions
{
    
    
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
              
        $app_settings = aifs_get_app_settings();
        
        $factory =  new Factory();
        
        $step = 1;
        
        global $wp_version;
        
        $version_parts = explode(".", $wp_version);
        
        $version_base = (int) $version_parts[0];
        
        /*
         * Settings Update notice
         */
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            
            if($version_base === 5){
                $style = 'class="notice notice-success is-dismissible"';
            }
            else{
                $style = 'id="message" class="updated below-h2"';
            }
            
            echo '<div '.$style.'><p>';
            echo esc_html__("The settings have been updated successfully!", 'auto-install-free-ssl');
            echo '</p></div>';
        }
        
        /*
         * Other notice CSS class
         */
        if($version_base === 5){
            $style = 'class="notice notice-success"';
        }
        else{
            $style = 'id="message" class="updated below-h2"';
        } 
        
        //get this domain
        $site_url = aifs_get_domain(false);
        
        
        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70100) {
            /**
             * PHP 5.6 and 7.0 not detecting is_dir above public_html over HTTP/HTTPS. That's why this condition.
             */
            $display_all = true;
            $step_number = "";
            
        }               
        elseif ((isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) && isset($app_settings['homedir'])) {
            $display_all = false; //SSL being issued. So display as per condition.
            
            $step_number = esc_html__("Final Step:", 'auto-install-free-ssl');
            
            //initialize the Acme Factory class
            $acmeFactory = new AcmeFactory($app_settings['homedir'].'/'.$app_settings['certificate_directory'], $app_settings['acme_version'], $app_settings['is_staging']);
            
            //get the path of SSL files
            $certificates_directory = $acmeFactory->getCertificatesDir();
            
            if(is_dir($certificates_directory)){
                
                //get the domains for which SSL is present in the directory
                $all_domains = $factory->getExistingSslList($certificates_directory);
            }
            
        }
        else {
            $display_all = true; //To open the posibility of 'Force HTTPS only' usage display all in this situation
            $step_number = "";
        }
        
        /*
         * Check whether an SSL is issued to this website
         */
        if ((isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) && isset($app_settings['homedir'])) {
                        
            $ssl_issued_to_this_domain = $factory->is_ssl_issued_and_valid($site_url);            
                
        }
        
        /*
         * Display default message to start with
         */
        if (!((isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) && isset($app_settings['homedir']))) {
            
            echo '<div '.$style.'><p>';
            echo esc_html__("The primary purpose of this plugin is to generate and auto-install Free SSL Certificates. ", 'auto-install-free-ssl');
            echo "<span style='color: #46b450; font-weight: bold;'>". esc_html__("But you can use it only for Force HTTPS too.", 'auto-install-free-ssl');
            
            if(!is_ssl()){
                echo ", ". esc_html__("if an SSL Certificate already installed on this website. ", 'auto-install-free-ssl');
            }
            
            echo "</span></p><p>";
            echo "<strong>" . esc_html__("If you want to generate Free SSL Certificates please start with the Basic settings.", 'auto-install-free-ssl') . "</strong> ";
            echo esc_html__("This plugin will display other options based on your information. ", 'auto-install-free-ssl')."</p><p>";
            
            if(!$display_all){
                echo esc_html__("Please note that, after you submit basic settings (step 1) and step 2 form, the plugin will hide the 'Activate Force HTTPS' option temporarily. It will show this option again when you generate a free SSL certificate for this website using this plugin. This precaution is to avoid accidental usage of force SSL, which may cause issues.", 'auto-install-free-ssl');
            }
            
            echo '</p></div>';
        }
        else{
                        
            if (!$ssl_issued_to_this_domain && !$display_all) {
                //if SSL directory doesn't exist
                
                echo '<div '.$style.'><p>';
                
                echo esc_html__("This plugin will generate free SSL certificate/s at the first run of the cron job (if you have already set up the cron job). This will happen in 24 hours. Then you'll get the 'Activate Force HTTPS' option again. ", 'auto-install-free-ssl')."<br /><br />";
                
                echo esc_html__("Can't wait? If you are confident using the cron job, change the frequency to run once every minute. After one minute, change the frequency again to run once every day. Please do this change for the first time only.", 'auto-install-free-ssl');
                
                echo '</p></div>';
            }
        }
        
        //
        
        
         
        ?>
        
                
        <div class="wrap">
        
        <?php
        echo '<h1>'.esc_html__("Auto-Install Free SSL : Dashboard", 'auto-install-free-ssl').'</h1>';
        //echo "<p>".esc_html__("Auto installs Free SSL certificate in shared hosting cPanel", 'auto-install-free-ssl').".</p>";
        echo '<p>'.esc_html__("The sequence of steps and video guide are given below", 'auto-install-free-ssl').':</p>'; ?>     
        
        <table>
        	<tr>
        	<td width="35%">       
            
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
        $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_basic_settings'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Basic Settings", 'auto-install-free-ssl'); ?></a>
            
        
        <?php if (isset($app_settings['is_cpanel']) && $app_settings['is_cpanel']) {
            ?>    
            <p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_cpanel_settings'); ?>" class="page-title-action button-primary"><?php echo esc_html__("cPanel Settings", 'auto-install-free-ssl'); ?></a>
            </p>
            
            <p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_exclude_domains'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Exclude Domains", 'auto-install-free-ssl'); ?></a> (<?php echo esc_html__("optional", 'auto-install-free-ssl'); ?>)
            </p>
        
        <?php
        } elseif (isset($app_settings['is_cpanel']) && !$app_settings['is_cpanel']) {
            ?>
        
        	<p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_domains'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Domains", 'auto-install-free-ssl'); ?></a>
            </p>
            
            <p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_add_domain'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Add New Domain", 'auto-install-free-ssl'); ?></a>
            </p>
        
        <?php
        } ?>
        
        <?php if (isset($app_settings['acme_version']) && $app_settings['acme_version'] === 2 && $app_settings['use_wildcard']) {
            ?>    
        
            <p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_dns_service_providers'); ?>" class="page-title-action button-primary"><?php echo esc_html__("DNS Service Providers", 'auto-install-free-ssl'); ?></a>
            </p>
        
        <?php
        } ?>
        
        <?php if (isset($app_settings['cpanel_host']) || isset($app_settings['all_domains'])) {
            ?>  
        	<p>
            	<br /><strong><?php echo esc_html__("Step", 'auto-install-free-ssl'); ?> <?php echo $step;
            $step++; ?>:</strong> <a href="<?php menu_page_url('aifs_add_cron_job'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Add Cron Job", 'auto-install-free-ssl'); ?></a>
            </p>
                    
        	<?php if (!$app_settings['use_wildcard']) {
                ?>
            	<p>
                	<br /><a href="<?php menu_page_url('aifs_issue_free_ssl'); ?>" class="page-title-action button-primary" onclick="return confirm('<?php echo esc_html__("If you have more than two domains/sub-domains, you may face a timeout issue. In that case, either use the cron job or utilize the \'Exclude Domains\' option.", 'auto-install-free-ssl'); ?>');"><?php echo esc_html__("Issue and install Free SSL certificate", 'auto-install-free-ssl'); ?></a> (<?php echo esc_html__("optional", 'auto-install-free-ssl'); ?>)
                </p>
             <?php
            } 
            
            if (isset($all_domains) && count($all_domains) > 0) { ?>
                     
                    <p>
                    	<br /><a href="<?php menu_page_url('aifs_change_le_account_key'); ?>" class="page-title-action button-primary" onclick="return confirm('<?php echo esc_html__("Are you sure to change your Let\'s Encrypt account key?", 'auto-install-free-ssl'); ?>');"><?php echo esc_html__("Change Let's Encrypt Account Key", 'auto-install-free-ssl'); ?></a> (<?php echo esc_html__("optional", 'auto-install-free-ssl'); ?>)
                    </p>
                    
                    <p>
                    	<br /><a href="<?php menu_page_url('aifs_revoke_ssl_certificate'); ?>" class="page-title-action button-primary"><?php echo esc_html__("Revoke SSL Certificate", 'auto-install-free-ssl'); ?></a> (<?php echo esc_html__("optional", 'auto-install-free-ssl'); ?>)
                    </p>
                    <?php
                    }
                    
                }
                ?>        
        		</td>   
        		        		
        		<?php 
        		 
        		      if($display_all || $ssl_issued_to_this_domain){
        		 ?>
        		      <td class="card block-body">
        		      
        		     <?php if(!get_option('aifs_force_ssl')){ ?>
        		
        			<h3 class="block-title">
        				<?= $step_number." ".esc_html__("Activate Force HTTPS", 'auto-install-free-ssl') ?>
        			</h3> 
        			
        			<?php if(($display_all || !get_option('aifs_ssl_installed_on_this_website')) && !is_ssl()){ ?>
        			
            			<p style="color: red; font-weight: bold;">
            				<?= esc_html__("Do this only if you are sure that an SSL certificate has been installed on this website.", 'auto-install-free-ssl') ?>
            			</p>
        			<?php } ?>
        			
        				<p><?= esc_html__("To remove the mixed content warning and see a padlock in the browser's address bar, you need to click the button below (only once). This will activate force SSL and all your website resources will load over HTTPS.", 'auto-install-free-ssl') ?></p>
						
						<?php if(!is_ssl()){ ?>
						
							<p><?= esc_html__("Clicking this button will immediately force your website to load over HTTPS and may prompt you to login again.", 'auto-install-free-ssl') ?></p>
												
							<p><strong><?= esc_html__("WARNING", 'auto-install-free-ssl') ?>:</strong> <?= esc_html__("If the SSL certificate has not been installed properly, clicking this button may cause issues accessing the website. So, please click this link first:", 'auto-install-free-ssl') ?> <a href="https://<?= $site_url ?>" target="_blank">https://<?= $site_url ?></a>. <?= esc_html__("If you see your website is loading with a mixed content warning and no padlock, but you see HTTPS in the address bar, that's okay for now. Please go ahead and click the button below.", 'auto-install-free-ssl') ?></p>
						
						<?php } ?>

						<p><?= esc_html__("If you face issues after clicking this button, please revert to HTTP.", 'auto-install-free-ssl') ?>
						<strong><?= esc_html__("Please don't worry, as soon as you click the button, we'll send you an automated email with a link. If you need to revert to HTTP, simply click that link.", 'auto-install-free-ssl') ?></strong>
						<?= esc_html__("If you don't find that email in your inbox, please don't forget to check your spam folder.", 'auto-install-free-ssl') ?></p>
						<p><?= esc_html__("But if the issue persists,", 'auto-install-free-ssl') ?>
						<a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#reverthttp" target="_blank"><?= esc_html__("click here", 'auto-install-free-ssl') ?></a>
						<?= esc_html__("for documentation on more options on how to revert to HTTP.", 'auto-install-free-ssl') ?></p>
        		
        				<?php 
        				if (isset($all_domains) && is_array($all_domains) && count($all_domains) > 1) {
        				    echo "<span style='color: #46b450;'>" . esc_html__("NOTE: Clicking this button activates force HTTPS on this website only. ", 'auto-install-free-ssl') . "</span>";
        				    
        				    echo esc_html__("You have generated SSL certificates for other websites using this installation. But for force HTTPS you need to install this plugin in other websites too (if they are made with WordPress) and click the same button. No need to do basic settings etc other configuration there.", 'auto-install-free-ssl');
        				}
        				
        				
        				  $forcessl = new ForceSSL();
        				  echo $forcessl->force_ssl_form(1);        				  
        		      }        		    
        		    else{ ?>
        		        
        		        <h3 class="block-title">
        					<?= esc_html__("Optional: Deactivate Force HTTPS", 'auto-install-free-ssl') ?>
        				</h3>
        				
        				<p><?= esc_html__("If force HTTPS is causing issues with your website, you may click the button below to Deactivate the force HTTPS feature and revert to HTTP. After you fix the SSL issues, you may activate force HTTPS again.", 'auto-install-free-ssl') ?></p>
        		        
        		        <?php
        		        /* Display revert button */
        		        
        		        $forcessl = new ForceSSL();
        		        echo $forcessl->force_ssl_form(0);
        		        
        		    }?>
        		    </td>
        		   <?php 
        		      }
        		 ////////} 
        		 ?>
        		         		
        	</tr>
        </table>
        
        <!-- Video guide -->
            <table>
        	<tr>
                    <td width="50%" style="margin-top: 2%;">
                        <h3>How to Configure this Plugin and Set Up Automation</h3>
                            <p style="color: green;">to Get and Install Free SSL Certificate for WordPress Website</p>

                            <iframe width="500" height="281" src="https://www.youtube.com/embed/tYIOHsuY-HE?start=70" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </td>
                 
        	<?php if(isset($app_settings['is_cpanel']) && $app_settings['is_cpanel'] && ((isset($all_domains) && count($all_domains) > 0) || !defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70100)){ ?>
          	    <td width="50%" style="margin-top: 2%;">                         
                            <h3>How to Install Free SSL Certificate on cPanel Shared Hosting</h3>
                            <p style="color: green;">N.B.: If the SSL was auto-installed on your website, you don't need to install SSL manually.</p>

                            <iframe width="500" height="281" src="https://www.youtube.com/embed/7M7Rufy7kBg" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

                    </td>
            <?php } ?>
                </tr>
            </table>   
        <!-- Powered by -->    		
    		<?php 
    		  if($version_base === 5){
    		      $style = 'class="header-footer"';
    		  }
    		  else{
    		      $style = 'id="message" class="updated below-h2 header-footer"';
    		  }
    		
    		?>
    		
    		<div <?= $style; ?> style="margin-top: 2%;">
              	<p>
              		<?php echo esc_html__("Need help", 'auto-install-free-ssl'); ?>? <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#help" target="_blank">Click here!</a> <span style="margin-left: 15%;"><?php echo esc_html__("For documentation", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#documentation" target="_blank">click here</a>.</span> <span style="margin-left: 15%;"><?php echo esc_html__("To report a bug", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/ap/contact-us" target="_blank">click here</a>.</span>
              	</p>
          	</div> <!-- End Powered by -->
          	
          	
        </div>
        <?php
    }
}
