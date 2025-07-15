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

class CustomerServicePlugin {

    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_csp_get_estimate', array( $this, 'ajax_get_estimate' ) );
        add_action( 'wp_ajax_nopriv_csp_get_estimate', array( $this, 'ajax_get_estimate' ) );

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

    public function register_shortcodes() {
        add_shortcode( 'customer_service_request_form', array( $this, 'render_request_form' ) );
        add_shortcode( 'customer_service_dashboard', array( $this, 'render_client_dashboard' ) );
    }

    // AJAX handler to get profile data
    public function ajax_get_profile() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'csp_client_profiles';
        $profile = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id ), ARRAY_A );
        if ( ! $profile ) {
            // Return empty profile structure
            $profile = array(
                'personal_info' => '',
                'company_info' => '',
                'billing_info' => '',
            );
        }
        wp_send_json_success( $profile );
    }

    // AJAX handler to update profile data
    public function ajax_update_profile() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        $personal_info = sanitize_textarea_field( $_POST['personal_info'] ?? '' );
        $company_info = sanitize_textarea_field( $_POST['company_info'] ?? '' );
        $billing_info = sanitize_textarea_field( $_POST['billing_info'] ?? '' );

        global $wpdb;
        $table = $wpdb->prefix . 'csp_client_profiles';

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id ) );

        if ( $existing ) {
            $updated = $wpdb->update(
                $table,
                array(
                    'personal_info' => $personal_info,
                    'company_info' => $company_info,
                    'billing_info' => $billing_info,
                ),
                array( 'user_id' => $user_id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $inserted = $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'personal_info' => $personal_info,
                    'company_info' => $company_info,
                    'billing_info' => $billing_info,
                ),
                array( '%d', '%s', '%s', '%s' )
            );
        }

        wp_send_json_success( 'Profile updated.' );
    }

    // AJAX handler to get settings (email and password)
    public function ajax_get_settings() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user = wp_get_current_user();
        wp_send_json_success( array(
            'email' => $user->user_email,
        ) );
    }

    // AJAX handler to update settings (email and password)
    public function ajax_update_settings() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        $new_email = sanitize_email( $_POST['email'] ?? '' );
        $new_password = $_POST['password'] ?? '';

        if ( ! is_email( $new_email ) ) {
            wp_send_json_error( 'Invalid email address.' );
        }

        $user_data = array(
            'ID' => $user_id,
            'user_email' => $new_email,
        );

        if ( ! empty( $new_password ) ) {
            $user_data['user_pass'] = $new_password;
        }

        $result = wp_update_user( $user_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( 'Failed to update user.' );
        }

        wp_send_json_success( 'Settings updated.' );
    }

    // AJAX handler to get messages between client and worker
    public function ajax_get_messages() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        $worker_id = intval( $_GET['worker_id'] ?? 0 );
        if ( ! $worker_id ) {
            wp_send_json_error( 'Invalid worker ID.' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'csp_messages';
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d) ORDER BY timestamp ASC",
            $user_id, $worker_id, $worker_id, $user_id
        ), ARRAY_A );
        wp_send_json_success( $messages );
    }

    // AJAX handler to send a message
    public function ajax_send_message() {
        check_ajax_referer( 'csp_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
        }
        $user_id = get_current_user_id();
        $worker_id = intval( $_POST['worker_id'] ?? 0 );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        if ( ! $worker_id || empty( $message ) ) {
            wp_send_json_error( 'Invalid input.' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'csp_messages';
        $inserted = $wpdb->insert(
            $table,
            array(
                'sender_id' => $user_id,
                'receiver_id' => $worker_id,
                'message' => $message,
                'timestamp' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s' )
        );
        if ( ! $inserted ) {
            wp_send_json_error( 'Failed to send message.' );
        }
        wp_send_json_success( 'Message sent.' );
    }

    public function render_client_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to view your dashboard.</p>';
        }

        ob_start();
        ?>
        <div id="csp-client-dashboard" class="csp-dashboard">
            <h2>Client Dashboard</h2>
            <nav class="csp-dashboard-nav">
                <button data-tab="profile" class="csp-tab-btn active">Profile</button>
                <button data-tab="settings" class="csp-tab-btn">Settings</button>
                <button data-tab="messages" class="csp-tab-btn">Messages</button>
                <button data-tab="calendar" class="csp-tab-btn">Calendar</button>
                <button data-tab="requests" class="csp-tab-btn">Requests</button>
                <button data-tab="invoices" class="csp-tab-btn">Invoices</button>
            </nav>
            <div class="csp-dashboard-content">
                <section id="csp-tab-profile" class="csp-tab-content active">
                    <h3>Profile</h3>
                    <div id="csp-profile-content">Loading profile...</div>
                </section>
                <section id="csp-tab-settings" class="csp-tab-content">
                    <h3>Settings</h3>
                    <div id="csp-settings-content">Loading settings...</div>
                </section>
                <section id="csp-tab-messages" class="csp-tab-content">
                    <h3>Messages</h3>
                    <div id="csp-messages-content">Loading messages...</div>
                </section>
                <section id="csp-tab-calendar" class="csp-tab-content">
                    <h3>Calendar</h3>
                    <div id="csp-calendar-content">Loading calendar...</div>
                </section>
                <section id="csp-tab-requests" class="csp-tab-content">
                    <h3>Requests</h3>
                    <div id="csp-requests-content">Loading requests...</div>
                </section>
                <section id="csp-tab-invoices" class="csp-tab-content">
                    <h3>Invoices</h3>
                    <div id="csp-invoices-content">Loading invoices...</div>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
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
    }

    public function register_settings() {
        register_setting( 'csp_settings_group', 'csp_worker_schedule' );
        register_setting( 'csp_settings_group', 'csp_maintenance_plans' );
        register_setting( 'csp_settings_group', 'csp_task_rates' );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'csp-style', plugin_dir_url( __FILE__ ) . 'assets/css/csp-style.css', array(), '1.0' );
        wp_enqueue_script( 'csp-script', plugin_dir_url( __FILE__ ) . 'assets/js/csp-script.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'csp-script', 'csp_ajax_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'csp_nonce' ),
        ));
    }

    public function render_request_form() {
        ob_start();
        ?>
        <form id="csp-request-form" class="csp-form">
            <label for="csp_task_type">Task Type:</label>
            <select id="csp_task_type" name="task_type" required>
                <option value="">Select a task</option>
                <option value="content_update">Content Update</option>
                <option value="debug">Debug</option>
                <option value="development">Development</option>
                <option value="other">Other</option>
            </select>

            <label for="csp_task_description">Task Description:</label>
            <textarea id="csp_task_description" name="task_description" rows="4" required></textarea>

            <label for="csp_client_email">Your Email:</label>
            <input type="email" id="csp_client_email" name="client_email" required />

            <div id="csp_estimate_result" class="csp-estimate-result"></div>

            <fieldset>
                <legend>Payment Options</legend>
                <label><input type="radio" name="payment_option" value="pay_now" checked> Pay Now</label>
                <label><input type="radio" name="payment_option" value="invoice"> Request Invoice</label>
            </fieldset>

            <button type="button" id="csp_get_estimate_btn">Get Estimate</button>
            <button type="submit" id="csp_submit_request_btn" disabled>Submit Request</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_estimate() {
        check_ajax_referer( 'csp_nonce', 'nonce' );

        $task_type = sanitize_text_field( $_POST['task_type'] ?? '' );
        $task_description = sanitize_textarea_field( $_POST['task_description'] ?? '' );
        $client_email = sanitize_email( $_POST['client_email'] ?? '' );

        if ( empty( $task_type ) || empty( $task_description ) || empty( $client_email ) ) {
            wp_send_json_error( 'Please fill in all required fields.' );
        }

        // Prepare prompt for ChatGPT
        $worker_schedule = get_option( 'csp_worker_schedule', '' );
        $maintenance_plans = get_option( 'csp_maintenance_plans', '' );

        $prompt = "You are a customer service assistant. Given the task type: {$task_type}, description: {$task_description}, worker schedule: {$worker_schedule}, and maintenance plans: {$maintenance_plans}, provide an estimated time to complete the task and estimated cost. If the client has a maintenance plan, apply coverage or discount accordingly. Also, if the cost is over $200, mention a $50 minimum deposit requirement.";

        // Call ChatGPT API (OpenAI) - Replace with your API key and endpoint
        $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        if ( empty( $api_key ) ) {
            wp_send_json_error( 'API key not configured.' );
        }

        $response = $this->call_chatgpt_api( $prompt, $api_key );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'Error communicating with ChatGPT API.' );
        }

        $result = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $result['choices'][0]['message']['content'] ) ) {
            wp_send_json_error( 'Invalid response from ChatGPT API.' );
        }

        $content = $result['choices'][0]['message']['content'];

        // Parse the content for time, cost, coverage, discount
        // For simplicity, assume JSON response from ChatGPT
        $estimate = json_decode( $content, true );

        if ( ! is_array( $estimate ) ) {
            // Fallback: parse manually or return error
            wp_send_json_error( 'Could not parse estimate from ChatGPT response.' );
        }

        wp_send_json_success( $estimate );
    }

    private function call_chatgpt_api( $prompt, $api_key ) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $body = json_encode( array(
            'model' => 'gpt-4o',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_tokens' => 150,
            'temperature' => 0.7,
        ) );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => $body,
            'timeout' => 15,
        );

        $response = wp_remote_post( $endpoint, $args );

        return $response;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Customer Service Plugin Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'csp_settings_group' );
                do_settings_sections( 'csp_settings_group' );
                ?>
                <h2>Worker Schedule</h2>
                <textarea name="csp_worker_schedule" rows="10" cols="50" placeholder="Enter worker schedule JSON or details here"><?php echo esc_textarea( get_option( 'csp_worker_schedule' ) ); ?></textarea>

                <h2>Maintenance Plans</h2>
                <textarea name="csp_maintenance_plans" rows="10" cols="50" placeholder="Enter maintenance plans JSON or details here"><?php echo esc_textarea( get_option( 'csp_maintenance_plans' ) ); ?></textarea>

                <h2>Task Rates (JSON)</h2>
                <textarea name="csp_task_rates" rows="10" cols="50" placeholder='{"content_update": 100, "debug": 150, "development": 300, "other": 200}'><?php echo esc_textarea( get_option( 'csp_task_rates' ) ); ?></textarea>

                <p>Enter task rates as JSON with task type keys and numeric values.</p>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
    }
}
 
new CustomerServicePlugin();
?>
