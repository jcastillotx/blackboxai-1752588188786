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
    }

    public function register_shortcodes() {
        add_shortcode( 'customer_service_request_form', array( $this, 'render_request_form' ) );
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

        // TODO: Integrate with ChatGPT API to get estimate based on task and worker schedule
        // For now, return dummy data
        $estimate = array(
            'time' => '3 days',
            'cost' => 150,
            'covered_by_plan' => false,
            'discount' => 0,
        );

        wp_send_json_success( $estimate );
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

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new CustomerServicePlugin();
?>
