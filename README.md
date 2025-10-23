# Umami Connect

**Umami Connect** is a simple WordPress plugin to integrate [Umami Analytics](https://umami.is) into your WordPress site. Supports both **Umami Cloud** and **Self-hosted** installations.


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

- Automatically injects the Umami tracking script into your site’s `<head>`
- Admin settings panel:
  - Cloud or Self-hosted mode
  - Host URL (for self-hosted)
  - Website ID (required)
  - Option to exclude logged-in users
  - Script loading: async or defer
- Auto-tracking:
  - **Links**: sends `click:/path` events for all `<a>` clicks
  - **Buttons**: tracks `button:Label` (uses text or aria-label)
  - **Forms**: tracks `form:id|name` or `form_submit` on submit
- Per-element overrides:
  - `data-umami-event="CustomName"`
  - `data-umami-data*=''`
- Gutenberg integration
- Optional helper script for rel-based custom link events

### Supported Blocks for Inline Event Tracking

| Block Type | Supported Elements     |
|------------|-----------------------|
| Button     | Button `<a>`           |
| Paragraph  | Inline links `<a>`     |
| Excerpt    | Inline links `<a>`     |
| Heading    | Inline links `<a>`     |

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
        wp-content/plugins/umami-connect
        ```
3. Activate the plugin in **WordPress Admin → Plugins**.

---

## Getting started

Go to **Umami Connect**:

- **Mode:**
  - **Cloud**: uses `https://cloud.umami.is/script.js`
  - **Self-hosted**: enter your custom host (without `/script.js`)
- **Website ID:** required, found in your Umami website settings (e.g. `123e4567-e89b-12d3-a456-426614174000`)
- **Script loading:** choose between async and defer

---

## Development


- Main plugin entry: `umami-connect.php` (loads all core logic)
- Most logic and admin pages: `includes/` (settings, menu, dashboard, filters, pages)
- Frontend helpers:
  - `assets/umami-autotrack.js` (auto-tracking for links, buttons, forms)
  - `assets/editor/umami-extend.js` (Gutenberg block integration)

---

**Need help or want to request a feature?**
Open an issue on [GitHub](https://github.com/ceviixx/umami-wp-connect/issues)
