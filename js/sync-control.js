jQuery(document).ready(function($) {
    $('.start-sync, .stop-sync').on('click', function() {
        var action = $(this).data('action');
        var ajaxAction = action === 'start' ? 'start_sync' : 'stop_sync';

        $.post(ajax_object.ajax_url, { action: ajaxAction }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload(); // Reload page to update status
            } else {
                alert('Failed to update sync status.');
            }
        });
    });
});