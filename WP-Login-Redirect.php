<?php
/**
 * @package WP-Login-Redirect
 */
/*
Plugin Name: Login Redirect
Plugin URI: https://github.com/ggordan/WP-Login-Redirect
Description: A simple plugin that redirects the user to a specified page once they log in, by default, the site homepage.
Version: 0.5
Author: Gordan Grasarevic
Author URI: http://ggordan.com
License: GPLv2 (or later)
*/

$plugin_version = 0.5;
$plugin_name = 'Login Redirect';
$plugin_name_settings = $plugin_name . ' Settings';

/*
 * Initialise the plugin. Check if an option exists for login redirect urls. If
 * not, add the default 'siteurl'.
 */
function init_login_redirect() {
    
    define('LOGIN_REDIRECT_E', 'Login Redirect');
    define('LOGIN_REDIRECT_SETTINGS_E', 'Login Redirect Settings');
    define('LOGIN_REDIRECT_OPTION', 'login_redirect_e');
    
    //delete_option(LOGIN_REDIRECT_OPTION);
    
    /*
     * Create defaults if the plugin has just been installed
     */
    if ( !get_option( LOGIN_REDIRECT_OPTION )) {
        global $wp_roles;
        $role_redirect_defaults = array();
        
        $role_redirect_defaults['global'] = array(
            'global_redirect_url' => '',
            'global_redirect_disabled' => 'yes',
            'disable_all' => null
        ) ;
        
        foreach ($wp_roles->role_names as $ident => $role) {
            
            $role_redirect_defaults['redirects'][$ident]['name'] = $role;
            $role_redirect_defaults['redirects'][$ident]['url'] = '';
            $role_redirect_defaults['redirects'][$ident]['disabled'] = null;
        }
        
        add_option ( LOGIN_REDIRECT_OPTION, json_encode($role_redirect_defaults) );
    }
    
   /*
    * Register the required style and script
    */
    wp_register_script('login-redirect-script', plugin_dir_url(__FILE__) . 'js/login_redirect.js', array('jquery', 'jquery-ui-autocomplete', 'jquery-ui-accordion'));
    wp_register_style('login-redirect-style', plugin_dir_url(__FILE__) . 'css/main.css');
    
} add_action('init', 'init_login_redirect');


/*
 * Function to add a menu item to the admin panel
 */
function login_redirect_menu() {
    add_options_page( 
            LOGIN_REDIRECT_SETTINGS_E, 
            LOGIN_REDIRECT_E, 
            'manage_options', 
            'login-redirect', 
            'login_redirect_options' 
            );
    
} add_action( 'admin_menu', 'login_redirect_menu' );


/*
 * Function that adds a menu options page where users can control the redirect 
 * urls.
 */
