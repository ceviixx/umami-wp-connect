# umami Connect – Dev Tools & Local Testing

This folder contains developer tools, debug routes, and local testing instructions for contributors.

## Debug Route: Force Auto-Update

To manually trigger WordPress auto-update jobs for testing, use the following route in your local environment:

```
http://YOUR-HOST/?force_auto_update=1
```

This will execute `wp_maybe_auto_update()` and print `Auto-update executed.` in the browser. See `debug-routes.php` for implementation details.

---

## Assets & Media

### GIF image for landing page
```bash
convert -dither None -colors 256 \
  \( screens/gutenberg/gutenberg-link-and-button.png -set delay 500 \) \
  \( screens/settings/settings-general.png -set delay 200 \) \
  \( screens/settings/settings-automation.png -set delay 200 \) \
  \( screens/settings/settings-event-overview.png -set delay 200 \) \
  -loop 0 \
  screens/umami-connect-demo.gif
```

---

## Local Testing & Automation

Below are useful commands and workflows for local development and CI testing:

Install using `brew install act`

#### Running `js-lint`
```bash
act -j js-lint --container-architecture linux/amd64
```

#### Running `php-lint`
```bash
act -j php-lint --container-architecture linux/amd64
```

#### Running `dependency-check`
```bash
act -j dependency-check --container-architecture linux/amd64
```

#### Running `wp-smoke-test`
```bash
act -j wp-smoke-test --container-architecture linux/amd64
```

#### Running `structure-check`
```bash
act -j structure-check --container-architecture linux/amd64
```
