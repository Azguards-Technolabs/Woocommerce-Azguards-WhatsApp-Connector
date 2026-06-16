<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$camp_table = $wpdb->prefix . 'azguards_whatsapp_campaigns';
$tpl_table  = $wpdb->prefix . 'azguards_whatsapp_templates';

// Load existing campaign if editing
$campaign_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$campaign    = null;
if ( $campaign_id ) {
    $campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $camp_table WHERE campaign_id = %d", $campaign_id ) );
}

// Fetch templates for the dropdown
$templates = $wpdb->get_results( "SELECT entity_id, template_name, template_id, body FROM $tpl_table WHERE status = 'APPROVED' ORDER BY template_name ASC" );

// Pre-calculate variables for UI interaction
$template_data = [];
foreach ( $templates as $tpl ) {
    $bodyText = $tpl->body ?? '';
    preg_match_all( '/\{\{\s*([^}]+?)\s*\}\}/', $bodyText, $matches );
    $vars = [];
    foreach ( $matches[1] ?? [] as $variable ) {
        $variable = trim( $variable );
        if ( $variable !== '' && ! in_array( $variable, $vars, true ) ) {
            $vars[] = $variable;
        }
    }
    $template_data[$tpl->entity_id] = [
        'id'   => $tpl->template_id,
        'name' => $tpl->template_name,
        'vars' => $vars
    ];
}

// Fetch User Roles (Customer Groups)
global $wp_roles;
$customer_groups = wp_roles()->get_names();
$selected_groups = isset($campaign->customer_groups) ? json_decode($campaign->customer_groups, true) : [];
if (!is_array($selected_groups)) {
    $selected_groups = [];
}
$variable_mapping = isset($campaign->variable_mapping) ? json_decode($campaign->variable_mapping, true) : [];
if (!is_array($variable_mapping)) {
    $variable_mapping = [];
}

// Pre-load saved contact IDs for pre-selection on edit
$saved_contact_ids = [];
if ( $campaign && ! empty( $campaign->contact_ids ) ) {
    $decoded = json_decode( $campaign->contact_ids, true );
    if ( is_array( $decoded ) ) {
        $saved_contact_ids = array_map( 'intval', $decoded );
    }
}

