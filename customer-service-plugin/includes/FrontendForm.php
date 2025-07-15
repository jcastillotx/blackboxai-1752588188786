<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class FrontendForm {

    public function __construct() {
        add_shortcode( 'customer_service_request_form', array( $this, 'render_request_form' ) );
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
}
?>
