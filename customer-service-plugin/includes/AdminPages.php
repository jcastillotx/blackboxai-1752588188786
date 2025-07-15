<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AdminPages {

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
}
?>
