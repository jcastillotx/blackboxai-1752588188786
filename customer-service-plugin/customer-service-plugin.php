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

class CustomerServicePlugin {

    private $request_form;

    public function __construct() {
        $this->request_form = new RequestForm();

        add_action( 'wp_ajax_csp_get_requests', array( $this, 'ajax_get_requests' ) );
        add_action( 'wp_ajax_nopriv_csp_get_requests', array( $this, 'ajax_get_requests' ) );

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

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
            array( $this, 'admin_page' ),
            'dashicons-admin-comments',
            6
        );

        add_submenu_page(
            'customer-service-plugin',
            'Requests',
            'Requests',
            'manage_options',
            'csp_requests',
            array( $this, 'admin_requests_page' )
        );

        add_submenu_page(
            'customer-service-plugin',
            'Messages',
            'Messages',
            'manage_options',
            'csp_messages',
            array( $this, 'admin_messages_page' )
        );

        add_submenu_page(
            'customer-service-plugin',
            'Invoices',
            'Invoices',
            'manage_options',
            'csp_invoices',
            array( $this, 'admin_invoices_page' )
        );
    }

    public function admin_requests_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized user' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'csp_requests';
        $requests = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
        ?>
        <div class="wrap">
            <h1>Customer Requests</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Task Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $requests as $request ) : ?>
                    <tr>
                        <td><?php echo esc_html( $request['id'] ); ?></td>
                        <td><?php echo esc_html( $request['user_id'] ); ?></td>
                        <td><?php echo esc_html( $request['task_type'] ); ?></td>
                        <td><?php echo esc_html( $request['description'] ); ?></td>
                        <td><?php echo esc_html( $request['status'] ); ?></td>
                        <td><?php echo esc_html( $request['due_date'] ); ?></td>
                        <td><?php echo esc_html( $request['created_at'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function admin_messages_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized user' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'csp_messages';
        $messages = $wpdb->get_results( "SELECT * FROM $table ORDER BY timestamp DESC", ARRAY_A );
        ?>
        <div class="wrap">
            <h1>Customer Messages</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sender ID</th>
                        <th>Receiver ID</th>
                        <th>Message</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $messages as $message ) : ?>
                    <tr>
                        <td><?php echo esc_html( $message['id'] ); ?></td>
                        <td><?php echo esc_html( $message['sender_id'] ); ?></td>
                        <td><?php echo esc_html( $message['receiver_id'] ); ?></td>
                        <td><?php echo esc_html( $message['message'] ); ?></td>
                        <td><?php echo esc_html( $message['timestamp'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function admin_invoices_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized user' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'csp_invoices';
        $invoices = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
        ?>
        <div class="wrap">
            <h1>Customer Invoices</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Invoice Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Invoice File</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $invoices as $invoice ) : ?>
                    <tr>
                        <td><?php echo esc_html( $invoice['id'] ); ?></td>
                        <td><?php echo esc_html( $invoice['user_id'] ); ?></td>
                        <td><?php echo esc_html( $invoice['invoice_number'] ); ?></td>
                        <td><?php echo esc_html( $invoice['amount'] ); ?></td>
                        <td><?php echo esc_html( $invoice['status'] ); ?></td>
                        <td>
                            <?php if ( $invoice['invoice_file'] ) : ?>
                                <a href="<?php echo esc_url( wp_upload_dir()['baseurl'] . '/' . $invoice['invoice_file'] ); ?>" target="_blank">View</a>
                            <?php else : ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $invoice['created_at'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
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

    public function register_settings() {
        register_setting( 'csp_settings_group', 'csp_worker_schedule' );
        register_setting( 'csp_settings_group', 'csp_maintenance_plans' );
        register_setting( 'csp_settings_group', 'csp_task_rates' );
        register_setting( 'csp_settings_group', 'csp_stripe_publishable_key' );
        register_setting( 'csp_settings_group', 'csp_stripe_secret_key' );
        register_setting( 'csp_settings_group', 'csp_openai_api_key' );
        register_setting( 'csp_settings_group', 'csp_workflow_vacation' );
        register_setting( 'csp_settings_group', 'csp_workflow_reduced_hours' );
    }

    public function admin_page() {
        global $wpdb;
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized user' );
        }

        $table = $wpdb->prefix . 'csp_requests';

        // Get counts for pending and completed tasks
        $pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'pending'" );
        $completed_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'completed'" );

        // Get active tasks (not completed)
        $active_tasks = $wpdb->get_results( "SELECT * FROM $table WHERE status != 'completed' ORDER BY created_at DESC", ARRAY_A );

        ?>
        <div class="wrap">
            <h1>Customer Service Admin Dashboard</h1>

            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1; padding: 20px; background: #f7f7f7; border-radius: 8px; text-align: center;">
                    <h2>Pending Tasks</h2>
                    <p style="font-size: 2em; font-weight: bold;"><?php echo intval( $pending_count ); ?></p>
                </div>
                <div style="flex: 1; padding: 20px; background: #f7f7f7; border-radius: 8px; text-align: center;">
                    <h2>Completed Tasks</h2>
                    <p style="font-size: 2em; font-weight: bold;"><?php echo intval( $completed_count ); ?></p>
                </div>
            </div>

            <h2>Active Tasks</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Submission Date</th>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Required Completion Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $active_tasks as $task ) : ?>
                    <tr>
                        <td><?php echo esc_html( $task['created_at'] ); ?></td>
                        <td><?php echo esc_html( $task['task_type'] ); ?></td>
                        <td><?php echo esc_html( $task['user_id'] ); ?></td>
                        <td><?php echo esc_html( $task['status'] ); ?></td>
                        <td><?php echo esc_html( $task['due_date'] ? $task['due_date'] : 'N/A' ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new CustomerServicePlugin();
?>
