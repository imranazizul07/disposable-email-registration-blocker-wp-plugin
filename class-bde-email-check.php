<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BDE_Email_Check {

    public function __construct() {
        // WordPress default registration
        add_action('register_post', array($this, 'check_email_domain'), 10, 3);
        add_filter('registration_errors', array($this, 'check_email_domain_wp'), 10, 3);

        // Ultimate Member registration
        if (function_exists('UM')) {
            add_action('um_submit_form_errors_hook__registration', array($this, 'ultimate_member_check_email_domain'), 10, 1);
        }

        // WooCommerce registration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_register_post', array($this, 'woocommerce_check_email_domain'), 10, 3);
        }

        // Custom registration forms (using hooks like 'user_register')
        add_action('user_register', array($this, 'check_email_domain_on_register'), 10, 1);
    }

    public function check_email_domain($sanitized_user_login, $user_email, $errors) {
        if ($this->is_disposable_email($user_email)) {
            $errors->add('invalid_email', __('Error: Disposable email addresses are not allowed.', 'block-disposable-emails'));
        }
    }

    public function check_email_domain_wp($errors, $sanitized_user_login, $user_email) {
        if ($this->is_disposable_email($user_email) && !$errors->get_error_message('invalid_email')) {
            $errors->add('invalid_email', __('Error: Disposable email addresses are not allowed.', 'block-disposable-emails'));
        }
        return $errors;
    }

    public function ultimate_member_check_email_domain($args) {
        if ($this->is_disposable_email($args['user_email'])) {
            UM()->form()->add_error('user_email', __('Disposable email addresses are not allowed.', 'block-disposable-emails'));
        }
    }

    public function woocommerce_check_email_domain($username, $email, $validation_errors) {
        if ($this->is_disposable_email($email) && !$validation_errors->get_error_message('invalid_email')) {
            $validation_errors->add('invalid_email', __('Error: Disposable email addresses are not allowed.', 'block-disposable-emails'));
        }
    }

    public function check_email_domain_on_register($user_id) {
        $user = get_userdata($user_id);
        if ($this->is_disposable_email($user->user_email)) {
            wp_delete_user($user_id);
            wp_die(__('Error: Disposable email addresses are not allowed.', 'block-disposable-emails'));
        }
    }

    private function is_disposable_email($email) {
        $domain = substr(strrchr($email, "@"), 1);
        $disposable_domains = BDE_Database::get_domains();
        return in_array($domain, $disposable_domains, true);
    }
}