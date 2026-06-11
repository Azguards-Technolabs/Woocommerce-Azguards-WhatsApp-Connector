<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">Edit Campaign</h1>
        <div>
            <a href="?page=wa-campaigns" style="text-decoration:none; color:#333; font-weight:bold; margin-right:15px;">&larr; Back</a>
            <a href="#" style="text-decoration:none; color:#333; font-weight:bold; margin-right:15px;">Delete</a>
            <a href="#" style="text-decoration:none; color:#333; font-weight:bold; margin-right:20px;">Save and Continue Edit</a>
            <button class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Save Campaign</button>
        </div>
    </div>

    <table class="form-table">
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Campaign Name *</label></th>
            <td><input type="text" class="regular-text" value="Weekend Food Offer"></td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Template *</label></th>
            <td>
                <select>
                    <option>order_delivered</option>
                    <option>abandoned_cart_reminder</option>
                </select>
                <div style="margin-top:10px; padding:15px; border:1px solid #ddd; border-radius:5px; background:#fcfcfc;">
                    <p style="font-weight:bold; margin-top:0;">Template Setup</p>
                    <p style="font-size:12px; color:#666;">Map each template variable to an automatic customer field, or type a custom value.</p>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <span style="background:#fee9cb; color:#ea5c0b; padding:5px 10px; border-radius:3px; font-weight:bold;">{{1}}</span>
                        <select style="width:150px;"><option>Customer First Name</option></select>
                        <input type="text" placeholder="Custom value (optional)" style="width:150px;">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="background:#fee9cb; color:#ea5c0b; padding:5px 10px; border-radius:3px; font-weight:bold;">{{2}}</span>
                        <select style="width:150px;"><option>Customer Last Name</option></select>
                        <input type="text" placeholder="Custom value (optional)" style="width:150px;">
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Send Template To *</label></th>
            <td>
                <select><option>Specific Contacts</option></select>
                <div style="margin-top:10px; border:1px solid #ddd; border-radius:5px;">
                    <div style="background:#f1f1f1; padding:10px; font-weight:bold; display:flex; justify-content:space-between; border-bottom:1px solid #ddd;">
                        <span>👥 Specific Contacts</span>
                        <button class="button button-small">Clear All</button>
                    </div>
                    <div style="padding:15px;">
                        <ul style="margin:0; padding:0; list-style:none;">
                            <li style="margin-bottom:5px;"><span style="color:red; cursor:pointer;">✖</span> Ronald Whitten - 1234567</li>
                            <li style="margin-bottom:5px;"><span style="color:red; cursor:pointer;">✖</span> Jainam barkoliya - 972-781-5545</li>
                            <li style="margin-bottom:5px;"><span style="color:red; cursor:pointer;">✖</span> ddas dasdas - 972-761-5542</li>
                        </ul>
                        <div style="margin-top:15px; color:#0073aa; font-size:12px; cursor:pointer;">&#8853; Search for customers by name, email, or phone number. Click to browse all.</div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Schedule Time *</label></th>
            <td><input type="text" value="05/15/2026 07:10"></td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Time Zone *</label></th>
            <td><select><option>UTC (00:00)</option></select></td>
        </tr>
        <tr>
            <th scope="row"><label style="color:#d32f2f;">Trigger Type *</label></th>
            <td><select><option>Single Time</option></select></td>
        </tr>
    </table>
</div>
