<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class RequestForm {

    public function __construct() {
        add_shortcode( 'customer_service_request_form', array( $this, 'render_request_form' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_csp_get_estimate', array( $this, 'ajax_get_estimate' ) );
        add_action( 'wp_ajax_nopriv_csp_get_estimate', array( $this, 'ajax_get_estimate' ) );
        add_action( 'wp_ajax_csp_submit_request', array( $this, 'ajax_submit_request' ) );
        add_action( 'wp_ajax_nopriv_csp_submit_request', array( $this, 'ajax_submit_request' ) );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'csp-style', plugin_dir_url( __FILE__ ) . '../assets/css/csp-style.css', array(), '1.0' );
        wp_enqueue_script( 'csp-script', plugin_dir_url( __FILE__ ) . '../assets/js/csp-script.js', array( 'jquery' ), '1.0', true );
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

        $worker_schedule = get_option( 'csp_worker_schedule', '' );
        $maintenance_plans = get_option( 'csp_maintenance_plans', '' );

        $prompt = "You are a customer service assistant. Given the task type: {$task_type}, description: {$task_description}, worker schedule: {$worker_schedule}, and maintenance plans: {$maintenance_plans}, provide an estimated time to complete the task and estimated cost. If the client has a maintenance plan, apply coverage or discount accordingly. Also, if the cost is over $200, mention a $50 minimum deposit requirement.";

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

        $estimate = json_decode( $content, true );

        if ( ! is_array( $estimate ) ) {
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

    public function ajax_submit_request() {
        check_ajax_referer( 'csp_nonce', 'nonce' );

        $task_type = sanitize_text_field( $_POST['task_type'] ?? '' );
        $task_description = sanitize_textarea_field( $_POST['task_description'] ?? '' );
        $client_email = sanitize_email( $_POST['client_email'] ?? '' );
        $payment_option = sanitize_text_field( $_POST['payment_option'] ?? '' );

        if ( empty( $task_type ) || empty( $task_description ) || empty( $client_email ) || empty( $payment_option ) ) {
            wp_send_json_error( 'Please fill in all required fields.' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'csp_requests';

        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => 0, // Could be updated to actual user ID if logged in
                'task_type' => $task_type,
                'description' => $task_description,
                'status' => 'pending',
                'due_date' => null,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( 'Failed to submit request.' );
        }

        // Handle payment option logic here (e.g., redirect to payment gateway or send invoice email)
        // For now, just return success message

        wp_send_json_success( 'Request submitted successfully.' );
    }
}
?>
