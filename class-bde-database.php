<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BDE_Database {

    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'bde_domains';
    }

    public static function create_table() {
        global $wpdb;
        self::init();

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . self::$table_name . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            domain varchar(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY domain (domain)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_domains() {
        global $wpdb;
        self::init();

        $results = $wpdb->get_results("SELECT domain FROM " . self::$table_name, ARRAY_A);
        return wp_list_pluck($results, 'domain');
    }

    public static function save_domains($domains) {
        global $wpdb;
        self::init();

        $wpdb->query("TRUNCATE TABLE " . self::$table_name);

        // Prepare the query for batch insert
        $values = array();
        foreach ($domains as $domain) {
            $values[] = $wpdb->prepare("(%s)", $domain);
        }
        $values = implode(", ", $values);

        // Insert all domains in one query
        $wpdb->query("INSERT INTO " . self::$table_name . " (domain) VALUES $values");
    }

    public static function add_domain($domain) {
        global $wpdb;
        self::init();

        $wpdb->insert(self::$table_name, array('domain' => $domain));
    }
}

BDE_Database::init();