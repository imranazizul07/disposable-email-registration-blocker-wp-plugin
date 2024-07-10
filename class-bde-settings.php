<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BDE_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_post_bde_pull_domains', array($this, 'pull_domains_from_url'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Temporary Email SignUps Settings', 'block-disposable-emails'),
            __('Disposable Emails', 'block-disposable-emails'),
            'manage_options',
            'block_disposable_emails',
            array($this, 'options_page'),
            'dashicons-email-alt2'
        );
    }

    public function settings_init() {
        register_setting('bde_plugin', 'bde_disposable_domains', array($this, 'sanitize_domains'));

        add_settings_section(
            'bde_plugin_section',
            __('Update Temporary Email Domains List', 'block-disposable-emails'),
            array($this, 'settings_section_callback'),
            'bde_plugin'
        );

        add_settings_field(
            'bde_disposable_domains_field',
            __('Add or Remove Domains:', 'block-disposable-emails'),
            array($this, 'domains_field_render'),
            'bde_plugin',
            'bde_plugin_section'
        );
    }

    public function settings_section_callback() {
        echo __('Enter one domain per line or auto-pull domain list from online by clicking on the button below!', 'block-disposable-emails');
    }

    public function domains_field_render() {
        $domains = BDE_Database::get_domains();
        echo '<textarea name="bde_disposable_domains" rows="10" cols="50" class="large-text code">';
        echo esc_textarea(implode("\n", $domains));
        echo '</textarea>';
        echo '<p><a href="' . esc_url(admin_url('admin-post.php?action=bde_pull_domains')) . '" class="button">' . __('Pull Disposable Domain List Automatically', 'block-disposable-emails') . '</a></p>';
    }

    public function options_page() {
        ?>
        <form action="options.php" method="post">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
            settings_fields('bde_plugin');
            do_settings_sections('bde_plugin');
            submit_button();

            // Display settings errors below the "Save Changes" button
            $messages = get_settings_errors('bde_plugin_messages');
            $transient_message = get_transient('bde_pull_success');
            
            if (!empty($messages) || $transient_message) {
                echo '<div class="notice notice-success is-dismissible">';
                if (!empty($messages)) {
                    foreach ($messages as $message) {
                        echo '<p>' . esc_html($message['message']) . '</p>';
                    }
                }
                if ($transient_message) {
                    echo '<p>' . esc_html__('Disposable email domains list pulled and saved successfully!', 'block-disposable-emails') . '</p>';
                    delete_transient('bde_pull_success');
                }
                echo '</div>';
            }
            ?>
        </form>
        <div style="margin-top: 175px;">
            <p>
                <?php esc_html_e('Really helped you? You can consider buying me a cup of coffee or a pizza!', 'block-disposable-emails'); ?>
                <a href="https://buymeacoffee.com/imranazizul07" target="_blank">
                    <span class="dashicons dashicons-coffee" style="color: red; text-decoration: none;"></span>
                </a>
            </p>
        </div>
        <?php
    }

    public function sanitize_domains($input) {
        $domains = array_map('trim', explode("\n", $input));
        $domains = array_filter($domains);
        BDE_Database::save_domains($domains);

        add_settings_error('bde_plugin_messages', 'bde_plugin_message', __('Domains updated successfully!', 'block-disposable-emails'), 'updated');
        return implode("\n", $domains);
    }

    public function pull_domains_from_url() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $url = 'https://raw.githubusercontent.com/wesbos/burner-email-providers/master/emails.txt';
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            wp_die(esc_html__('Error pulling domains.', 'block-disposable-emails'));
        }

        $domains = explode("\n", wp_remote_retrieve_body($response));
        $domains = array_map('trim', $domains);
        $domains = array_filter($domains);

        $existing_domains = BDE_Database::get_domains();
        $all_domains = array_unique(array_merge($existing_domains, $domains));

        BDE_Database::save_domains($all_domains);
        set_transient('bde_pull_success', true, 30);
        wp_redirect(admin_url('admin.php?page=block_disposable_emails'));
        exit;
    }
}

new BDE_Settings();