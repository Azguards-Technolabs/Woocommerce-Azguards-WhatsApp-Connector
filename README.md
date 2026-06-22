# WhatsApp Connector for WooCommerce

A premium, enterprise-grade WooCommerce plugin to achieve full feature parity with Magento WhatsApp Connector implementations. This plugin enables seamless communication between your WooCommerce store and the WhatTack WhatsApp API.

## 🚀 Key Features

### 📦 Order & Shipping Notifications
- **Shipment Tracking**: Automatic notifications when tracking info is added, with built-in duplicate prevention.
- **Order Events**: Custom templates for Order Created, Processing, Completed, Failed, and more.
- **Stock Alerts**: Low-stock and Out-of-stock notifications for store admins.

### 📅 Campaign Management
- **Visual Builder**: Premium, card-based interface for creating and managing WhatsApp marketing campaigns.
- **Queue Worker**: High-performance batch processing using WooCommerce Action Scheduler for reliable delivery.
- **Targeting**: Send campaigns to specific user roles, manual contact selections, or all synced customers.

### 🎨 Premium Template Builder
- **Carousel Support**: Multi-card templates with media and individual button actions.
- **Live Preview**: Real-time iPhone-style mockup to preview messages as they appear on WhatsApp.
- **Dynamic Variables**: Easily insert customer fields like `{{firstname}}`, `{{order_id}}`, etc., into your templates.

## 🛠 Technical Setup

### Installation
1. Upload the `whatsapp-connector` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure your API credentials in **WooCommerce > Settings > WhatsApp Connector**.

### Authentication
The plugin uses a secure, transient-based authentication system:
- **Automatic Refresh**: Tokens are cached and refreshed automatically before expiration.
- **JWT Extraction**: Automatically resolves `businessId` and `userId` from the secure token payload.

### API Integration
- **Endpoint**: `https://whatatalk-api.azguardstech.com/`
- **Hook Integration**: Uses standard WooCommerce hooks for maximum compatibility with 3rd party shipping plugins.

## 🔒 Security & Performance
- **ABSPATH Protection**: All files are protected against direct access.
- **Action Scheduler**: Offloads heavy API tasks to the background to keep your admin dashboard fast.
- **Meta Status Sync**: Real-time synchronization of template approval statuses from Meta.

---
© 2026 Azguards Technolabs. All Rights Reserved.
