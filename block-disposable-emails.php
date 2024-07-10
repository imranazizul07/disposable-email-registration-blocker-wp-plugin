<?php
/*
Plugin Name: Block Disposable Emails
Description: Blocks user registration using temporary disposable emails in all registration options including Ultimate Member, WooCommerce, WP Default login, and any plugin using the user_register hook.
Version: 1.0
Author: Imran Md Azizul Islam
Author URI: https://buymeacoffee.com/imranazizul07
Requires PHP: 5.6
Requires at least: 4.7
Tested up to: 6.5.5
Text Domain: block-disposable-emails
Domain Path: /languages
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin textdomain for translations
function bde_load_textdomain() {
    load_plugin_textdomain('block-disposable-emails', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'bde_load_textdomain');

require_once plugin_dir_path(__FILE__) . 'class-bde-database.php';
require_once plugin_dir_path(__FILE__) . 'class-bde-settings.php';
require_once plugin_dir_path(__FILE__) . 'class-bde-email-check.php';

class Block_Disposable_Emails {

    public function __construct() {
        register_activation_hook(__FILE__, array('BDE_Database', 'create_table'));
        new BDE_Email_Check();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('bde-styles', plugin_dir_url(__FILE__) . 'assets/bde-styles.css', array(), '1.0', 'all');
    }

    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=block_disposable_emails') . '">' . __('Settings', 'block-disposable-emails') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new Block_Disposable_Emails();