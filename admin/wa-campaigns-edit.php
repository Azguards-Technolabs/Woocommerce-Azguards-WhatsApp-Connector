<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_templates = $wpdb->prefix . 'azguards_whatsapp_templates';
$templates = [];
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_templates'" ) == $table_templates ) {
    $templates = $wpdb->get_results( "SELECT entity_id, template_name FROM $table_templates ORDER BY template_name ASC", ARRAY_A );
}
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">Edit Campaign</h1>
        <div>
            <a href="?page=wa-campaigns" style="text-decoration:none; color:#333; font-weight:bold; margin-right:15px;">&larr; Back</a>
            <button id="wa_save_campaign_btn" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Save Campaign</button>
        </div>
    </div>

    <form id="wa_campaign_edit_form">
    <table class="form-table">
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Campaign Name *</label></th>
            <td><input type="text" id="wa_campaign_name" name="campaign_name" class="regular-text" value=""></td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Template *</label></th>
            <td>
                <select id="wa_template_entity_id" name="template_entity_id">
                    <option value="">-- Select Template --</option>
                    <?php if ( ! empty( $templates ) ) : ?>
                        <?php foreach ( $templates as $tpl ) : ?>
                            <option value="<?php echo esc_attr( $tpl['entity_id'] ); ?>"><?php echo esc_html( $tpl['template_name'] ); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div style="margin-top:10px; padding:15px; border:1px solid #ddd; border-radius:5px; background:#fcfcfc;">
                    <p style="font-weight:bold; margin-top:0;">Template Setup</p>
                    <p style="font-size:12px; color:#666;">Mapping is static for now.</p>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Send Template To *</label></th>
            <td>
                <select name="target_type"><option value="Specific Contacts">Specific Contacts</option></select>
                <div style="margin-top:10px; border:1px solid #ddd; border-radius:5px;">
                    <div style="background:#f1f1f1; padding:10px; font-weight:bold; display:flex; justify-content:space-between; border-bottom:1px solid #ddd;">
                        <span>👥 Specific Contacts</span>
                        <button type="button" class="button button-small" id="wa_clear_contacts">Clear All</button>
                    </div>
                    <div style="padding:15px;">
                        <ul id="wa_selected_contacts" style="margin:0; padding:0; list-style:none;">
                            <!-- Target elements will go here -->
                        </ul>
                        <div style="margin-top:15px;">
                            <input type="text" id="wa_customer_phone_input" placeholder="Type Phone Number and click Add" class="regular-text">
                            <button type="button" class="button" id="wa_add_contact_btn">Add</button>
                        </div>
                        <div style="margin-top:15px; color:#0073aa; font-size:12px; cursor:pointer;" id="wa_load_customers_btn">&#8853; Search for customers by name, email, or phone number. Click to browse all.</div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Schedule Time *</label></th>
            <td><input type="datetime-local" id="wa_schedule_time" name="schedule_time" value=""></td>
        </tr>
    </table>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let targets = [];

    $('#wa_add_contact_btn').on('click', function() {
        var phone = $('#wa_customer_phone_input').val().trim();
        if ( phone ) {
            targets.push(phone);
            renderTargets();
            $('#wa_customer_phone_input').val('');
        }
    });

    $(document).on('click', '.wa-remove-target', function() {
        var phone = $(this).data('phone');
        targets = targets.filter(t => t !== phone);
        renderTargets();
    });

    function renderTargets() {
        $('#wa_selected_contacts').empty();
        targets.forEach(phone => {
            $('#wa_selected_contacts').append(
                '<li style="margin-bottom:5px;"><span class="wa-remove-target" data-phone="'+phone+'" style="color:red; cursor:pointer;">✖</span> ' + phone + '</li>'
            );
        });
    }

    $('#wa_load_customers_btn').on('click', function() {
        $.post(ajaxurl, { action: 'wa_get_customers' }, function(response) {
            if (response.success && response.data.contacts) {
                response.data.contacts.forEach(c => {
                    if (c.phone && !targets.includes(c.phone)) {
                        targets.push(c.phone);
                    }
                });
                renderTargets();
            }
        });
    });

    $('#wa_clear_contacts').on('click', function() {
        targets = [];
        renderTargets();
    });

    $('#wa_save_campaign_btn').on('click', function(e) {
        e.preventDefault();
        var data = $('#wa_campaign_edit_form').serializeArray();
        data.push({name: 'action', value: 'wa_save_campaign'});
        targets.forEach(t => data.push({name: 'targets[]', value: t}));
        
        var $btn = $(this);
        $btn.text('Saving...').prop('disabled', true);
        
        $.post(ajaxurl, $.param(data), function(response) {
            $btn.text('Save Campaign').prop('disabled', false);
            if (response.success) {
                alert('Success: ' + response.data.message);
                window.location.href = response.data.redirect;
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            $btn.text('Save Campaign').prop('disabled', false);
            alert('Server error occurred.');
        });
    });
});
</script>
