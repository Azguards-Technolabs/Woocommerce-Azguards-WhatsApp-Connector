# 📱 WhatsApp Connector for WooCommerce — User Guide

> **Vendor:** Azguards Technolabs
> **Plugin Name:** `whatsapp-connector`
> **Compatible With:** WordPress 6.x & WooCommerce 8.x/9.x
> **Last Updated:** June 2026

> [!TIP]
> **Welcome!**
> This guide is for **Store Owners, Marketing Managers, and Administrators** of the WhatsApp Connector plugin for WooCommerce.

---

## 📦 Installation

### Method 1: Install via WordPress Admin (Recommended)

1. Download the plugin ZIP file (`whatsapp-connector.zip`) provided by your developer or marketplace.
2. Log in to your **WordPress Admin Dashboard**.
3. Go to **Plugins → Add New Plugin** and click **Upload Plugin** at the top.
4. Choose the downloaded ZIP file and click **Install Now**.
5. Once installed, click **Activate Plugin**.

---

### Method 2: Manual Installation (Upload via FTP/SFTP)

1. Download the plugin ZIP file and extract it. You will find a folder named `whatsapp-connector`.
2. Connect to your website via FTP/SFTP.
3. Upload the `whatsapp-connector` folder to your WordPress plugins directory:
   ```
   wp-content/plugins/whatsapp-connector/
   ```
4. Log in to your **WordPress Admin Dashboard**.
5. Go to **Plugins**. Find "WhatsApp Connector" in the list and click **Activate**.

---

### ✅ Verify Installation

After activation, confirm the plugin is active:

1. Log in to **WordPress Admin**.
2. Go to **WooCommerce** in the left sidebar menu. You should see new submenus for **WhatTack Customers**, **Campaigns**, and **WhatTack Templates**.
3. Go to **WooCommerce → Settings** — you should see a new **WhatsApp Connector** tab at the top.

If these items are visible, the plugin is successfully installed.

---

The **Azguards WhatsApp Connector** transforms your WooCommerce store into a powerful WhatsApp communication engine — automatically messaging customers at every key moment and letting your team run targeted marketing campaigns.

---

## 🗺️ 1. Admin Navigation: Where is Everything?

### ⚙️ Configuration (Initial Setup)
* **API, Abandoned Cart & Cron Settings:** `WooCommerce > Settings > WhatsApp Connector`

### 📊 Operational Dashboards (Daily Use)
* **Templates Grid:** `WooCommerce > WhatTack Templates`
* **Campaigns Grid:** `WooCommerce > Campaigns`
* **Synced Customers Grid:** `WooCommerce > WhatTack Customers`

---

## 🔑 2. Initial Setup

Before anything else works, you must connect the plugin to your WhatsApp API account.

1. Go to **`WooCommerce > Settings > WhatsApp Connector > API Settings`**
2. Set **Enable WhatsApp Connector** → `Yes`
3. Enter the credentials provided by your developer:

| Field | Description |
| :--- | :--- |
| **Authentication API URL** | The OAuth token endpoint (e.g. `https://api.whattalk.io/oauth/token`) |
| **Client ID** | Your API client ID — treat it like a username |
| **Client Secret** | Your API password — keep it confidential |

4. Click **Validate Credentials** — a success notice confirms the connection is live.
5. Click **Save changes** at the bottom of the page.

---

## ⚡ 3. Automated Order Notifications

WooCommerce fires a WhatsApp message automatically whenever any of these events occur. Each can be independently enabled/disabled in the **Template Settings** section:

| Event | When It Fires | WooCommerce Hook |
| :--- | :--- | :--- |
| **Order Created** | Customer completes checkout | `woocommerce_checkout_order_processed` |
| **Invoice Created** | Order is fully paid | `woocommerce_payment_complete` |
| **Order Shipped** | Order status changes to Completed | `woocommerce_order_status_completed` |
| **Shipment Tracking Added** | A tracking number is added via a shipment tracking plugin | `woocommerce_order_shipment_tracking_added` |
| **Order Cancelled** | Order status changes to Cancelled | `woocommerce_order_status_cancelled` |
| **Order Refunded** | Order is fully refunded | `woocommerce_order_fully_refunded` |

### Configuring a Notification Template

1. Go to **`WooCommerce > Settings > WhatsApp Connector > Template Settings`**.
2. Click the accordion for the event you wish to configure (e.g., *Order Created Notification*).
3. Check the **Enable Template** box.
4. Select an **APPROVED** template from the dropdown menu.
5. Map the WhatsApp placeholders (e.g., `{{1}}`, `{{2}}`) to WooCommerce data (e.g., `Customer First Name`, `Order ID`).
6. *(Optional)* If the template requires media, upload an Image or Video header using the **Choose Media** button.
7. Click **Save Template Settings**.

> [!IMPORTANT]
> Templates must be **approved** in your Meta/WhatsApp Partner Portal before they will appear in the dropdown.

---

## 🔄 4. Template Sync — How new templates appear in WordPress

When your marketing team creates and gets approval for new templates in the WhatsApp Partner Portal, they become available in WooCommerce **automatically** via a background sync job.