function login_redirect_options() {
    if ( !current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

    global $wp_roles;
    
    $site_roles = $wp_roles->role_names;
    $current_roles = json_decode(get_option( LOGIN_REDIRECT_OPTION ));
    
    // HTML FOR Roles
    $concatenator = '';
    $current_redirect_wrap = '<div class="redir_wrap">%s</div>';
    $current_redirect = '<p class="current_redirect"><strong>%ss</strong> are currently redirected to <code><a href="%s">%s</a></code></p>';
    
    // enqueue the required stylesheet, and javascript file
    wp_enqueue_style( 'login-redirect-style' );
    wp_enqueue_script( 'login-redirect-script' );
    
    /*
     * Check if the form has been submitted
     */
    if ($_REQUEST['save_login_redirect']) {
       
        // Process redirect urls
        $insert_data = array();

        // GLOBAL REDIRECT SETTINGS
        $global_redirect_url = $_REQUEST['global_redirect'];
        $global_redirect_disabled = (isset($_REQUEST['global_disable'])) ? 'yes' : null;
        $disable_all = (isset($_REQUEST['disable_all'])) ? 'yes' : null;

        $insert_data['global'] = array(
            'global_redirect_url' => $global_redirect_url,
            'global_redirect_disabled' => $global_redirect_disabled,
            'disable_all' => $disable_all
        );
        
        foreach ($_REQUEST['redirect'] as $key => $data) {
            
            $disabled = (isset($data['disabled'])) ? 'yes' : null;
            
            $insert_data['redirects'][$key] = array(
                'name'  => $site_roles[$key],
                'url'   => $data['url'],
                'disabled' => $disabled
            );
            
            echo get_redirect_url($data['inner'], $data['url'], true);
            
            if (isset($data['inner'])) {
                if (get_redirect_url($data['inner'], $data['url'])) {
                    
                    $insert_data['redirects'][$key]['inner'] = $data['inner'];
                }
            }
        }
        
        if (isset($_REQUEST['global_redirect_inner'])) {
            if (get_redirect_url($_REQUEST['global_redirect_inner'], $global_redirect_url)) {
                
                $insert_data['global']['global_redirect_inner'] = $_REQUEST['global_redirect_inner'];
            }
        }
        
        update_option(LOGIN_REDIRECT_OPTION, json_encode($insert_data));
    }
    
    // Check if any new roles have been added, add a new identifier to show user
    if (count ($site_roles) > count( (array) $current_roles->redirects)) {
        echo '<p class="newroles">' . __('It seems like there are some new roles on your site. You can now customize their url redirects. (Click to dismiss)') .'</p>';
        
        foreach ($site_roles as $ident => $role) {
            if (!in_array($ident, array_keys((array)$current_roles->redirects))) {
                $current_roles->redirects->$ident->new = true;
                $current_roles->redirects->$ident->name = $role;
                $current_roles->redirects->$ident->url = '';
            }
        }
    }
    
    if(isset($insert_data)) {
        $current_roles = json_decode(json_encode($insert_data));
    }
    
    $disable_all = (strlen($current_roles->global->disable_all)) ? 'checked="checked"' : null;
    $redirect_disable = (strlen($current_roles->global->global_redirect_disabled)) ? 'checked="checked"' : null;

    $global_redirect_inner = null;
    // Check for global redirect hidden value
    if (isset($current_roles->global->global_redirect_inner)) {
        
        $global_redirect_inner = '<input type="hidden" value="' . $current_roles->global->global_redirect_inner . '" id="global_redirect_hidden"  />';
    }
    
    ?>
    
    <div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
        <h2 class="a"> <?php echo LOGIN_REDIRECT_SETTINGS_E; ?> </h2>
        <p class="desc"> <?php _e('This plugin controls the login redirects for all roles available on your site. If the plugin is enabled, once a user logs in they will be redirect to the'); ?> <code><?php echo get_option( 'siteurl' ); ?></code> <?php _e('by default, or a custom page specified by you.'); ?></p>
        <h3> Instructions: </h3>
        <ul>
            <li><strong>Custom URLS</strong>: To set the login redirect for a specific role, simply type in the URL.</li>
            <li><strong>Posts/Pages</strong>: To redirect users to a specific page, or blog post on your site, simply start typing the name of the page, and the plugin will suggest pages from your site.</li>
        </ul>
    </div>
    <form method="post" action="">
    <div class="role_redirect global">
        <p> <strong> Disable redirects for all roles.</strong> <span>(You can also disable redirects for any roles.)</span> </p>
        <p> <input type="checkbox" name="disable_all" <?php echo $disable_all; ?> /> Disable  all redirects </p>
        <p> <strong>Global Redirect</strong> <span>(Redirect all roles to:)</span></p>
        <p>
            <input class="redirect_input" maxlength="255" size="35" id="global_redirect" name="global_redirect" data-apn="global_redirect" value="<?php echo $current_roles->global->global_redirect_url; ?>" placeholder="Start typing..." />
            <?php echo $global_redirect_inner; ?>
        </p>
        <p> <input type="checkbox" name="global_disable" <?php echo $redirect_disable; ?> /> Disable the global redirect. Alternatively, leave the global redirect field blank.  </p>
    </div>
    <div class="redir_settings"> 
        <?php 
        foreach($current_roles->redirects as $ident => $role_data): 
        $new = (isset($role_data->new)) ? 'new': '';  
        $disabled = (strlen($role_data->disabled)) ? 'checked="checked"' : null;
        $inner = (isset($role_data->inner)) ? '<input type="hidden" id="'.$ident.'_hidden" name="redirect['. $ident .'][inner]" value="'. $role_data->inner .'" />' : null;
        ?>
        <h3 class="<?php echo $new; ?> acct"><a href="#"> <?php echo $role_data->name; ?> Redirect</a> </h3>
        <div class="role_redirect <?php echo $new; ?>">
            <p> Redirect <?php printf("<strong>" . __("%s") . "</strong>", $role_data->name); ?> to:</p>
            <p>
                <input class="redirect_input  <?php echo $new; ?>" id="<?php echo $ident; ?>" maxlength="255" size="35" name="redirect[<?php echo $ident; ?>][url]" data-apn="redirect[<?php echo $ident; ?>]" value="<?php echo $role_data->url; ?>" placeholder="Start typing..." />
                <?php echo $inner; ?>
            </p>
            <p>
                <input type="checkbox"  name="redirect[<?php echo $ident; ?>][disabled]" <?php echo $disabled; ?> /> Disable custom redirects for <?php printf(__("%ss"), strtolower($role_data->name)); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php

    /*
     * Display current role redirects
     */
    foreach($current_roles->redirects as $role_id => $role) {
        
        if (isset($role->new) || (!strlen($role->disabled)) ) {
            if ( strlen($role->url) )
                $concatenator .= sprintf($current_redirect, $role->name, get_redirect_url ($role->inner, $role->url, false), $role->url);
        }
        
    } printf($current_redirect_wrap, $concatenator);   
    
    ?>
    <div class="submitbutton">
        <input type="submit" id="submit" class="button-primary" name="save_login_redirect" value=" Save Changes "/>
    </div>
    </form>
    <?php
}


/*
 * Get page url by title 
 */
function get_redirect_url($ID, $title, $blank = false) {
    global $wpdb;
    $count = $wpdb->get_results("SELECT * FROM " . $wpdb->posts . " WHERE ID='". $ID ."' AND post_title='". $title ."' LIMIT 2" );
    //$count = $wpdb->get_results("SELECT * FROM " . $wpdb->posts . "WHERE ID='1' LIMIT 2" );
    
    if (count($count) != 1) {
        return false;
    } else {
        if ($blank) {
            return get_permalink($ID);
        } else {
            return true;
        }
    }
}

/*
 * Ajax
 */
function suggest_redirect_callback() {

    global $wpdb;
    
    // clean the query string
    $query = $wpdb->escape( $_REQUEST['term'] );
    $response = array();
    $results = (array) ($wpdb->get_results("SELECT post_title, ID FROM " . $wpdb->posts . " WHERE post_title LIKE '%".$query."%' AND post_status = 'publish'"));

    foreach ( $results as $key ) {
        $response[] = array(
            'label' => $key->post_title,
            'value' => $key->post_title,
            'id' => $key->ID
        );
    }
    
    echo json_encode($response);

    die(); // this is required to return a proper result
    
} add_action('wp_ajax_suggest_redirect', 'suggest_redirect_callback');


/*
 * Function that handles the redirect after login
 * 
 */
function login_redirect_to($user_login, $user) {
    global $user;
    $current_redirects = json_decode(get_option( LOGIN_REDIRECT_OPTION ));
    
    // Check if all redireects have been disabled
    if(!strlen($current_redirects->disable_all))  {
        if ( count( $user->roles )) {
            if (in_array('subscriber', $user->roles) && !in_array('administrator', $user->roles)) {
                $redirect_to = get_option( 'login_redirect_e' );
                wp_redirect( $redirect_to, $status = 302 );
            }
        }
    }
    
} add_action( 'wp_login', 'login_redirect_to', 10, 2 );

?>