# Umami Connect

> Simple, privacy-focused analytics for WordPress — powered by [Umami](https://umami.is).

**Umami Connect** seamlessly integrates Umami Analytics into your WordPress site with **zero configuration complexity**. Works with both **Umami Cloud** and **Self-hosted** installations. Auto-track links, buttons, forms, and custom events right from the Gutenberg editor.

[![Latest Release](https://img.shields.io/github/v/release/ceviixx/umami-wp-connect?label=Latest)](https://github.com/ceviixx/umami-wp-connect/releases/latest)
[![GitHub](https://img.shields.io/badge/GitHub-Issues-181717?logo=github)](https://github.com/ceviixx/umami-wp-connect/issues)
[![Discord](https://img.shields.io/badge/Discord-Community-5865F2?logo=discord&logoColor=white)](https://discord.gg/84w4CQU7Jb)

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://php.net/)

![umami Connect](screens/umami-connect-demo.gif)


<table>
  <tr>
   <td><img src="screens/editor-tracking.png" alt="Editor tracking" width="200"></td>
    <td><img src="./screens/setup-general.png" alt="Setup general" width="200"></td>
    <td><img src="./screens/setup-self-protection.png" alt="Setup self protection" width="200"></td>
    <td><img src="screens/setup-automation.png" alt="Setup automation" width="200"></td>
    <td><img src="screens/setup-event-overview.png" alt="Setup event overview" width="200"></td>
  </tr>
</table>



---


## Features

### Core Tracking
- Injects the Umami tracking script into your site's `<head>`
- Easy setup in WordPress Admin:
  - Cloud or Self-hosted mode
  - Host URL (for self-hosted)
  - Website ID (required)
  - Script loading: async or defer

### Auto-Tracking
- Auto‑tracking out of the box:
  - **Links:** adds `data-umami-event` to inline links
  - **Buttons:** adds `data-umami-event` to Gutenberg buttons and native buttons
  - **Forms:** adds `data-umami-event` on submit (uses `form:<id|name>` or `form_submit`)

### Gutenberg Editor Integration
- **Button block:** set custom event + key/value data in the sidebar
- **Inline link tracking:** toolbar action to set event + key/value data on selected links

### Advanced Configuration
- **Host URL override:** Override the endpoint for analytics events (useful for CDN or different collector domains)
- **Auto-track disable:** Disable Umami's automatic tracking for full manual control
- **Domain restrictions:** Limit tracking to specific hostnames (comma-separated list)
- **Event tagging:** Add tags to all events for filtering in reports
- **URL cleanup:** Exclude search parameters or hash fragments from page views
- **Do Not Track:** Respect browser DNT preferences
- **beforeSend hook:** Inspect or modify payloads before sending
  - Function name mode: Reference a global function (with validation)
  - Inline mode: Define custom JavaScript logic (with test button)

### Admin UX
- **Events overview page:** search/sort through configured events
- **Dashboard widget:** quick status and update notifications
- **Update management:** release notes and one-click self-update with version badge
- **Self-protection:** prevent logged-in users from being tracked
- **Context-sensitive help:** per-page help tabs with detailed documentation

### Where tracking works

- Buttons
  - Block: Button — the button link (`<a>`) is supported
- Inline links inside blocks
  - Paragraph, Heading, Excerpt, Quote, Pullquote, List, List Item, Columns, Cover, Group

Note: If a block renders standard `<a>` links, inline link tracking will usually work.

---

## Installation


1. Download the latest release ZIP from the [GitHub Releases page](https://github.com/ceviixx/umami-wp-connect/releases) **or** clone the repository:
   - **Release ZIP:** Go to the [Releases](https://github.com/ceviixx/umami-wp-connect/releases) tab and download the latest `.zip` file
   - **Git:** `git clone https://github.com/ceviixx/umami-wp-connect.git`
2. Install the plugin using one of the following methods:
   - **Via ZIP upload:**
     1. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
     2. Select the downloaded ZIP file from the release and install it
    - **Manually via folder:**
      1. Unzip the release ZIP (or the cloned repo)
      2. Copy the folder to:
        ```bash
        wp-content/plugins/umami-wp-connect
        ```
3. Activate the plugin in **WordPress Admin → Plugins**.

---

## Getting started

In your WordPress Admin go to **Umami Connect** and set:

- Mode:
  - Cloud: uses `https://cloud.umami.is/script.js`
  - Self‑hosted: enter your custom host (without `/script.js`)
- Website ID: required (from your Umami website settings, e.g. `123e4567-e89b-12d3-a456-426614174000`)
- Script loading: `defer` (recommended) or `async`


Tip: Enable pretty permalinks in WordPress for the best page view tracking.

---

## Advanced Configuration

The **Advanced** page provides fine-grained control over the Umami tracker behavior via `data-*` attributes:

### Host URL Override
Override the endpoint where analytics events are sent. Useful when:
- Loading `script.js` from a CDN
- Your Umami collector runs on a different domain
- Example: `https://analytics.example.com`

### Auto-Track Control
Disable Umami's built-in auto-tracking for full manual control via JavaScript API.

### Domain Restrictions
Limit tracking to specific hostnames (comma-separated, no spaces).
- Example: `example.com,blog.example.com`

### Event Tagging
Add a tag to all events for easy filtering in reports.
- Example: `production` or `eu-region`

### URL Cleanup
- **Exclude search:** Ignore URL query parameters in page views
- **Exclude hash:** Ignore URL hash fragments in page views

### Do Not Track
Respect the user's browser DNT preference.

### beforeSend Hook
Inspect or modify event payloads before they're sent to Umami:

**Function Name Mode:**
- Reference a global function (e.g., `MyApp.handlers.beforeSend`)
- Use the "Check function" button to verify it exists on the public site

**Inline Mode:**
- Define the function body directly in the admin
- Use the "Test function" button to validate syntax before saving
- The function receives `(url, data)` and should return the modified payload or a falsy value to cancel

Example:
```javascript
// Only track page views, skip custom events
if (data && data.name) {
  return null; // cancel custom events
}
return { url, data }; // allow page views
```

For more details, see the [Umami Tracker Configuration](https://umami.is/docs/tracker-configuration) documentation.

---

## Usage

### Basic Tracking
- Auto‑tracking works without extra steps if enabled in **Automation** settings:
  - **Links:** automatic `data-umami-event` like `link:Text` and `data-umami-event-url` for the href
  - **Buttons:** automatic `data-umami-event` like `button:Text`
  - **Forms:** automatic `data-umami-event` like `form:<id|name>` on submit

### Custom Events in Gutenberg
- **Button block:** select a button → sidebar "Umami Tracking" → set Event Name and optional key/value pairs
- **Inline link tracking:** select linked text in supported blocks → toolbar "Umami Tracking" → set Event Name and optional key/value pairs

### Advanced Configuration
- Go to **Umami Connect → Advanced** to access fine-grained tracker configuration
- Configure host URL override, domain restrictions, event tagging, URL cleanup, DNT, and beforeSend hooks
- Use the built-in validation tools (Check function, Test function) for beforeSend configuration

### Self-Protection
- Enable "Do not track my own visits" to exclude logged-in WordPress users from analytics
- Useful for keeping your data focused on actual visitors

---

## Development


- Main plugin entry: `umami-connect.php`
- Admin logic/pages and filters: `includes/*`
- Frontend:
  - `assets/umami-autotrack.js` (helper + config; Umami script comes from your host)
  - `assets/editor/umami-extend.js` (Gutenberg integration for buttons and inline links)

Contributions and issues are welcome → GitHub Issues.

---

**Need help or want to request a feature?**

- Open an issue on [GitHub](https://github.com/ceviixx/umami-wp-connect/issues)
- Join our [Discord](https://discord.gg/84w4CQU7Jb) for community chat and quick help
