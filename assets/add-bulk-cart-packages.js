jQuery(document).ready(function ($) {
    $('#woo-bulk-add-row').on('click', function () {
        var newRow = $('#woo-bulk-repeater .woo-bulk-row:first').clone();
        newRow.find('select').val('');
        newRow.find('input').val(1);
        $('#woo-bulk-repeater').append(newRow);
    });

    $(document).on('click', '.woo-bulk-remove-row', function () {
        if ($('#woo-bulk-repeater .woo-bulk-row').length > 1) {
            $(this).closest('.woo-bulk-row').remove();
        }
    });
});