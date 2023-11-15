<?php
/*
Plugin Name: Test Content Generator
Version: 0.4.1
Description: Intended for plugin and theme developers, this lets you quickly generate a test site full of random users, posts, comments, tags and images. Go to Tools-><strong>Content Generator</strong> to use, or call from the command line with WP_CLI.
Author: Keith Drakard
Author URI: https://drakard.com/
*/


if (! defined('WPINC')) die;


class TestContentGenerator {
    
    private $default_tab = 'posts';
    
    public function __construct() {
        load_plugin_textdomain('TestContentGenerator', false, __DIR__.'/languages');
        
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_page'));
        
        if (defined('WP_CLI') and WP_CLI) {
            require_once __DIR__.'/library/wp-cli-commands.php';
        }
    }
    
    
    /**** plugin init **********************************/
    
    public function deactivation_hook() {
        // tidy up after yourself
        foreach (array_keys($this->tabs) as $setting) {
            delete_option('TCG_'.$setting);
        }
    }
    
    public function init() {
        $this->tabs = [
            'users'      => __('Users', 'TestContentGenerator'),
            'custom'     => __('Custom', 'TestContentGenerator'),
            'taxonomies' => __('Taxonomies', 'TestContentGenerator'),
            'images'     => __('Images', 'TestContentGenerator'),
            'posts'      => __('Posts', 'TestContentGenerator'),
            'comments'   => __('Comments', 'TestContentGenerator'),
        ];
        $this->buttons = [
            'posts'      => __('Add Test Posts', 'TestContentGenerator'),
            'images'     => __('Add Test Images', 'TestContentGenerator'),
            'comments'   => __('Add Test Comments', 'TestContentGenerator'),
            'users'      => __('Add Test Users', 'TestContentGenerator'),
            'taxonomies' => __('Add Test Terms', 'TestContentGenerator'),
            'custom'     => __('Save Custom Settings', 'TestContentGenerator'),
        ];
        
        // check we actually want the custom bits
        $custom_options = get_option('TCG_Custom');
        $enable_cpt = (isset($custom_options['enable_cpt'])) ? (bool) $custom_options['enable_cpt'] : false;
        $enable_cat = (isset($custom_options['enable_cat'])) ? (bool) $custom_options['enable_cat'] : false;
        $enable_tag = (isset($custom_options['enable_tag'])) ? (bool) $custom_options['enable_tag'] : false;
        
        if ($enable_cpt or $enable_cat or $enable_tag) {
            require_once 'library/tab_custom.php';
            if ($enable_cpt) TCG_Custom::register_cpt();
            if ($enable_cat) TCG_Custom::register_cat();
            if ($enable_tag) TCG_Custom::register_tag();
        }
    }
    
    
    /**** admin for the plugin *************************/
    
    public function admin_init() {
        // I'm not using ajax in this plugin, so don't keep reloading and reinitialising the classes just because the tab's been left open
        if (defined('DOING_AJAX') and DOING_AJAX) return;
        
        // load each of our separate (mostly so this file isn't 1000+ lines long) tab classes, each of which registers their own settings
        foreach (array_keys($this->tabs) as $tab) {
            require_once __DIR__.'/library/tab_'.$tab.'.php';
            $class = 'TCG_'.ucfirst($tab);
            $instance = new $class();
        }
    }
    
    public function add_admin_page() {
        add_management_page(__('Test Content Generator', 'TestContentGenerator'), __('Content Generator', 'TestContentGenerator'), 'install_plugins', __CLASS__, array($this, 'admin_page_content'), -1);
    }

    public function admin_page_content() {
        echo '<div class="wrap"><h1>'.__('Test Content Generator', 'TestContentGenerator').'</h1>';
        
        $active_tab = (isset($_GET['tab']) and array_key_exists($_GET['tab'], $this->tabs)) ? $_GET['tab'] : $this->default_tab; //array_keys($this->tabs)[0];
        
        settings_errors();
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $tab => $title) {
            printf('<a href="?page=%s&tab=%s" class="nav-tab %s">%s</a>',
                   __CLASS__, $tab, ($tab == $active_tab) ? 'nav-tab-active' : '', $title);
        }
        echo '</h2>';

        echo '<form action="options.php" method="post">';
        do_settings_sections('TCG_'.ucfirst($active_tab));
        settings_fields('TCG_'.ucfirst($active_tab));
        submit_button($this->buttons[$active_tab]);
        echo '</form>';
        
        echo '</div>';
    }

    
}




$TestContentGenerator = new TestContentGenerator();
register_deactivation_hook(__FILE__, array($TestContentGenerator, 'deactivation_hook'));
