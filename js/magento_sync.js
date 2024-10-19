jQuery(document).ready(function($) {
    $('#test_magento_connection').on('click', function(e) {
        e.preventDefault();

        // Disable the button to prevent multiple clicks
        $(this).prop('disabled', true).text('Testing...');

        // Clear previous messages
        $('#connection_message').html('');

        // Perform the AJAX request
        $.ajax({
            url: magentoSyncData.ajax_url,
            method: 'POST',
            data: {
                action: 'test_magento_connection',
                security: magentoSyncData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#connection_message').html('<div style="color:green;">' + response.data + '</div>');
                } else {
                    $('#connection_message').html('<div style="color:red;">' + response.data + '</div>');
                }
            },
            error: function() {
                $('#connection_message').html('<div style="color:red;">Failed to perform the connection test.</div>');
            },
            complete: function() {
                $('#test_magento_connection').prop('disabled', false).text('Test Connection');
            }
        });
    });
});