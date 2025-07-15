<?php
/*
Plugin Name: Customer Service & Request Plugin
Plugin URI: https://www.kre8ivtech.com
Description: Customer service and request plugin with ChatGPT integration for task estimation and payment handling.
Version: 1.0.0
Author: Jeremiah Castillo
Author URI: https://www.kre8ivtech.com
License: GPL2
Text Domain: customer-service-plugin
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'includes/RequestForm.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/AdminDashboard.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/AdminSettings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/AdminPages.php';

class CustomerServicePlugin {

    private $request_form;
    private $admin_dashboard;
    private $admin_settings;
    private $admin_pages;

    public function __construct() {
        $this->request_form = new RequestForm();
        $this->admin_dashboard = new AdminDashboard();
        $this->admin_settings = new AdminSettings();
        $this->admin_pages = new AdminPages();

        add_action( 'wp_ajax_csp_get_requests', array( $this, 'ajax_get_requests' ) );
        add_action( 'wp_ajax_nopriv_csp_get_requests', array( $this, 'ajax_get_requests' ) );

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this->admin_settings, 'register_settings' ) );

        register_activation_hook( __FILE__, array( 'CustomerServicePlugin', 'activate_plugin' ) );
    }

    public static function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_profiles = $wpdb->prefix . 'csp_client_profiles';
        $table_messages = $wpdb->prefix . 'csp_messages';
        $table_requests = $wpdb->prefix . 'csp_requests';
        $table_invoices = $wpdb->prefix . 'csp_invoices';

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql_profiles = "CREATE TABLE $table_profiles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            personal_info TEXT NOT NULL,
            company_info TEXT NOT NULL,
            billing_info TEXT NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        $sql_messages = "CREATE TABLE $table_messages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sender_id BIGINT(20) UNSIGNED NOT NULL,
            receiver_id BIGINT(20) UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_requests = "CREATE TABLE $table_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            task_type VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(50) NOT NULL,
            due_date DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_invoices = "CREATE TABLE $table_invoices (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) NOT NULL,
            invoice_file VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql_profiles );
        dbDelta( $sql_messages );
        dbDelta( $sql_requests );
        dbDelta( $sql_invoices );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Customer Service',
            'Customer Service',
            'manage_options',
            'customer-service-plugin',
            array( $this->admin_dashboard, 'render_dashboard' ),
            'dashicons-admin-comments',
            6
        );

        add_submenu_page(
            'customer-service-plugin',
            'Requests',
            'Requests',
            'manage_options',
            'csp_requests',
            array( $this->admin_pages, 'admin_requests_page' )
        );

        add_submenu_page(
            'customer-service-plugin',
            'Messages',
            'Messages',
            'manage_options',
            'csp_messages',
            array( $this->admin_pages, 'admin_messages_page' )
        );

        add_submenu_page(
            'customer-service-plugin',
            'Invoices',
            'Invoices',
            'manage_options',
            'csp_invoices',
            array( $this->admin_pages, 'admin_invoices_page' )
        );

        add_submenu_page(
            'customer-service-plugin',
            'Settings',
            'Settings',
            'manage_options',
            'csp_settings',
            array( $this->admin_settings, 'render_settings_page' )
        );
    }

    public function ajax_get_requests() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        global $wpdb;
        $table_requests = $wpdb->prefix . 'csp_requests';
        $table_comments = $wpdb->prefix . 'csp_messages';

        // Get requests for the user
        $requests = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.id, r.task_type, r.description, r.status, r.due_date, r.created_at,
            (SELECT GROUP_CONCAT(message SEPARATOR '||') FROM $table_comments WHERE sender_id != %d AND receiver_id = %d AND message IS NOT NULL) AS agent_comments
            FROM $table_requests r
            WHERE r.user_id = %d
            ORDER BY r.created_at DESC",
            $user_id, $user_id, $user_id
        ), ARRAY_A );

        wp_send_json_success( $requests );
    }
}

new CustomerServicePlugin();
?>
