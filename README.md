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

- Injects the Umami tracking script into your site’s `<head>`
- Easy setup in WordPress Admin:
  - Cloud or Self-hosted mode
  - Host URL (for self-hosted)
  - Website ID (required)
  - Script loading: async or defer
- Auto‑tracking out of the box:
  - Links: adds `data-umami-event` to inline links
  - Buttons: adds `data-umami-event` to Gutenberg buttons and native buttons
  - Forms: adds `data-umami-event` on submit (uses `form:<id|name>` or `form_submit`)
- Gutenberg editor integration:
  - Button block: set custom event + key/value data in the sidebar
  - Inline link tracking: toolbar action to set event + key/value data on selected links
- Admin UX:
  - Events overview page (search/sort through configured events)
  - Dashboard widget with quick status
  - Support & Updates page with release notes and self‑update helper

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

## Usage

- Auto‑tracking works without extra steps if enabled in Automation:
  - Links: automatic `data-umami-event` like `link:Text` and `data-umami-event-url` for the href
  - Buttons: automatic `data-umami-event` like `button:Text`
  - Forms: automatic `data-umami-event` like `form:<id|name>` on submit
- Gutenberg Button block: select a button → sidebar “Umami Tracking” → set Event Name and optional key/value pairs
- Inline link tracking: select linked text in supported blocks → toolbar “Umami Tracking” → set Event Name and optional key/value pairs

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
