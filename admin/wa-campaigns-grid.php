<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Dummy processing logic for demo
$campaigns = [
    ['id' => 1, 'name' => 'demo_test_sat_may', 'template' => 'order_delivered', 'groups' => '', 'time' => 'May 15, 2026 7:10:00 AM', 'status' => 'paused'],
    ['id' => 2, 'name' => 'Weekend Food Offer', 'template' => 'abandoned_cart_reminder', 'groups' => 'General', 'time' => 'May 30, 2026 6:03:00 AM', 'status' => 'SCHEDULED'],
    ['id' => 3, 'name' => 'Customer Loyalty Offer', 'template' => 'order_confirmation', 'groups' => 'Retailer', 'time' => 'May 29, 2026 6:06:00 AM', 'status' => 'SCHEDULED'],
    ['id' => 4, 'name' => 'Premium Villa Launch', 'template' => 'abandoned_cart_reminder', 'groups' => 'Retailer', 'time' => 'May 29, 2026 6:16:00 AM', 'status' => 'SCHEDULED'],
];
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">WhatsApp Campaigns</h1>
        <div>
            <a href="#" style="text-decoration:none; color:#333; font-weight:bold; margin-right:20px;">Get All Campaign</a>
            <a href="?page=wa-campaign-edit" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Create Campaign</a>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <div>
            <select><option>Actions</option></select>
            <span style="margin-left:15px; color:#777;">4 records found</span>
        </div>
        <div>
            <button class="button">Filters</button>
            <select><option>Default View</option></select>
            <select><option>Columns</option></select>
            <span style="margin-left:20px;">
                <select><option>20</option></select> per page
                <button class="button">&lt;</button>
                <input type="text" value="1" style="width:40px; text-align:center;"> of 1
                <button class="button">&gt;</button>
            </span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:10px; border:none;">
        <thead style="background:#4d443c; color:#fff;">
            <tr>
                <td style="width:40px; text-align:center;"><input type="checkbox"></td>
                <th style="color:#fff; padding:10px;">ID</th>
                <th style="color:#fff; padding:10px;">Campaign Name</th>
                <th style="color:#fff; padding:10px;">Template</th>
                <th style="color:#fff; padding:10px;">Customer Groups</th>
                <th style="color:#fff; padding:10px;">Schedule Time</th>
                <th style="color:#fff; padding:10px;">Status</th>
                <th style="color:#fff; padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($campaigns as $camp): ?>
            <tr>
                <td style="text-align:center;"><input type="checkbox"></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $camp['id']; ?></td>
                <td style="padding:10px; vertical-align:middle; color:#0073aa;"><?php echo $camp['name']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $camp['template']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $camp['groups']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $camp['time']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $camp['status']; ?></td>
                <td style="padding:10px; vertical-align:middle; color:#0073aa;">
                    Select ▼
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
