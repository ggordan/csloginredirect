<?php

/*
Plugin Name: Login Redirect
Plugin URI: https://github.com/ggordan/WP-Login-Redirect
Description: A simple plugin that redirects the user to a specified page once they log in, by default, the site homepage.
Version: 0.5
Author: Gordan Grasarevic
Author URI: http://ggordan.com
License: GPLv2 (or later)
*/

// Check if the login_redirect option field is defined, and if not set it to the current site url
if ( !get_option( 'login_redirect_e' ) )
    add_option ( 'login_redirect_e', get_option( 'siteurl' ) );

/*
 * Function that handles the redirect after login
 */
function login_redirect_to($user_login, $user) {
    global $redirect_to;
    
    if ( count( $user->roles )) {
        if (in_array('subscriber', $user->roles) && !in_array('administrator', $user->roles)) {
            $redirect_to = get_option( 'login_redirect_e' );
            wp_redirect( $redirect_to, $status = 302 );
        }
    }
} add_action( 'wp_login', 'login_redirect_to', 10, 2 );

/*
 * Function to add a menu item to the admin panel
 */
function login_redirect_menu() {

    add_options_page( 'Subscriber Login Redirect Settings' , 'Subscriber Login Redirect' , 'manage_options', 'login-redirect', 'login_redirect_options' );
} add_action( 'admin_menu', 'login_redirect_menu' );

function login_redirect_options() {
    global $wp_roles;
    
    if ( !current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        
    // Check if the form has been submitted
    if ($_REQUEST['save_login_redirect']) {
        if ( strlen( $_REQUEST['redirect_url'] ) ) {
            update_option('login_redirect_e', $_REQUEST['redirect_url']);
        } else {
            update_option( 'login_redirect_e', get_option( 'siteurl' ));
        }
    }
    
    $html = <<<BODY
    <div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2> Subscriber Login Redirect Settings </h2>
    <p> This plugin controls the login redirect option. If the plugin is enabled, once a user logs in they will be redirect to the <code>%s</code> by default, or a custom page specified by you.</p>
    <p> If the input field is left blank once the changes are saved, the plugin will again default to your site url.
    <form method="post" action="%s">
    <p> Define the redirect url: <input id="redirect_url" maxlength="255" size="35" name="redirect_url" placeholder="%s" /> <input type="submit" id="submit" class="button-primary" name="save_login_redirect" value=" Save Changes "/></p>
</form>
    </div>
BODY;
    
    printf($html, get_option('siteurl'), $_SERVER['REQUEST_URI'], get_option('login_redirect_e'));
    
}

?>
