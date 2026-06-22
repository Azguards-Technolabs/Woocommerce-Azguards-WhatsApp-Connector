# WhatsApp Connector for WooCommerce

A premium, enterprise-grade WooCommerce plugin to achieve full feature parity with Magento WhatsApp Connector implementations. This plugin enables seamless communication between your WooCommerce store and the WhatTack WhatsApp API.

## 🚀 Deployment Guide

### Phase 1: Preparation
1.  Compress the `whatsapp-connector` folder into a `whatsapp-connector.zip` file.

### Phase 2: Upload & Activation
1.  Go to **Plugins > Add New > Upload Plugin** in your WordPress Admin.
2.  Upload the ZIP and click **Activate**.

### Phase 3: Configuration
1.  Navigate to **WooCommerce > Settings > WhatsApp Connector**.
2.  Enter your **Client ID** and **Client Secret**.
3.  Click **Validate Credentials** to fetch your access token.
4.  Go to **WooCommerce > WhatTack Templates** and click **Sync Templates**.

### Phase 4: Mapping
1.  In the **Templates** tab of the settings, select a template for each event.
2.  Map the placeholders (e.g., `{{1}}` to Order ID) and **Save**.


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

© 2026 Azguards Technolabs. All Rights Reserved.

---