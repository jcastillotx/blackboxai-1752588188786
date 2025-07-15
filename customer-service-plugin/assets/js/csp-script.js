jQuery(document).ready(function($) {
    $('#csp_get_estimate_btn').on('click', function(e) {
        e.preventDefault();

        var taskType = $('#csp_task_type').val();
        var taskDescription = $('#csp_task_description').val();
        var clientEmail = $('#csp_client_email').val();

        if (!taskType || !taskDescription || !clientEmail) {
            alert('Please fill in all required fields before getting an estimate.');
            return;
        }

        $('#csp_estimate_result').html('Getting estimate...');

        $.ajax({
            url: csp_ajax_obj.ajax_url,
            method: 'POST',
            data: {
                action: 'csp_get_estimate',
                nonce: csp_ajax_obj.nonce,
                task_type: taskType,
                task_description: taskDescription,
                client_email: clientEmail
            },
            success: function(response) {
                if (response.success) {
                    var estimate = response.data;
                    var coveredText = estimate.covered_by_plan ? 'Your maintenance plan covers this task.' : '';
                    var discountText = estimate.discount > 0 ? 'You have a discount of $' + estimate.discount + '.' : '';
                    var depositText = estimate.cost > 200 ? 'A $50 minimum deposit is required.' : '';
                    var html = '<p>Estimated Time: ' + estimate.time + '</p>' +
                               '<p>Estimated Cost: $' + estimate.cost + '</p>' +
                               '<p>' + coveredText + ' ' + discountText + ' ' + depositText + '</p>';
                    $('#csp_estimate_result').html(html);
                    $('#csp_submit_request_btn').prop('disabled', false);
                } else {
                    $('#csp_estimate_result').html('<p style="color:red;">' + response.data + '</p>');
                    $('#csp_submit_request_btn').prop('disabled', true);
                }
            },
            error: function() {
                $('#csp_estimate_result').html('<p style="color:red;">Error getting estimate. Please try again later.</p>');
                $('#csp_submit_request_btn').prop('disabled', true);
            }
        });
    });

    $('#csp-request-form').on('submit', function(e) {
        e.preventDefault();

        var paymentOption = $('input[name="payment_option"]:checked').val();
        var estimateCostText = $('#csp_estimate_result p').filter(function() {
            return $(this).text().startsWith('Estimated Cost:');
        }).text();
        var estimateCost = 0;
        if (estimateCostText) {
            estimateCost = parseFloat(estimateCostText.replace('Estimated Cost: $', ''));
        }

        if (paymentOption === 'pay_now') {
            if (estimateCost > 200) {
                if (!confirm('The estimated cost is over $200. A $50 minimum deposit is required. Do you want to proceed with payment?')) {
                    return;
                }
            }
            alert('Proceeding to payment gateway (to be implemented).');
            // TODO: Integrate payment gateway here
        } else if (paymentOption === 'invoice') {
            alert('Invoice request submitted. We will contact you soon.');
            // TODO: Implement invoice request handling here
        }
    });
});
