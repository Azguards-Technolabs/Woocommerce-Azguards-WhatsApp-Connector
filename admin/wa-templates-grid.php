<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Dummy processing logic for demo
$templates = [
    ['name' => 'abandoned_cart_recovery', 'type' => 'MEDIA', 'category' => 'Marketing', 'language' => 'en_US', 'status' => 'PENDING', 'created' => 'May 12, 2026 4:11:24 AM'],
    ['name' => 'flash_sale_urgency', 'type' => 'MEDIA', 'category' => 'Marketing', 'language' => 'en_US', 'status' => 'PENDING', 'created' => 'May 12, 2026 4:11:24 AM'],
    ['name' => 'this_is_an_order_status_template2', 'type' => 'TEXT', 'category' => 'Utility', 'language' => 'en_US', 'status' => 'approved', 'created' => 'May 12, 2026 3:56:52 AM'],
];
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">WhatsApp Templates</h1>
        <div>
            <a href="#" style="text-decoration:none; color:#333; font-weight:bold; margin-right:20px;">Sync Templates</a>
            <a href="?page=wa-template-builder" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Create Template</a>
        </div>
    </div>

    <!-- Toolbar Exact Match -->
    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <div>
            <select><option>Actions</option></select>
            <span style="margin-left:15px; color:#777;">48 records found</span>
        </div>
        <div>
            <button class="button">Filters</button>
            <select><option>Default View</option></select>
            <select><option>Columns</option></select>
            <span style="margin-left:20px;">
                <select><option>50</option></select> per page
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
                <th style="color:#fff; padding:10px;">Template Name</th>
                <th style="color:#fff; padding:10px;">Type</th>
                <th style="color:#fff; padding:10px;">Category</th>
                <th style="color:#fff; padding:10px;">Language Code</th>
                <th style="color:#fff; padding:10px;">Status</th>
                <th style="color:#fff; padding:10px;">Created At</th>
                <th style="color:#fff; padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($templates as $tmp): ?>
            <tr>
                <td style="text-align:center;"><input type="checkbox"></td>
                <td style="padding:10px; vertical-align:middle; color:#0b6a9c;"><?php echo $tmp['name']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $tmp['type']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $tmp['category']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $tmp['language']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $tmp['status']; ?></td>
                <td style="padding:10px; vertical-align:middle;"><?php echo $tmp['created']; ?></td>
                <td style="padding:10px; vertical-align:middle; color:#0b6a9c;">
                    Select ▼
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
