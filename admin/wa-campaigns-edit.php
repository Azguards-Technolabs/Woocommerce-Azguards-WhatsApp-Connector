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
$templates = $wpdb->get_results( "SELECT entity_id, template_name, template_id, body FROM $tpl_table ORDER BY template_name ASC" );

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
if (!is_array($selected_groups)) $selected_groups = [];
$variable_mapping = isset($campaign->variable_mapping) ? json_decode($campaign->variable_mapping, true) : [];
if (!is_array($variable_mapping)) $variable_mapping = [];

// Pre-load saved contact IDs for pre-selection on edit
$saved_contact_ids = [];
if ( $campaign && ! empty( $campaign->contact_ids ) ) {
    $decoded = json_decode( $campaign->contact_ids, true );
    if ( is_array( $decoded ) ) $saved_contact_ids = array_map( 'intval', $decoded );
}

$page_title = $campaign ? 'Edit Campaign' : 'Create Campaign';

// Enqueue Google Fonts
wp_enqueue_style( 'wa-google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], null );
?>
<style>
/* ---- Campaign Edit — Premium Design ---- */
:root {
    --wac-green:   #25d366;
    --wac-green-dk:#0e8f5a;
    --wac-accent:  #ea5c0b;
    --wac-red:     #e53e3e;
    --wac-border:  #e2e6ea;
    --wac-bg:      #f4f6f8;
    --wac-text:    #1a1f36;
    --wac-muted:   #6b7280;
    --wac-surface: #ffffff;
    --wac-radius:  10px;
    --wac-shadow:  0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --wac-shadow-md: 0 4px 16px rgba(0,0,0,.08);
}
.wac-wrap * { box-sizing: border-box; font-family: 'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }

/* Page header */
.wac-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 2px solid var(--wac-border);
}
.wac-header h1 {
    font-size: 22px !important;
    font-weight: 700 !important;
    color: var(--wac-text) !important;
    margin: 0 !important;
    letter-spacing: -.3px;
}
.wac-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--wac-muted) !important;
    background: var(--wac-bg);
    border: 1px solid var(--wac-border);
    border-radius: 6px;
    padding: 7px 16px;
    text-decoration: none !important;
    transition: all .18s ease;
    box-shadow: var(--wac-shadow);
}
.wac-back-btn:hover {
    background: var(--wac-green) !important;
    color: #fff !important;
    border-color: var(--wac-green);
}

/* Status message */
.wac-status-msg {
    display: none;
    padding: 10px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 20px;
}
.wac-status-msg.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.wac-status-msg.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

/* Form table - premium */
.wac-wrap .form-table {
    background: var(--wac-surface);
    border: 1px solid var(--wac-border);
    border-radius: var(--wac-radius) !important;
    box-shadow: var(--wac-shadow);
    border-collapse: collapse;
    width: 100%;
    overflow: hidden;
}
.wac-wrap .form-table tr { border-bottom: 1px solid var(--wac-border); }
.wac-wrap .form-table tr:last-child { border-bottom: none; }
.wac-wrap .form-table th {
    width: 210px;
    padding: 16px 20px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: var(--wac-text) !important;
    background: var(--wac-bg);
    vertical-align: top;
}
.wac-wrap .form-table td {
    padding: 14px 20px !important;
    vertical-align: middle;
}
.wac-wrap .form-table label.required::after {
    content: ' *';
    color: var(--wac-red);
}

/* Inputs / selects */
.wac-wrap .form-table input[type="text"],
.wac-wrap .form-table input[type="datetime-local"],
.wac-wrap .form-table select,
.wac-wrap .form-table textarea,
.wac-wrap #wa_camp_customer_groups {
    width: 100%;
    max-width: 480px;
    padding: 9px 12px !important;
    border: 1.5px solid var(--wac-border) !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    font-family: inherit !important;
    color: var(--wac-text) !important;
    background: var(--wac-surface) !important;
    transition: all .18s !important;
    -webkit-appearance: none;
    appearance: none;
    box-shadow: var(--wac-shadow) !important;
}
.wac-wrap .form-table input:focus,
.wac-wrap .form-table select:focus,
.wac-wrap #wa_camp_customer_groups:focus {
    border-color: var(--wac-green) !important;
    box-shadow: 0 0 0 3px rgba(37,211,102,.12) !important;
    outline: none !important;
}
.wac-wrap .form-table p.description {
    font-size: 12px;
    color: var(--wac-muted);
    margin-top: 6px;
}

