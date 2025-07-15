<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AdminDashboard {

    public function render_dashboard() {
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
?>