$page_title = $campaign ? 'Edit Campaign' : 'Create Campaign';
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;"><?php echo esc_html( $page_title ); ?></h1>
        <div>
            <a href="?page=wa-campaigns" style="text-decoration:none; color:#333; font-weight:bold; margin-right:15px;">&larr; Back to Campaigns</a>
        </div>
    </div>

    <div id="wa_campaign_status_msg" style="display:none; padding:10px 15px; margin-bottom:15px; border-radius:4px;"></div>

    <table class="form-table">
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Campaign Name *</label></th>
            <td><input type="text" id="wa_camp_name" class="regular-text" value="<?php echo esc_attr( $campaign->campaign_name ?? '' ); ?>" placeholder="e.g. Summer Sale Promo"></td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Template *</label></th>
            <td>
                <select id="wa_camp_template">
                    <option value="">— Select Template —</option>
                    <?php foreach ( $templates as $tpl ): ?>
                        <option value="<?php echo esc_attr( $tpl->entity_id ); ?>"
                            <?php selected( $campaign->template_entity_id ?? '', $tpl->entity_id ); ?>>
                            <?php echo esc_html( $tpl->template_name ); ?> (<?php echo esc_html( $tpl->template_id ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ( empty( $templates ) ): ?>
                    <p class="description" style="color:#c62828;">No templates found. Please <a href="?page=wa-templates-grid">sync or create templates</a> first.</p>
                <?php endif; ?>
            </td>
        </tr>
        <tr id="wa_template_setup_row" style="display:none;">
            <th scope="row"><label>Template Setup</label></th>
            <td>
                <div style="border:1px solid #e2e2e2; padding: 15px; border-radius: 4px; background: #fff; max-width: 600px;">
                    <p style="margin-top:0; border-bottom: 2px solid #ea5c0b; display:inline-block; font-weight:600; padding-bottom: 5px;">Template Setup</p>
                    <p class="description" style="margin-bottom: 15px;">Map each template variable to an automatic customer field, or type a custom value.</p>
                    
                    <div id="wa_template_vars_container">
                        <!-- Dynamic fields injected here -->
                    </div>
                </div>
            </td>
        </tr>
        <tr id="wa_send_to_row">
            <th scope="row"><label style="color:#d32f2f;">Send To *</label></th>
            <td>
                <select id="wa_camp_target">
                    <option value="customer_groups" <?php selected( $campaign->target_type ?? '', 'customer_groups' ); ?>>Customer Groups</option>
                    <option value="all_contacts" <?php selected( $campaign->target_type ?? '', 'all_contacts' ); ?>>All Synced Contacts</option>
                    <option value="specific_contacts" <?php selected( $campaign->target_type ?? '', 'specific_contacts' ); ?>>Specific Contacts</option>
                </select>
            </td>
        </tr>
        <tr id="wa_customer_groups_row" style="display:none;">
            <th scope="row"><label>Customer Groups</label></th>
            <td>
                <select id="wa_camp_customer_groups" multiple style="min-width:300px; height: 120px;">
                    <?php foreach ($customer_groups as $role_key => $role_name): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php strpos(wp_json_encode($selected_groups, true), $role_key) !== false ? print 'selected' : ''; ?>>
                            <?php echo esc_html($role_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Hold Ctrl/Cmd to select multiple groups.</p>
            </td>
        </tr>
        <tr id="wa_contacts_row" style="display:none;">
            <th scope="row"><label>Specific Contacts</label></th>
            <td>
                <div style="border:1px solid #ccc; max-height:220px; overflow-y:auto; background:#fafafa; border-radius:3px;" id="wa_contacts_list">
                    <div style="padding:12px; color:#555; font-size:13px;">Loading synced contacts...</div>
                </div>
                <p class="description" style="margin-top:6px;">Only customers who have been synced to WhatTack are listed. <a href="<?php echo admin_url('users.php'); ?>">Sync more customers &rarr;</a></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Time Zone *</label></th>
            <td>
                <select id="wa_camp_timezone" style="max-width:300px;">
                    <?php echo wp_timezone_choice( $campaign->timezone ?? wp_timezone_string() ); ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Trigger Type *</label></th>
            <td>
                <select id="wa_camp_trigger_type">
                    <option value="explicit_date" <?php selected( $campaign->trigger_type ?? '', 'explicit_date' ); ?>>Specific Time</option>
                    <option value="cron" <?php selected( $campaign->trigger_type ?? '', 'cron' ); ?>>Recurring (Cron)</option>
                </select>
            </td>
        </tr>
        <tr id="row_schedule_time">
            <th scope="row"><label style="color:#d32f2f;">Schedule Time *</label></th>
            <td>
                <input type="datetime-local" id="wa_camp_schedule" class="regular-text"
                    value="<?php echo !empty($campaign->schedule_time) && $campaign->schedule_time !== '0000-00-00 00:00:00' ? esc_attr( date( 'Y-m-d\TH:i', strtotime( $campaign->schedule_time ) ) ) : ''; ?>">
            </td>
        </tr>
        <tr id="row_cron_expression" style="display:none;">
            <th scope="row"><label style="color:#d32f2f;">Cron Expression *</label></th>
            <td>
                <input type="text" id="wa_camp_cron" class="regular-text" value="<?php echo esc_attr( $campaign->cron_expression ?? '' ); ?>" placeholder="e.g. 0 0 12 1 * ?">
                <p class="description">Example: 0 0 12 1 * ? (Seconds Minutes Hours Day of Month Month Day of Week Year)</p>
            </td>
        </tr>
    </table>

    <div style="margin-top:20px;">
        <button type="button" id="wa_save_campaign" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:10px 25px; font-weight:bold; border-radius:3px; font-size:14px; cursor:pointer;">
            <?php echo $campaign ? 'Update Campaign' : 'Save Campaign'; ?>
        </button>
        <?php if ( $campaign ): ?>
            <button type="button" id="wa_delete_campaign" class="button" style="background:#c62828; color:#fff; border:none; padding:10px 25px; font-weight:bold; border-radius:3px; font-size:14px; cursor:pointer; margin-left:10px;">
                Delete Campaign
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(function($) {
    var campaignId = <?php echo intval( $campaign_id ); ?>;

    $('#wa_save_campaign').on('click', function() {
        var $btn = $(this);
        var name     = $('#wa_camp_name').val().trim();
        var template = $('#wa_camp_template').val();
        var timezone = $('#wa_camp_timezone').val();
        var trigger_type = $('#wa_camp_trigger_type').val();
        var schedule = $('#wa_camp_schedule').val();
        var cron     = $('#wa_camp_cron').val();

        if (!name || !template || !timezone) {
            showMsg('error', 'Please fill in all required fields (Campaign Name, Template, Time Zone).');
            return;
        }

        if (trigger_type === 'explicit_date' && !schedule) {
            showMsg('error', 'Please provide a Schedule Time.');
            return;
        }

        if (trigger_type === 'cron' && !cron) {
            showMsg('error', 'Please provide a Cron Expression.');
            return;
        }

        $btn.text('Saving...').prop('disabled', true);

        var selectedContacts = [];
        $('#wa_contacts_list input[type=checkbox]:checked').each(function() {
            selectedContacts.push(parseInt($(this).val()));
        });

        var customerGroups = $('#wa_camp_customer_groups').val() || [];
        var targetTypeVal = $('#wa_camp_target').val();

        if (targetTypeVal === 'specific_contacts' && selectedContacts.length === 0) {
            showMsg('error', 'Please select at least one contact.');
            $btn.prop('disabled', false);
            return;
        }

        if (targetTypeVal === 'customer_groups' && customerGroups.length === 0) {
            showMsg('error', 'Please select at least one customer group.');
            $btn.prop('disabled', false);
            return;
        }

        var variableMapping = {};
        $('#wa_template_vars_container .wa-var-row').each(function() {
            var v = $(this).data('var');
            var path = $(this).find('.wa-var-field').val();
            var val = $(this).find('.wa-var-custom').val();
            variableMapping[v] = { path: path, value: val };
        });

        $.post(ajaxurl, {
            action:             'wa_save_campaign',
            campaign_id:        campaignId,
            campaign_name:      name,
            template_entity_id: template,
            target_type:        targetTypeVal,
            contact_ids:        selectedContacts,
            customer_groups:    customerGroups,
            variable_mapping:   JSON.stringify(variableMapping),
            timezone:           timezone,
            trigger_type:       trigger_type,
            schedule_time:      schedule,
            cron_expression:    cron
        }, function(resp) {
            if (resp.success) {
                showMsg('success', resp.data.message || 'Campaign saved!');
                if (!campaignId && resp.data.campaign_id) {
                    campaignId = resp.data.campaign_id;
                    $btn.text('Update Campaign');
                    // Update URL without reload
                    history.replaceState(null, '', window.location.pathname + '?page=wa-campaign-edit&id=' + campaignId);
                }
            } else {
                showMsg('error', resp.data || 'Error saving campaign.');
            }
        }).fail(function() {
            showMsg('error', 'Server error while saving campaign.');
        }).always(function() {
            $btn.text(campaignId ? 'Update Campaign' : 'Save Campaign').prop('disabled', false);
        });
    });

    <?php if ( $campaign ): ?>
    $('#wa_delete_campaign').on('click', function() {
        if (!confirm('Are you sure you want to delete this campaign?')) return;
        $.post(ajaxurl, { action: 'wa_delete_campaign', campaign_id: campaignId }, function(resp) {
            if (resp.success) {
                window.location.href = '?page=wa-campaigns';
            } else {
                showMsg('error', resp.data || 'Error deleting campaign.');
            }
        });
    });
    <?php endif; ?>

    function showMsg(type, msg) {
        var $el = $('#wa_campaign_status_msg');
        $el.text(msg)
           .css({
               display: 'block',
               background: type === 'success' ? '#e7f9ed' : '#fdeaea',
               color: type === 'success' ? '#1a7f64' : '#c53030',
               border: '1px solid ' + (type === 'success' ? '#b7e4c7' : '#f5c6cb')
           });
        setTimeout(function() { $el.fadeOut(); }, 5000);
    }

    function handleTargetChange(val) {
        if (val === 'specific_contacts') {
            $('#wa_contacts_row').show();
            $('#wa_customer_groups_row').hide();
            loadSyncedContacts(savedContactIds);
        } else if (val === 'customer_groups') {
            $('#wa_contacts_row').hide();
            $('#wa_customer_groups_row').show();
        } else {
            $('#wa_contacts_row').hide();
            $('#wa_customer_groups_row').hide();
        }
    }

    function loadSyncedContacts(preSelected) {
        preSelected = preSelected || [];
        var $list = $('#wa_contacts_list');
        $list.html('<div style="padding:10px;color:#555;">Loading...</div>');
        $.get(ajaxurl, { action: 'wa_get_synced_contacts' }, function(resp) {
            if (!resp.success || !resp.data.length) {
                $list.html('<div style="padding:12px;color:#c53030;font-size:13px;">No synced contacts found.<br>Please sync customers from the <a href="' + <?php echo wp_json_encode( admin_url('users.php') ); ?> + '">Users page</a> first.</div>');
                return;
            }
            var html = '<table style="width:100%;border-collapse:collapse;">';
            html += '<thead><tr style="background:#514943;color:#fff;"><th style="padding:8px 10px;text-align:left;font-weight:600;"><input type="checkbox" id="wa_select_all_contacts"> Select All</th><th style="padding:8px 10px;font-weight:600;">Name</th><th style="padding:8px 10px;font-weight:600;">Email</th><th style="padding:8px 10px;font-weight:600;">Phone</th></tr></thead><tbody>';
            $.each(resp.data, function(i, c) {
                var isChecked = preSelected.indexOf(parseInt(c.id, 10)) !== -1 ? 'checked' : '';
                var rowBg = i % 2 === 0 ? '#fff' : '#f9f9f9';
                if (isChecked) rowBg = '#fff3e0';
                html += '<tr style="background:' + rowBg + ';border-bottom:1px solid #eee;">';
                html += '<td style="padding:8px 10px;"><input type="checkbox" class="wa_contact_cb" value="' + c.id + '" ' + isChecked + '></td>';
                html += '<td style="padding:8px 10px;font-size:13px;" ' + (isChecked ? 'style="font-weight:bold;"' : '') + '>' + (c.name || '—') + '</td>';
                html += '<td style="padding:8px 10px;font-size:13px;color:#555;">' + c.email + '</td>';
                html += '<td style="padding:8px 10px;font-size:13px;color:#555;">' + (c.phone || '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $list.html(html);

            // Select-all checkbox
            $('#wa_select_all_contacts').on('change', function() {
                $list.find('.wa_contact_cb').prop('checked', this.checked);
            });
        });
    }

    $('#wa_camp_target').on('change', function() {
        handleTargetChange($(this).val());
    });

    function handleTriggerTypeChange(val) {
        if (val === 'cron') {
            $('#row_schedule_time').hide();
            $('#row_cron_expression').show();
        } else {
            $('#row_schedule_time').show();
            $('#row_cron_expression').hide();
        }
    }

    $('#wa_camp_trigger_type').on('change', function() {
        handleTriggerTypeChange($(this).val());
    });

    // Init: show contacts panel if editing and target is specific_contacts
    var savedContactIds = <?php echo json_encode( $saved_contact_ids ); ?>;
    var initTarget = $('#wa_camp_target').val();
    handleTargetChange(initTarget);

    var initTrigger = $('#wa_camp_trigger_type').val();
    handleTriggerTypeChange(initTrigger);

    var templateData = <?php echo json_encode($template_data); ?>;
    var existingMapping = <?php echo json_encode($variable_mapping); ?>;

    function renderTemplateVars(templateEntityId) {
        var $container = $('#wa_template_vars_container');
        $container.empty();
        
        if (!templateEntityId || !templateData[templateEntityId]) {
            $('#wa_template_setup_row').hide();
            return;
        }

        var tpl = templateData[templateEntityId];
        if (!tpl.vars || tpl.vars.length === 0) {
            $('#wa_template_setup_row').hide();
            return;
        }
        
        $('#wa_template_setup_row').show();

        var customerFields = [
            {val: '', label: '-- Select Field --'},
            {val: 'firstname', label: 'Customer First Name'},
            {val: 'lastname', label: 'Customer Last Name'},
            {val: 'name', label: 'Customer Full Name'},
            {val: 'email', label: 'Customer Email'},
            {val: 'phone', label: 'Customer Phone'}
        ];

        var html = '';
        $.each(tpl.vars, function(i, v) {
            var mapValue = existingMapping[v] || {};
            var selectedField = mapValue.path || '';
            var customVal = mapValue.value || '';

            html += '<div class="wa-var-row" data-var="' + v + '" style="background:#f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #e1e1e1; display: flex; align-items: center; justify-content: space-between; border-radius:3px;">';
            
            html += '<div style="flex: 0 0 50px;">';
            html += '<strong style="display:block; margin-bottom: 5px; font-size:13px; color:#555;">' + v + '</strong>';
            html += '<span style="background:#fef2e6; color:#ea5c0b; border:1px solid #ffd4b8; padding:3px 8px; border-radius:15px; font-size:12px; font-family:monospace;">{{' + v + '}}</span>';
            html += '</div>';

            html += '<div style="flex: 1; padding: 0 15px;">';
            html += '<select class="wa-var-field" style="width:100%;">';
            $.each(customerFields, function(j, f) {
                var sel = (selectedField === f.val) ? 'selected' : '';
                html += '<option value="' + f.val + '" ' + sel + '>' + f.label + '</option>';
            });
            html += '</select>';
            html += '</div>';

            html += '<div style="flex: 1;">';
            html += '<input type="text" class="wa-var-custom regular-text" placeholder="Custom value (optional)" value="' + customVal + '" style="width:100%; margin-bottom:4px;">';
            html += '<span style="font-size:11px; color:#999; display:block; line-height:1.2;">Tip: selecting a field clears custom value and vice-versa.</span>';
            html += '</div>';

            html += '</div>';
        });

        $container.html(html);

        // Bind events
        $container.find('.wa-var-field').on('change', function() {
            if ($(this).val() !== '') {
                $(this).closest('.wa-var-row').find('.wa-var-custom').val('');
            }
        });
        
        $container.find('.wa-var-custom').on('input', function() {
            if ($(this).val() !== '') {
                $(this).closest('.wa-var-row').find('.wa-var-field').val('');
            }
        });
    }

    $('#wa_camp_template').on('change', function() {
        renderTemplateVars($(this).val());
    });
    
    // Init template variables if editing
    var initTpl = $('#wa_camp_template').val();
    if (initTpl) {
        renderTemplateVars(initTpl);
    }
});
</script>
