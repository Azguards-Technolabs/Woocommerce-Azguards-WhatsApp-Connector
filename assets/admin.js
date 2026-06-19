jQuery(function ($) {
    $('.wa-template-dropdown').select2();

    $('.wa-template-dropdown').on('change', function () {
        let templateId = $(this).val();
        let section = $(this).data('section');
        let tableId = 'wa_' + section + '_table_data';
        let wrapperId = '#wrapper-' + tableId;
        $.post(ajaxurl, {
            action: 'wa_get_template_variables',
            template_id: templateId,
            table_id: tableId,
            section: section
        }, function (response) {
            if (response.success) {
                let wrapper = $(wrapperId);
                let table = wrapper.find(`table.wa-variable-table-${tableId} tbody`);
                let hiddenInput = wrapper.find('input.wa-custom-table-json');
                let tableAddClass = wrapper.find('table.wa-variable-table');
                if (response.data.section == 'order_creation') {
                    tableAddClass.addClass('order-event');
                } else {
                    tableAddClass.addClass('user-event');
                }
                table.empty();
                let newData = [];
                if (response.data.variables.length) {
                    response.data.variables.forEach((row, i) => {
                        let tr = `<tr>
                            <td>${row.index}</td>
                            <td>
                                <select name="${tableId}[${i}][max_results]">`;

                        Object.entries(response.data.options).forEach(([key, label]) => {
                            let selected = key === row.max_results ? 'selected' : '';
                            tr += `<option value="${key}" ${selected}>${label}</option>`;
                        });

                        tr += `</select></td></tr>`;
                        table.append(tr);
                        newData.push(row);
                    });
                } else {
                    let tr = `<tr><td style="color: red;">Template variable not available</td></tr>`;
                    table.append(tr);
                    newData.push(row);
                }

                $('.wa-variable-table-' + tableId + ' select').on('change', function () {
                    var table = $(this).closest('table');
                    var data = [];

                    table.find('tbody tr').each(function () {
                        var index = $(this).find('td:first').text().trim();
                        var max = $(this).find('select').val();
                        data.push({ index: index, max_results: max });
                    });
                    hiddenInput.val(JSON.stringify(data));
                });
                hiddenInput.val(JSON.stringify(newData));
            }
        });
    });

    jQuery(document).ready(function ($) {
        const configKeys = [
            'order_creation',
            'order_pending_payment',
            'order_processing',
            'order_on_hold',
            'order_completed',
            'order_cancellation',
            'order_credit_memo',
            'order_failed',
            'order_draft',
            'order_invoice',
            'order_shipment',
            'product_notification',
            'abandon_cart'
        ];

        function toggleVariableTables() {
            configKeys.forEach((key) => {
                const selectId = `#wa_${key}_template`;
                const tableId = `#wa_${key}_table_data`;

                const selectedVal = $(selectId).val();
                if (selectedVal) {
                    $(tableId).closest('tr').css('display', 'contents');
                } else {
                    $(tableId).closest('tr').css('display', 'none');
                }
            });
        }

        // Initial check
        toggleVariableTables();

        // Bind change event to all
        configKeys.forEach((key) => {
            $(`#wa_${key}_template`).on('change', toggleVariableTables);
        });
    });

    $('#wa-sync-templates-btn').on('click', function (e) {
        e.preventDefault();
        let $btn = $(this);
        if ($btn.hasClass('disabled')) return;

        $btn.addClass('disabled').text('Syncing...');

        $.post(ajaxurl, {
            action: 'wa_sync_templates',
            security: $btn.data('nonce')
        }, function (response) {
            $btn.removeClass('disabled').text('Sync Templates');
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert(response.data || 'Sync failed.');
            }
        });
    });
});
