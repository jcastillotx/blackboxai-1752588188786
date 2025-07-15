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

    // Messaging system integration
    var currentMessageTab = 'inbox'; // inbox or sent

    function loadMessages(tab) {
        var data = {
            action: 'csp_get_messages',
            nonce: csp_ajax_obj.nonce,
            worker_id: 0 // For inbox, worker_id is sender or receiver? Need clarification, assuming 0 fetches all messages
        };

        if (tab === 'sent') {
            // For sent messages, we might need a different AJAX or filter
            // For now, fetch all messages and filter client sent messages in frontend
        }

        $.ajax({
            url: csp_ajax_obj.ajax_url,
            method: 'GET',
            data: data,
            success: function(response) {
                if (response.success) {
                    var messages = response.data;
                    var html = '<ul class="csp-message-list">';
                    messages.forEach(function(msg) {
                        html += '<li class="csp-message-item">';
                        html += '<strong>From:</strong> ' + msg.sender_id + ' <strong>To:</strong> ' + msg.receiver_id + '<br>';
                        html += '<span>' + msg.message + '</span><br>';
                        html += '<small>' + msg.timestamp + '</small>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#csp-messages-content').html(html);
                } else {
                    $('#csp-messages-content').html('<p>Error loading messages.</p>');
                }
            },
            error: function() {
                $('#csp-messages-content').html('<p>Error loading messages.</p>');
            }
        });
    }

    // New message form
    function renderNewMessageForm() {
        var html = '<form id="csp-new-message-form">';
        html += '<label for="csp_worker_id">Worker ID:</label>';
        html += '<input type="number" id="csp_worker_id" name="worker_id" required />';
        html += '<label for="csp_message_text">Message:</label>';
        html += '<textarea id="csp_message_text" name="message" rows="4" required></textarea>';
        html += '<button type="submit">Send Message</button>';
        html += '</form>';
        $('#csp-messages-content').html(html);

        $('#csp-new-message-form').on('submit', function(e) {
            e.preventDefault();
            var worker_id = $('#csp_worker_id').val();
            var message = $('#csp_message_text').val();

            $.ajax({
                url: csp_ajax_obj.ajax_url,
                method: 'POST',
                data: {
                    action: 'csp_send_message',
                    nonce: csp_ajax_obj.nonce,
                    worker_id: worker_id,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        alert('Message sent.');
                        loadMessages('inbox');
                    } else {
                        alert('Error sending message: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error sending message.');
                }
            });
        });
    }

    // Messaging tab buttons
    var messageTabsHtml = '<div class="csp-message-tabs">';
    messageTabsHtml += '<button id="csp-tab-inbox" class="csp-message-tab active">Inbox</button>';
    messageTabsHtml += '<button id="csp-tab-sent" class="csp-message-tab">Sent</button>';
    messageTabsHtml += '<button id="csp-tab-new" class="csp-message-tab">New Message</button>';
    messageTabsHtml += '</div>';
    $('#csp-messages-content').before(messageTabsHtml);

    // Handle messaging tab clicks
    $(document).on('click', '.csp-message-tab', function() {
        $('.csp-message-tab').removeClass('active');
        $(this).addClass('active');
        var tabId = $(this).attr('id');

        if (tabId === 'csp-tab-inbox') {
            loadMessages('inbox');
        } else if (tabId === 'csp-tab-sent') {
            loadMessages('sent');
        } else if (tabId === 'csp-tab-new') {
            renderNewMessageForm();
        }
    });

    // Load inbox messages by default when messages tab is clicked
    $('button[data-tab="messages"]').on('click', function() {
        loadMessages('inbox');
    });

    // Initial load if messages tab is active on page load
    if ($('button[data-tab="messages"]').hasClass('active')) {
        loadMessages('inbox');
    }
});
