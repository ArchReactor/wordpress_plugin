<?php
/**
 * Plugin Name: ArchReactor
 * Plugin URI:  https://archreactor,org
 * Description: ArchReactor membership helpers
 * Version:     1.0.0
 * Author:      Chris Weiss
 * Author URI:  https://weissmaker.com
 * License:     GPL2
 */

require_once 'adminpage.php';
require_once 'formhelp.php';
require_once 'membership.php';
require_once 'paypal.php';
require_once 'rostercodes.php';
require_once 'rostermanager.php';

add_action( 'plugins_loaded', 'archreactor_init' );
add_action('wp_enqueue_scripts', 'archreactor_tabs_assets');

function archreactor_init(){
    ArchReactorFormHelp::init();
    ArchReactorMembership::init();
    ArchReactorPaypal::init();
    ArchReactorRoster::init();
    ArchReactorRosterManager::init();
    ArchReactorAdmin::init();
}

function archreactor_tabs_assets() {
    global $wp_scripts;
    $ui_version = $wp_scripts->registered['jquery-ui-core']->ver;
    wp_enqueue_script('jquery-ui-tabs');

    // Load standard jQuery UI styling from a public CDN
    wp_enqueue_style(
        'jquery-ui-cdn-styles',
        "//code.jquery.com/ui/{$ui_version}/themes/base/jquery-ui.css",
        array(),
        $ui_version
    );

    // Custom inline script to initialize the tabs container on the front end
    $custom_js = "
        jQuery(document).ready(function($) {
            if ($('.archreactor-tabs-container').length) {
                var tabs = $('.archreactor-tabs-container').tabs();
            }
        });
    ";
    wp_add_inline_script('jquery-ui-tabs', $custom_js);
}

