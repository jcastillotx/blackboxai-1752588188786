<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AdminSettings {

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

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized user' );
        }
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

                <h2>Stripe API Settings</h2>
                <label for="csp_stripe_publishable_key">Publishable Key:</label>
                <input type="text" id="csp_stripe_publishable_key" name="csp_stripe_publishable_key" value="<?php echo esc_attr( get_option( 'csp_stripe_publishable_key' ) ); ?>" size="50" />

                <label for="csp_stripe_secret_key">Secret Key:</label>
                <input type="text" id="csp_stripe_secret_key" name="csp_stripe_secret_key" value="<?php echo esc_attr( get_option( 'csp_stripe_secret_key' ) ); ?>" size="50" />

                <h2>OpenAI API Key</h2>
                <label for="csp_openai_api_key">API Key:</label>
                <input type="text" id="csp_openai_api_key" name="csp_openai_api_key" value="<?php echo esc_attr( get_option( 'csp_openai_api_key' ) ); ?>" size="50" />

                <h2>Workflow Settings</h2>
                <label for="csp_workflow_vacation">Vacation Status:</label>
                <select id="csp_workflow_vacation" name="csp_workflow_vacation">
                    <option value="no" <?php selected( get_option( 'csp_workflow_vacation' ), 'no' ); ?>>No</option>
                    <option value="yes" <?php selected( get_option( 'csp_workflow_vacation' ), 'yes' ); ?>>Yes</option>
                </select>

                <label for="csp_workflow_reduced_hours">Reduced Work Hours (per week):</label>
                <input type="number" id="csp_workflow_reduced_hours" name="csp_workflow_reduced_hours" value="<?php echo esc_attr( get_option( 'csp_workflow_reduced_hours' ) ); ?>" min="0" max="40" />

                <p>Enter workflow inputs such as vacation status and reduced work hours.</p>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
?>
