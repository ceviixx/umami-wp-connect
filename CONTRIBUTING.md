
# Contributing to umami Connect

Thanks for your interest in improving the plugin! This guide focuses on **adding a new integration** (e.g. for a form plugin, e‑commerce, or block library). If you just want to report an issue or request a feature, head to:

- Issues: https://github.com/ceviixx/umami-wp-connect/issues
- Discussions: https://github.com/ceviixx/umami-wp-connect/discussions

---
## 1. Fork & Clone (dev branch)

```bash
git clone https://github.com/YOUR-USERNAME/umami-wp-connect.git
cd umami-wp-connect
git remote add upstream https://github.com/ceviixx/umami-wp-connect.git
git fetch upstream
git checkout -b feature/my-integration upstream/dev
```

Always branch off `dev` (not `main`). Keep your fork synced:

```bash
git fetch upstream
git merge upstream/dev
```

---
## 2. How Integrations Are Loaded

Integrations are registered in `integrations/registry.php` via `umami_connect_get_integrations()`. Each entry uses the **integration key** (folder name) and defines:

```php
$key => array(
  'label'       => 'Human Name',
  'description' => __( 'Short explainer shown in UI.', 'umami-connect' ),
  'color'       => '#hexcolor',   // Used in admin lists
  'check'       => function () {  // Return true if plugin is active
    return function_exists( 'some_plugin_function' );
  },
  'files'       => array(         // Files loaded if check() is true
    'hooks.php',
    'admin-settings.php',
    'analytics-data.php',
  ),
)
```

The autoloader (`includes/core/autoloader.php`) calls the `check` closure; if it returns `true`, it requires each file listed under `files` inside `integrations/<key>/`.

---
## 3. Required Files (Typical Pattern)

Create a new directory: `integrations/<your-key>/` (use lowercase, dashes instead of spaces). Add these files as needed:

| File | Purpose |
|------|---------|
| `analytics-data.php` | Pushes integration-specific events & candidates into the Events Overview (via `add_filter('umami_connect_get_all_events', ...)`). |
| `hooks.php` | Runtime tracking hooks (inject attributes, listen to actions, send JS events). Keep defensive checks. |
| `admin-settings.php` | (Optional) Adds settings UI for custom event names/data under the integration’s native admin/editor. |

Some integrations might also need `event-provider.php` or additional helpers, but keep it minimal.

### Example Starter

Instead of inline examples, see the template in `integrations/_example/` which includes:

- `README.md` (how to adapt)
- `analytics-data.php` (event provider pattern)
- `hooks.php` (attribute injection pattern)
- `admin-settings.php` (optional settings scaffold)

Copy that folder, rename it to your integration key, then adjust code and add a registry entry.

Keep functions `prefixed` (`umami_<integration>_...`) and avoid class names unless needed. Procedural is fine.

---
## 4. Event Data Structure

Each event array must include at least:

- `event` — Actual event name or `(Candidate)` placeholder
- `post_id` — Related content/form ID (or 0 if not applicable)
- `post_title` — Human label for display
- `block_type` — Short source tag (e.g. `CF7`, `WPForms`, `WooCommerce`)
- `label` — Same or different from title
- `data_pairs` — Array of pairs: `[ ['key'=>'foo','value'=>'bar'], ... ]`
- `event_type` — Unique type when tracked (e.g. `integration_cf7`); `none` for candidates
- `is_tracked` — Boolean
- `integration` — Integration key (folder name)
- `integration_label` / `integration_color` — For UI coloring/filtering
- `edit_link` / `edit_label` — Direct admin URL to edit tracking settings

Search & filtering rely on `event`, `post_title`, `label`, `integration_label`, and `integration`.

---
## 5. Defensive Checks

Always guard your files so they only run if the dependent plugin is active:

```php
if ( ! function_exists( 'myform' ) ) { return; }
if ( ! defined( 'ABSPATH' ) ) { exit; }
```

Never fatal-error if something is missing—just return early.

---
## 7. Testing Your Integration

1. Activate the dependent plugin locally.
2. Configure your integration’s event name(s) in its admin/editor UI (if provided).
3. Visit the frontend and trigger the action (submit form, click button, add to cart, etc.).
4. Open the Events Overview in umami Connect settings; confirm events appear and filter by your integration.
5. Check the browser devtools for `umami.track()` calls or server-side hook execution.

Optional: add a quick screenshot/GIF to the PR showing the event captured.

---
## 8. Submitting the Pull Request

```bash
git add .
git commit -m "feat(integration): add MyForm integration"
git push origin feature/my-integration
```

Open a PR against `dev` with:
- Motivation & scope
- List of added files
- Any limitations or TODOs
- Screenshots (admin + event capture)

Label it with `enhancement` (or let triage adjust).

---
## 9. Common Pitfalls & Tips

| Issue | Solution |
|-------|----------|
| Events not showing | Confirm `integration` key matches folder name and registry key. |
| Attributes not injected | Ensure hook runs on frontend (avoid `is_admin()`) and regex matches output. |
| Wrong color/dot | Provide `integration_color` consistently in events. |
| Duplicate loading | Keep file list minimal; avoid requiring same file twice. |
| JSON meta invalid | Validate with `json_decode()` and fallback when parsing custom data. |

---
## 10. Style & Conventions

- PHP: WordPress standards (tabs). Keep procedural unless complexity warrants classes.
- Function names: `umami_<integration>_<action>`.
- Avoid heavy dependencies—stay lean.
- Keep performance: bail early, avoid scanning huge content unnecessarily.

---
## 11. Maintainer Review Checklist (You Can Self-Check)

- Registry entry added correctly
- Guard clauses present in each integration file
- No fatal errors if dependency missing
- All text translatable, escaped
- Event arrays contain required keys
- Works in Events Overview filter/search
- PR targets `dev`



Thanks for contributing integrations—each one makes analytics easier for more users 🎉