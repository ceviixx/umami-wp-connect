# Integration Template (_example)

Use this folder as a starting point for a new integration. Copy it to `integrations/<your-key>/` (lowercase, dashes), then wire it up in `integrations/registry.php`.

What to change:
- Folder name: `_example` ➜ your integration key (e.g. `myform`)
- File content: Replace placeholder names, keys, and comments
- Registry: Add your key, label, color, `check` callback, and `files` list

Files in this template:
- `analytics-data.php` — Provides events to the Events Overview
- `hooks.php` — Runtime hooks (inject attributes, listen to actions)
- `admin-settings.php` — Optional settings UI for event name/data

Make sure your code only runs when the dependency is active and never fatals on missing APIs.