/* Template setup card */
.wac-template-setup-card {
    border: 1px solid var(--wac-border);
    border-radius: var(--wac-radius);
    background: var(--wac-bg);
    overflow: hidden;
    max-width: 600px;
}
.wac-template-setup-card-header {
    padding: 12px 16px;
    background: var(--wac-surface);
    border-bottom: 1px solid var(--wac-border);
    font-size: 13px;
    font-weight: 600;
    color: var(--wac-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    display: flex;
    align-items: center;
    gap: 7px;
}
.wac-template-setup-card-header::before {
    content: '';
    display: inline-block;
    width: 3px; height: 14px;
    background: var(--wac-accent);
    border-radius: 2px;
}
.wac-template-setup-card-body { padding: 14px 16px; }
.wac-desc-small { font-size: 12px; color: var(--wac-muted); margin: 0 0 12px; }

/* Var rows */
.wa-var-row {
    background: var(--wac-surface) !important;
    border: 1px solid var(--wac-border) !important;
    border-radius: 6px !important;
    padding: 12px !important;
    margin-bottom: 10px !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}
.wa-var-badge-pill {
    display: inline-block;
    background: #fff3ee;
    color: var(--wac-accent);
    border: 1px solid #ffd4b8;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-family: monospace;
    margin-top: 4px;
    white-space: nowrap;
}
.wa-var-label { font-size: 12px; font-weight: 600; color: var(--wac-muted); margin-bottom: 3px; }

/* Contacts list */
#wa_contacts_list {
    border: 1px solid var(--wac-border) !important;
    max-height: 240px;
    overflow-y: auto;
    border-radius: var(--wac-radius) !important;
    background: var(--wac-surface) !important;
}

/* Save bar */
.wac-action-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
    padding: 18px 20px;
    background: var(--wac-surface);
    border: 1px solid var(--wac-border);
    border-radius: var(--wac-radius);
    box-shadow: var(--wac-shadow);
}
#wa_save_campaign {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Inter', sans-serif !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    padding: 10px 26px !important;
    border-radius: 6px !important;
    background: linear-gradient(135deg, #25d366 0%, #0e8f5a 100%) !important;
    border: none !important;
    color: #fff !important;
    cursor: pointer !important;
    transition: all .18s !important;
    box-shadow: 0 4px 14px rgba(37,211,102,.4) !important;
}
#wa_save_campaign:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 22px rgba(37,211,102,.5) !important; }
#wa_save_campaign:disabled { opacity:.7 !important; transform:none !important; cursor:wait !important; }
#wa_delete_campaign {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Inter', sans-serif !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    padding: 10px 22px !important;
    border-radius: 6px !important;
    background: #fff !important;
    border: 1.5px solid #fecaca !important;
    color: var(--wac-red) !important;
    cursor: pointer !important;
    transition: all .18s !important;
}
#wa_delete_campaign:hover { background: #fef2f2 !important; }
</style>

