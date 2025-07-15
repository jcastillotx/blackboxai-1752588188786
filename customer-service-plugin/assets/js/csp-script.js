jQuery(document).ready(function($) {
    // Load client requests in dashboard
    function loadRequests() {
        $.ajax({
            url: csp_ajax_obj.ajax_url,
            method: 'GET',
            data: {
                action: 'csp_get_requests',
                nonce: csp_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    var requests = response.data;
                    var html = '<ul class="csp-request-list">';
                    requests.forEach(function(req) {
                        html += '<li class="csp-request-item" data-id="' + req.id + '">';
                        html += '<strong>Task:</strong> ' + req.task_type + ' | ';
                        html += '<strong>Status:</strong> ' + req.status;
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#csp-requests-content').html(html);
                } else {
                    $('#csp-requests-content').html('<p>Error loading requests.</p>');
                }
            },
            error: function() {
                $('#csp-requests-content').html('<p>Error loading requests.</p>');
            }
        });
    }

    // Show popup with request details and agent comments
    function showRequestDetails(id, requests) {
        var req = requests.find(r => r.id == id);
        if (!req) return;

        var comments = req.agent_comments ? req.agent_comments.split('||').join('<br>') : 'No comments yet.';
        var popupHtml = '<div class="csp-popup-overlay">';
        popupHtml += '<div class="csp-popup">';
        popupHtml += '<h3>Request Details</h3>';
        popupHtml += '<p><strong>Task Type:</strong> ' + req.task_type + '</p>';
        popupHtml += '<p><strong>Description:</strong> ' + req.description + '</p>';
        popupHtml += '<p><strong>Status:</strong> ' + req.status + '</p>';
        popupHtml += '<p><strong>Due Date:</strong> ' + (req.due_date ? req.due_date : 'N/A') + '</p>';
        popupHtml += '<p><strong>Agent Comments:</strong><br>' + comments + '</p>';
        popupHtml += '<button id="csp-close-popup">Close</button>';
        popupHtml += '</div></div>';

        $('body').append(popupHtml);

        $('#csp-close-popup').on('click', function() {
            $('.csp-popup-overlay').remove();
        });
    }

    // Store loaded requests globally for popup access
    var loadedRequests = [];

    // Load requests on dashboard tab show
    $('button[data-tab="requests"]').on('click', function() {
        loadRequests();
    });

    // Delegate click event for request items to show popup
    $('#csp-requests-content').on('click', '.csp-request-item', function() {
        var id = $(this).data('id');
        showRequestDetails(id, loadedRequests);
    });

    // Initial load of requests if requests tab is active on page load
    if ($('button[data-tab="requests"]').hasClass('active')) {
        loadRequests();
    }

    // Update loadedRequests when requests are loaded
    function loadRequests() {
        $.ajax({
            url: csp_ajax_obj.ajax_url,
            method: 'GET',
            data: {
                action: 'csp_get_requests',
                nonce: csp_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    loadedRequests = response.data;
                    var html = '<ul class="csp-request-list">';
                    loadedRequests.forEach(function(req) {
                        html += '<li class="csp-request-item" data-id="' + req.id + '">';
                        html += '<strong>Task:</strong> ' + req.task_type + ' | ';
                        html += '<strong>Status:</strong> ' + req.status;
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#csp-requests-content').html(html);
                } else {
                    $('#csp-requests-content').html('<p>Error loading requests.</p>');
                }
            },
            error: function() {
                $('#csp-requests-content').html('<p>Error loading requests.</p>');
            }
        });
    }
});