**How it works:**
1. A WordPress cron job (`wa_template_sync_event`) runs on a configurable interval.
2. It calls the API and fetches all current approved templates.
3. New templates are saved to the WordPress database; existing ones are updated.
4. They immediately appear in `WooCommerce > WhatTack Templates`.

**Configure the sync frequency:**
`WooCommerce > Settings > WhatsApp Connector > Cron Settings > Sync Templates (Mins)`
Recommended value: **60** (every hour). No manual import is needed.

**Template statuses:**

| Status | Meaning |
| :--- | :--- |
| `APPROVED` | Ready to use in campaigns and notifications |
| `PENDING` | Waiting for Meta review — cannot be used yet |
| `REJECTED` | Meta rejected it — revise and resubmit in your WhatsApp Business Manager |

---

## 👥 5. Customer Sync — How customers reach your WhatsApp platform

Customers are synced to the WhatsApp platform so they can receive campaigns.

**How it works:**
1. A cron job (`wa_contact_sync_event`) runs on a configurable interval.
2. It finds all WooCommerce customers who are not yet marked as synced.
3. It pushes them to the external contact API.
4. Once synced, they appear in **`WooCommerce > WhatTack Customers`**.

**Configure the sync frequency:**
`WooCommerce > Settings > WhatsApp Connector > Cron Settings > Sync Contacts (Mins)`
Recommended value: **60** (every hour).

---

## 🛍️ 6. Abandoned Cart Recovery

Customers who add items to their cart but don't check out can be brought back automatically.

1. Go to **`WooCommerce > Settings > WhatsApp Connector > Abandoned Cart Settings`**
2. Check **Enable Abandoned Cart**.
3. Set the key fields:

| Field | Recommended | Description |
| :--- | :--- | :--- |
| **Abandon Trigger Delay (Mins)** | `60` | Minutes of inactivity before a WooCommerce session is treated as abandoned. |
| **Template** | `Select Template` | Choose the approved reminder template to send. |

4. Configure the variables exactly like the order notification templates above.
5. Click **Save Default Mapping** and then **Save changes**.

**Key guarantee:** Each WooCommerce cart session triggers **at most one message**. The plugin tracks every notified cart in its database — customers will never be spammed for the same cart twice.

---

## 🎯 7. Marketing Campaigns

Send a WhatsApp blast to a group of customers at a scheduled time.

### Creating a Campaign

1. Go to **`WooCommerce > Campaigns`** → Click **Add New Campaign** at the top.
2. Fill in:
   - **Campaign Name** — internal label (e.g. *Eid Sale 2026*)
   - **Select Template** — only `APPROVED` templates appear
   - **Target Audience** — select predefined groups or specific contacts
   - **Variable Mapping** — fill in placeholder values for template variables
   - **Schedule Data/Time** — time to deploy the blast
3. Click **Save Campaign**.

**Campaign Statuses:**
| Status | Meaning |
| :--- | :--- |
| `pending` | Waiting for the scheduled time |
| `processing` | Currently being dispatched |
| `sent` | All messages delivered |
| `failed` | Campaign failed |

> [!TIP]
> If a campaign fails, you can select it from the Campaign Grid and choose **Retry** from the Bulk Actions dropdown.

---

## ⚙️ 8. Cron (Background Job) Settings

`WooCommerce > Settings > WhatsApp Connector > Cron Settings`

| Setting | What it Controls | Recommended |
| :--- | :--- | :--- |
| **Sync Campaign (Mins)** | How often campaign statuses are pulled | `5` |
| **Sync Contacts (Mins)** | How often new customers are pushed | `60` |
| **Sync Templates (Mins)** | How often approved templates are pulled | `60` |

> [!NOTE]
> The plugin automatically integrates with **WooCommerce Action Scheduler** for highly reliable background processing.

---

## ⚕️ 9. Troubleshooting & FAQs

**Q: My order notifications are not sending.**
> Verify the API Credentials are valid (`WooCommerce -> Settings -> WhatsApp Connector -> API Settings`). Also ensure the required Template is set to **Enable** and an `APPROVED` template is assigned.

**Q: I get a "Security check failed" when saving templates.**
> The plugin expects you to have WooCommerce Administrator access. If you see this, perform a hard refresh (`Ctrl + Shift + R`) on the browser and save again.

**Q: Templates grid is empty.**
> Confirm at least one template is `APPROVED` in your Meta portal. Validate credentials in the API Settings, then wait for the next Template Sync cron to run (or trigger it manually if you have a cron control plugin).

**Q: Will customers get duplicate abandoned cart messages?**
> No. Each cart session is tracked in the `azguards_whatsapp_abandoned_cart` database table and can only ever trigger one notification.

**Q: Can I use video headers in Order Confirmations?**
> Yes! The unified Template Builder enables you to attach `IMAGE`, `VIDEO`, or `DOCUMENT` headers seamlessly directly from the WooCommerce admin.

---

## ✅ 10. Best Practices

- **Test Before Launch:** Register a test account and place an order to ensure the variables (like First Name and Order ID) format correctly.
- **Set abandoned cart threshold to 60+ minutes** — shorter risks interrupting active shoppers.
- **Review your Action Scheduler** (`WooCommerce > Status > Scheduled Actions`) periodically to ensure cron jobs are running smoothly.

---

*Powered by Azguards Technolabs.*
