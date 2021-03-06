<?php

/**
 * Plugin Name: Fatal Safe
 * Description: Saves your ass when you're struck by a fatal error while editing a live theme in WordPress admin panel
 * Version: 1.0.0
 * Author: Hasin Hayder
 * Author URI: http://hasin.me
 * Plugin URI: https://github.com/hasinhayder/fatalsafe
 */
class FatalSafe
{
    public function __construct()
    {
        register_shutdown_function(array($this, 'hellJustFrozeOver'));
        add_action('admin_enqueue_scripts',array($this,'enqueueScripts'));
        add_action('wp_ajax_dismissfserror',array($this,'dismissFatalSafeErrorMessage'));

        $fatalErrorMessage = get_option('fatalsafe');
        if (trim($fatalErrorMessage) != '') {
            add_action('admin_notices', array($this, 'honkAtWill'));
        }
    }

    function dismissFatalSafeErrorMessage(){

        $nonce = $_POST['nonce'];
        if(wp_verify_nonce($nonce,'dismissfserror')){
            //alright, you shall pass
            update_option('fatalsafe','');
            echo "You shall pass";
            die();
        }

    }

    function enqueueScripts(){
        $jsPath = plugin_dir_url(__FILE__).'js/fatalsafe.js';
        wp_enqueue_script('fatalsafe-js',$jsPath,array('jquery'),null,true);

        $ajaxUrl = admin_url('admin-ajax.php');
        wp_localize_script('fatalsafe-js','fatalsafe',array('ajaxurl'=>$ajaxUrl));
    }

    function honkAtWill()
    {
        $fatalErrorMessage = get_option('fatalsafe');
        ?>
        <div class='notice error failsafe-error-msg is-dismissible'>
            <p>
                <?php echo $fatalErrorMessage; ?>
            </p>
            <input type='hidden' id='fatalsafe-nonce' value='<?php echo wp_create_nonce('dismissfserror');?>'/>
        </div>
        <?php
    }

    function hellJustFrozeOver()
    {
        $error = error_get_last();
        $currentTheme = wp_get_theme();
        if ($error) {
            $foundOneFromTwentySeries = false;
            $themes = wp_get_themes(array('errors' => false, 'allowed' => true, 'blog_id' => 0));

            foreach ($themes as $slug => $theme) {
                if (strpos($slug, 'twenty') !== false && $theme->get_stylesheet() != $currentTheme->get_stylesheet()) {
                    switch_theme($theme->get_stylesheet());
                    $foundOneFromTwentySeries = true;
                    break;
                }
            }

            if (!$foundOneFromTwentySeries) {
                //dang, no theme from twenty series was present
                foreach ($themes as $slug => $theme) {
                    if ($theme->get_stylesheet() != $currentTheme->get_stylesheet()) {
                        switch_theme($theme->get_stylesheet());
                        break;
                    }
                }
            }


            $fatalErrorMessage = "Your <b>{$currentTheme->Name}</b> theme threw a fatal error on <b>line #{$error['line']}</b> in {$error['file']}. The error message was: <b>{$error['message']}</b>. Please fix it.";
            update_option('fatalsafe', $fatalErrorMessage);
            echo "<h1>Don't Panic! Refresh This Page To Get Some Oxygen</h1>";
        }

    }
}

new FatalSafe();