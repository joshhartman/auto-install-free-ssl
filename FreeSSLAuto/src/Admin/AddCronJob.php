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
 * Add the cron job
 *
 */
class AddCronJob
{
   
    /**
     * Start up
     */
    public function __construct()
    {
        if (! defined('ABSPATH')) {
            die('Nothing with direct access!');
        }
         
        $this->app_settings = aifs_get_app_settings();
        
        $this->app_cron_path = str_replace("FreeSSLAuto/src/Admin", "", __DIR__)."cron.php";
        
        //hook if the cPanel settings or domain information exist
        if (isset($this->app_settings['cpanel_host']) || isset($this->app_settings['all_domains'])) {
            add_action('admin_menu', array( $this, 'add_cron_job_menu' ));
            add_action('admin_init', array( $this, 'add_cron_job_page_init' ));
            add_action('admin_init', array( $this, 'do_output_buffer' )); //required for successful redirect
        }
    }
    
    
    /**
     * Add the sub menu
     */
    public function add_cron_job_menu()
    {
        add_submenu_page('auto_install_free_ssl', esc_html__("Add Cron Job Page", 'auto-install-free-ssl'), esc_html__("Add Cron Job", 'auto-install-free-ssl'), 'manage_options', 'aifs_add_cron_job', array( $this, 'add_cron_job_admin_page' ));
    }
       
    
    /**
     * Create the page
     */
    public function add_cron_job_admin_page()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
            exit;
        }
        
        echo '<div class="wrap">';
        
        echo '<h1>'. esc_html__("Add Cron Job", 'auto-install-free-ssl'). '</h1>';
        echo '<h3>'. esc_html__("Auto-Install Free SSL", 'auto-install-free-ssl'). '</h3>';
        
        echo '<p>'. esc_html__("We need to run a cron job once every day for complete automation.", 'auto-install-free-ssl'). '</p>';
        
        
        //Process the data when form is submitted
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (\function_exists('shell_exec') && \function_exists('exec')) {
                
                //START create cron job
                
                //cron initialization
                $output = shell_exec('crontab -l');
                
                $cron_file = __DIR__.'/crontab.txt';
                
                $add_new_cron_base = "0 0 * * * php -q {$this->app_cron_path}";
                
                $cron_notify = isset($_POST['add_cron_job_auto_install_free_ssl']['cron_notify']) ? strtolower(sanitize_text_field($_POST['add_cron_job_auto_install_free_ssl']['cron_notify'])) : null;
                
                if ($cron_notify === "on") {
                    $email = $this->app_settings['admin_email'][0];
                    
                    //check if $output contain "MAILTO="
                    if (false === strpos($output, 'MAILTO=')) {
                        //$output doesn't contain it. add 'MAILTO="user@email.com"' at beginning of $output
                        
                        $output = 'MAILTO="'.$email.'"\n'.$output;
                    }
                    
                    $add_new_cron = $add_new_cron_base;
                } else {
                    //notification not required
                    //add  >/dev/null 2>&1 at the end of the cron job
                    $add_new_cron = $add_new_cron_base.' >/dev/null 2>&1';
                }
                
                //check if the cron job was already added
                if (false === strpos($output, $this->app_cron_path)) {
                    $add_cron = trim($output.$add_new_cron);
                    
                    if (file_put_contents($cron_file, $add_cron.PHP_EOL)) {
                        $output = [];
                        
                        //$return_var = 1 means error. $return_var = 0 means success.
                        exec("crontab ${cron_file}", $output, $return_var);
                        
                        if (1 === $return_var) {
                            $error_message = esc_html__("Sorry, the cron job was not added due to an error. Please try again or add cron job by log into your cPanel or web hosting control panel.", 'auto-install-free-ssl'). '<br />'.implode('<br />', $output);
                            ;
                        } elseif (0 === $return_var) {
                            //Success, so remove the default WP cron
                            /* if (wp_next_scheduled('auto_install_free_ssl_daily_event')) {
                                wp_clear_scheduled_hook('auto_install_free_ssl_daily_event');
                            } */
                            
                            unlink($cron_file);
                            
                            //redirect
                            wp_redirect(menu_page_url('auto_install_free_ssl').'&settings-updated=true', 301);
                            exit;
                        }
                    } else {
                        $error_message = esc_html__("Sorry, the cron job was not added. Please try again or add cron job by log into your cPanel or web hosting control panel.", 'auto-install-free-ssl');
                    }
                } else {
                    $text = esc_html__("The cron job was added already.", 'auto-install-free-ssl');
                
                    $message = <<<SUCCESS
            <div class="notice notice-success is-dismissible">
              	<p>
              	 ${text}
              	</p>
          	</div>
SUCCESS;
                    echo $message;
                }
            } else {
                //shell_exec and exec do not exist
            
                $disabled_functions = !\function_exists('shell_exec') ? "'shell_exec'," : null;
            
                if ($disabled_functions === null) {
                    $disabled_functions = !\function_exists('exec') ? "'exec'" : null;
                } else {
                    $disabled_functions .= !\function_exists('exec') ? " and 'exec'" : null;
                }
                        
                $error_message = sprintf(esc_html__("Your web hosting account doesn't have PHP function %s enabled. So the this option is not working. Please login to your cPanel / web hosting control panel and set up the cron job there. This is a one-time manual work.", 'auto-install-free-ssl'), $disabled_functions). "</strong></span>";
            }
        
        
            if (isset($error_message)) {
                $error_message = <<<ERROR
            <div class="notice notice-error is-dismissible">
              	<p>
              		$error_message
              	</p>
          	</div>
ERROR;
            
                echo $error_message;
            }
        
            //END create cron job
        }
    
    
        //GET request section
        
        $common_text = esc_html__("Daily Cron job will issue/renew the SSL certificates automatically.", 'auto-install-free-ssl');
    
        $common_text .= ' <strong>'.esc_html__("If your cPanel have SSL installation feature enabled, this app will install the issued SSL automatically.", 'auto-install-free-ssl').'</strong>';
    
        $common_text .= ' '.esc_html__("That means complete automation of your free SSL certificates even in shared hosting cPanel.", 'auto-install-free-ssl');
    
        $common_text .= ' '.esc_html__("If your cPanel doesn't have SSL installation feature, please contact your web hosting provider for SSL installation.", 'auto-install-free-ssl');
    
        $common_text .= ' '.esc_html__("You will receive an automated email for issue/renewal of SSL.", 'auto-install-free-ssl');
    
        $common_text .= ' '.esc_html__("The email will contain the path to SSL files.", 'auto-install-free-ssl');
    
        $common_text .= ' '.esc_html__("You can copy-paste path of SSL files and send it to your web hosting provider for installation.", 'auto-install-free-ssl');
    
    
    
        //check whether shell_exec and exec function is enabled
        //If not enabled, don't display the form.
        //instead, display tips - how to add the cron
    
        if (\function_exists('shell_exec') && \function_exists('exec')) {
        
        //cron initialization
            $output = shell_exec('crontab -l');
        
            if (false === strpos($output, $this->app_cron_path)) {
                $cron_button_text = esc_html__("Add the Cron Job", 'auto-install-free-ssl');
            
                echo "<strong>".sprintf(esc_html__("Please click the '%s' button below to add the Cron Job.", 'auto-install-free-ssl'), $cron_button_text)."</strong><br /><br />";
            
                echo esc_html__("This is a quick option to add a cron job which will run this app every day at 12:00 a.m. midnight.", 'auto-install-free-ssl');
            
                echo ' '. esc_html__("If you want the cron job to run at a different time, please use the cron job option of your cPanel / web hosting control panel instead.", 'auto-install-free-ssl');
                echo ' '. esc_html__("Please remember to set the cron job once every day.", 'auto-install-free-ssl');
                echo ' '. esc_html__("Command of the cron job:", 'auto-install-free-ssl');
            
                echo "<pre><strong>php -q $this->app_cron_path</strong></pre><br />";
            
                echo $common_text;
            
                echo '<form method="post" action="">';
                
                settings_fields('add_cron_job_aifs_group');
                do_settings_sections('add_cron_job_aifs_admin');
                submit_button($cron_button_text);
                
                echo '</form>';
            } else {
                //The cron job was added already
                echo "<span style='color: green;'><strong>".esc_html__("Wonderful! The cron job was added already. You don't need to do anything on this page.", 'auto-install-free-ssl')."</strong></span><br /><br />";
            }
            
            $text_for_video = "If 'Add the Cron Job' button fails to add the cron job, please follow the video guide below.";
            
        } else {
            //Either shell_exec or exec is not enabled
            echo "<span style='color: green;'>". esc_html__("Please follow the video guide below,", 'auto-install-free-ssl').'</span>';
            
            echo esc_html__(" log in to your cPanel / web hosting control panel and Add a Daily Cron Job that will run this app once every day.", 'auto-install-free-ssl').'<br />';
        
            echo esc_html__("Command of the cron job in red", 'auto-install-free-ssl');
        
            echo ": <pre style='color: red; font-weight: bold;'>php -q $this->app_cron_path</pre><br />";
        
            echo $common_text;
        
            $disabled_functions = !\function_exists('shell_exec') ? "'shell_exec'," : null;
        
            if ($disabled_functions === null) {
                $disabled_functions = !\function_exists('exec') ? "'exec'" : null;
            } else {
                $disabled_functions .= !\function_exists('exec') ? " and 'exec'" : null;
            }
        
            echo "<br /><br /><span><strong>";
        
            echo esc_html__("This app has a quick option to add a cron job with one click, which will run this app every day at 12:00 a.m. midnight.", 'auto-install-free-ssl');
        
            echo ' '. sprintf(esc_html__("But your web hosting account doesn't have PHP function %s enabled. So please login to your cPanel / web hosting control panel and set up the cron job there.", 'auto-install-free-ssl'), $disabled_functions);
        
            echo ' '. esc_html__("This is a one-time manual work.", 'auto-install-free-ssl').'</strong></span><br /><br />';
            
            $text_for_video = null;
        }
        
        echo '<a href="'.menu_page_url('auto_install_free_ssl', false).'" class="page-title-action button">'.esc_html__("Go Back", 'auto-install-free-ssl').'</a>';
        
        echo '</div>'; ?> 
        
        <div style="margin-top: 2%;">
        	<?php if(!is_null($text_for_video)){ ?>
        		<p style="color: red;"><?= $text_for_video ?></p>
        	<?php } ?>
        	<h3>How to add a Cron Job in a minute on cPanel shared hosting</h3>
        	<iframe width="560" height="315" src="https://www.youtube.com/embed/AEwcWBWohXs" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        
            <!-- Powered by -->
            <br />
            <div class="header-footer">
              	<p>             
              		<?php echo esc_html__("Need help", 'auto-install-free-ssl'); ?>? <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#help" target="_blank">Click here!</a> <span style="margin-left: 15%;"><?php echo esc_html__("For documentation", 'auto-install-free-ssl'); ?>, <a href="https://freessl.tech/free-ssl-certificate-for-wordpress-website/#documentation" target="_blank">click here</a>.</span>
              	</p>          	
          	</div> <!-- End Powered by -->
        
        <?php
    }
    
    /**
     * Register and add settings
     */
    public function add_cron_job_page_init()
    {
        register_setting(
            'add_cron_job_aifs_group', // Option group
            'add_cron_job_auto_install_free_ssl', // Option name
            array( $this, 'sanitize' ) // Sanitize
            );
        
        add_settings_section(
            'add_cron_job_section_id', // Section ID
            '', //
            array( $this, 'print_section_info' ), // Callback
            'add_cron_job_aifs_admin' // Page
            );
        
        add_settings_field(
            'cron_notify',
            esc_html__("Send me cron output every time the cron job runs (recommended)", 'auto-install-free-ssl'),
            array( $this, 'cron_notify_callback' ),
            'add_cron_job_aifs_admin',
            'add_cron_job_section_id'
            );
    }
    
    /**
     * Sanitize each setting field as needed - here this actually has no function
     *
     * @param array $input (Contains all settings fields as array keys)
     */
    public function sanitize($input)
    {
        $new_input = array();
        
        $new_input['cron_notify'] = sanitize_text_field($input['cron_notify']);
        
        return $new_input;
    }
    
    /**
     * Print the Section text
     */
    public function print_section_info()
    {
    }
    
    
    /**
     * cron_notify
     */
    public function cron_notify_callback()
    {
        echo '<input type="checkbox" id="cron_notify" name="add_cron_job_auto_install_free_ssl[cron_notify]" checked />';
        
        echo '<label for="cron_notify">'.esc_html__("to this email id", 'auto-install-free-ssl') . ': '. $this->app_settings['admin_email'][0] . '</label><br /><br />';
    }
    
    /**
     * required for successful redirect
     */
    public function do_output_buffer()
    {
        ob_start();
    }
}