<div class="wac-wrap wrap">
    <div class="wac-header">
        <h1><?php echo esc_html( $page_title ); ?></h1>
        <a href="<?php echo esc_url( admin_url('admin.php?page=wa-campaigns') ); ?>" class="wac-back-btn">
            &larr; Back to Campaigns
        </a>
    </div>

    <div id="wa_campaign_status_msg" class="wac-status-msg"></div>

    <table class="form-table">
        <tr>
            <th scope="row"><label class="required">Campaign Name</label></th>
            <td>
                <input type="text" id="wa_camp_name" value="<?php echo esc_attr( $campaign->campaign_name ?? '' ); ?>" placeholder="e.g. Summer Sale Promo">
                <p class="description">Internal campaign name for admin reference. Customers will not see this name.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label class="required">Template</label></th>
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
                    <p class="description" style="color:#991b1b;">No templates found. Please <a href="?page=wa-templates-grid">sync or create templates</a> first.</p>
                <?php else: ?>
                    <p class="description">Choose the approved WhatsApp template that will be sent to the selected customers.</p>
                <?php endif; ?>
            </td>
        </tr>
        <tr id="wa_template_setup_row" style="display:none;">
            <th scope="row"><label>Template Setup</label></th>
            <td>
                <div class="wac-template-setup-card">
                    <div class="wac-template-setup-card-header">Template Variable Mapping</div>
                    <div class="wac-template-setup-card-body">
                        <p class="wac-desc-small">Map every template placeholder to a customer field, or type a fixed custom value. Example: {{firstname}} can use Customer First Name, while {{coupon}} can use a custom coupon code.</p>
                        <div id="wa_template_vars_container"></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr id="wa_send_to_row">
            <th scope="row"><label class="required">Send To</label></th>
            <td>
                <select id="wa_camp_target">
                    <option value="customer_groups" <?php selected( $campaign->target_type ?? '', 'customer_groups' ); ?>>Customer Groups</option>
                    <option value="all_contacts"    <?php selected( $campaign->target_type ?? '', 'all_contacts' ); ?>>All Synced Contacts</option>
                    <option value="specific_contacts" <?php selected( $campaign->target_type ?? '', 'specific_contacts' ); ?>>Specific Contacts</option>
                </select>
                <p class="description">Select who should receive this campaign: role groups, every synced contact, or manually selected contacts.</p>
            </td>
        </tr>
        <tr id="wa_customer_groups_row" style="display:none;">
            <th scope="row"><label>Customer Groups</label></th>
            <td>
                <select id="wa_camp_customer_groups" multiple style="height:130px;">
                    <?php foreach ($customer_groups as $role_key => $role_name): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php strpos(wp_json_encode($selected_groups, true), $role_key) !== false ? print 'selected' : ''; ?>>
                            <?php echo esc_html($role_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Use this when the campaign is for specific WordPress/WooCommerce roles. Hold Ctrl/Cmd to select multiple groups.</p>
            </td>
        </tr>
        <tr id="wa_contacts_row" style="display:none;">
            <th scope="row"><label>Specific Contacts</label></th>
            <td>
                <div id="wa_contacts_list">
                    <div style="padding:14px;color:#6b7280;font-size:13px;">Loading synced contacts…</div>
                </div>
                <p class="description" style="margin-top:8px;">Only customers who have been synced to WhatTack are listed. <a href="<?php echo admin_url('users.php'); ?>">Sync more customers &rarr;</a></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label class="required">Time Zone</label></th>
            <td>
                <select id="wa_camp_timezone">
                    <?php echo wp_timezone_choice( $campaign->timezone ?? wp_timezone_string() ); ?>
                </select>
                <p class="description">Campaign schedule is calculated in this timezone.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label class="required">Trigger Type</label></th>
            <td>
                <select id="wa_camp_trigger_type">
                    <option value="explicit_date" <?php selected( $campaign->trigger_type ?? '', 'explicit_date' ); ?>>Specific Time</option>
                    <option value="cron"          <?php selected( $campaign->trigger_type ?? '', 'cron' ); ?>>Recurring (Cron)</option>
                </select>
                <p class="description">Use Specific Time for one-time campaigns. Use Recurring when the campaign should repeat by cron expression.</p>
            </td>
        </tr>
        <tr id="row_schedule_time">
            <th scope="row"><label class="required">Schedule Time</label></th>
            <td>
                <input type="datetime-local" id="wa_camp_schedule"
                    value="<?php echo !empty($campaign->schedule_time) && $campaign->schedule_time !== '0000-00-00 00:00:00' ? esc_attr( date('Y-m-d\TH:i', strtotime($campaign->schedule_time)) ) : ''; ?>">
                <p class="description">Date and time when this one-time campaign should be sent.</p>
            </td>
        </tr>
        <tr id="row_cron_expression" style="display:none;">
            <th scope="row"><label class="required">Cron Expression</label></th>
            <td>
                <input type="text" id="wa_camp_cron" value="<?php echo esc_attr( $campaign->cron_expression ?? '' ); ?>" placeholder="e.g. 0 0 12 1 * ?">
                <p class="description">Example: 0 0 12 1 * ? (Seconds Minutes Hours Day-of-Month Month Day-of-Week Year)</p>
            </td>
        </tr>
    </table>

    <div class="wac-action-bar">
        <button type="button" id="wa_save_campaign">
            💾 <?php echo $campaign ? 'Update Campaign' : 'Save Campaign'; ?>
        </button>
        <?php if ( $campaign ): ?>
        <button type="button" id="wa_delete_campaign">
            🗑 Delete Campaign
        </button>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(function($) {
    var campaignId = <?php echo intval( $campaign_id ); ?>;

    function showMsg(type, msg) {
        var $el = $('#wa_campaign_status_msg');
        $el.text(msg).removeClass('success error').addClass(type).show();
        setTimeout(function() { $el.fadeOut(400, function(){ $el.removeClass('success error'); }); }, 5000);
    }

    $('#wa_save_campaign').on('click', function() {
        var $btn = $(this);
        var name         = $('#wa_camp_name').val().trim();
        var template     = $('#wa_camp_template').val();
        var timezone     = $('#wa_camp_timezone').val();
        var trigger_type = $('#wa_camp_trigger_type').val();
        var schedule     = $('#wa_camp_schedule').val();
        var cron         = $('#wa_camp_cron').val();

        if (!name || !template || !timezone) {
            showMsg('error', 'Please fill in all required fields (Campaign Name, Template, Time Zone).');
            return;
        }
        if (trigger_type === 'explicit_date' && !schedule) { showMsg('error', 'Please provide a Schedule Time.'); return; }
        if (trigger_type === 'cron' && !cron)              { showMsg('error', 'Please provide a Cron Expression.'); return; }

        $btn.prop('disabled', true).html('⏳ Saving…');

        var selectedContacts = [];
        $('#wa_contacts_list input[type=checkbox]:checked').each(function() { selectedContacts.push(parseInt($(this).val())); });
        var customerGroups = $('#wa_camp_customer_groups').val() || [];
        var targetTypeVal  = $('#wa_camp_target').val();

        if (targetTypeVal === 'specific_contacts' && selectedContacts.length === 0) { showMsg('error', 'Please select at least one contact.'); $btn.prop('disabled', false).html('💾 ' + (campaignId ? 'Update Campaign' : 'Save Campaign')); return; }
        if (targetTypeVal === 'customer_groups'   && customerGroups.length === 0)   { showMsg('error', 'Please select at least one customer group.'); $btn.prop('disabled', false).html('💾 ' + (campaignId ? 'Update Campaign' : 'Save Campaign')); return; }

        var variableMapping = {};
        $('#wa_template_vars_container .wa-var-row').each(function() {
            var v    = $(this).data('var');
            var path = $(this).find('.wa-var-field').val();
            var val  = $(this).find('.wa-var-custom').val();
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
                    history.replaceState(null, '', window.location.pathname + '?page=wa-campaign-edit&id=' + campaignId);
                }
            } else {
                showMsg('error', resp.data || 'Error saving campaign.');
            }
        }).fail(function() {
            showMsg('error', 'Server error while saving campaign.');
        }).always(function() {
            $btn.prop('disabled', false).html('💾 ' + (campaignId ? 'Update Campaign' : 'Save Campaign'));
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

    function handleTargetChange(val) {
        if (val === 'specific_contacts')  { $('#wa_contacts_row').show(); $('#wa_customer_groups_row').hide(); loadSyncedContacts(savedContactIds); }
        else if (val === 'customer_groups') { $('#wa_contacts_row').hide(); $('#wa_customer_groups_row').show(); }
        else { $('#wa_contacts_row').hide(); $('#wa_customer_groups_row').hide(); }
    }

    function loadSyncedContacts(preSelected) {
        preSelected = preSelected || [];
        var $list = $('#wa_contacts_list');
        $list.html('<div style="padding:14px;color:#6b7280;font-size:13px;">Loading…</div>');
        $.get(ajaxurl, { action: 'wa_get_synced_contacts' }, function(resp) {
            if (!resp.success || !resp.data.length) {
                $list.html('<div style="padding:14px;color:#991b1b;font-size:13px;">No synced contacts found.<br>Please sync customers from the <a href="' + <?php echo wp_json_encode( admin_url('users.php') ); ?> + '">Users page</a> first.</div>');
                return;
            }
            var html = '<table style="width:100%;border-collapse:collapse;font-family:inherit;">';
            html += '<thead><tr style="background:#1a1f36;color:#fff;"><th style="padding:10px 12px;text-align:left;font-size:12px;font-weight:600;"><input type="checkbox" id="wa_select_all_contacts"> Select All</th><th style="padding:10px 12px;font-size:12px;font-weight:600;">Name</th><th style="padding:10px 12px;font-size:12px;font-weight:600;">Email</th><th style="padding:10px 12px;font-size:12px;font-weight:600;">Phone</th></tr></thead><tbody>';
            $.each(resp.data, function(i, c) {
                var isChecked = preSelected.indexOf(parseInt(c.id, 10)) !== -1 ? 'checked' : '';
                var rowBg = isChecked ? '#f0fdf4' : (i % 2 === 0 ? '#fff' : '#f9fafb');
                html += '<tr style="background:' + rowBg + ';border-bottom:1px solid #e2e6ea;">';
                html += '<td style="padding:9px 12px;"><input type="checkbox" class="wa_contact_cb" value="' + c.id + '" ' + isChecked + '></td>';
                html += '<td style="padding:9px 12px;font-size:13px;font-weight:' + (isChecked ? '600' : '400') + ';">' + (c.name || '—') + '</td>';
                html += '<td style="padding:9px 12px;font-size:13px;color:#6b7280;">' + c.email + '</td>';
                html += '<td style="padding:9px 12px;font-size:13px;color:#6b7280;">' + (c.phone || '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $list.html(html);
            $('#wa_select_all_contacts').on('change', function() { $list.find('.wa_contact_cb').prop('checked', this.checked); });
        });
    }

    $('#wa_camp_target').on('change', function() { handleTargetChange($(this).val()); });

    function handleTriggerTypeChange(val) {
        if (val === 'cron') { $('#row_schedule_time').hide(); $('#row_cron_expression').show(); }
        else                { $('#row_schedule_time').show(); $('#row_cron_expression').hide(); }
    }
    $('#wa_camp_trigger_type').on('change', function() { handleTriggerTypeChange($(this).val()); });

    var savedContactIds = <?php echo json_encode( $saved_contact_ids ); ?>;
    handleTargetChange($('#wa_camp_target').val());
    handleTriggerTypeChange($('#wa_camp_trigger_type').val());

    var templateData    = <?php echo json_encode($template_data); ?>;
    var existingMapping = <?php echo json_encode($variable_mapping); ?>;

    function renderTemplateVars(templateEntityId) {
        var $container = $('#wa_template_vars_container');
        $container.empty();
        if (!templateEntityId || !templateData[templateEntityId]) { $('#wa_template_setup_row').hide(); return; }
        var tpl = templateData[templateEntityId];
        if (!tpl.vars || tpl.vars.length === 0) { $('#wa_template_setup_row').hide(); return; }
        $('#wa_template_setup_row').show();

        var customerFields = [
            {val: '', label: '-- Select Field --'},
            {val: 'firstname', label: 'Customer First Name'},
            {val: 'lastname',  label: 'Customer Last Name'},
            {val: 'name',      label: 'Customer Full Name'},
            {val: 'email',     label: 'Customer Email'},
            {val: 'phone',     label: 'Customer Phone'}
        ];

        var html = '';
        $.each(tpl.vars, function(i, v) {
            var mapValue      = existingMapping[v] || {};
            var selectedField = mapValue.path  || '';
            var customVal     = mapValue.value || '';

            html += '<div class="wa-var-row" data-var="' + v + '">';

            html += '<div style="flex:0 0 110px;">';
            html += '<div class="wa-var-label">' + (i + 1) + '. Variable</div>';
            html += '<span class="wa-var-badge-pill">{{' + v + '}}</span>';
            html += '</div>';

            html += '<div style="flex:1;">';
            html += '<select class="wa-var-field" style="width:100%;padding:8px 10px;border:1.5px solid #e2e6ea;border-radius:6px;font-size:13px;font-family:inherit;background:#fff;">';
            $.each(customerFields, function(j, f) {
                var sel = (selectedField === f.val) ? 'selected' : '';
                html += '<option value="' + f.val + '" ' + sel + '>' + f.label + '</option>';
            });
            html += '</select></div>';

            html += '<div style="flex:1;">';
            html += '<input type="text" class="wa-var-custom" placeholder="Or type a custom value…" value="' + customVal + '" style="width:100%;padding:8px 10px;border:1.5px solid #e2e6ea;border-radius:6px;font-size:13px;font-family:inherit;">';
            html += '<span style="font-size:11px;color:#9ca3af;display:block;margin-top:3px;">Tip: selecting a field clears custom value and vice-versa.</span>';
            html += '</div>';

            html += '</div>';
        });
        $container.html(html);

        $container.find('.wa-var-field').on('change', function() {
            if ($(this).val() !== '') $(this).closest('.wa-var-row').find('.wa-var-custom').val('');
        });
        $container.find('.wa-var-custom').on('input', function() {
            if ($(this).val() !== '') $(this).closest('.wa-var-row').find('.wa-var-field').val('');
        });
    }

    $('#wa_camp_template').on('change', function() { renderTemplateVars($(this).val()); });
    var initTpl = $('#wa_camp_template').val();
    if (initTpl) renderTemplateVars(initTpl);
});
</script>
