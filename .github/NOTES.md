# GIF image for landing page
```bash
convert -delay 200 -loop 0 screens/editor-tracking.png screens/setup-general.png screens/setup-self-protection.png screens/setup-automation.png sreens/setup-event-overview.png screens/umami-connect-demo.gif
```

# Local workflow tets
```bash
for job in js-lint php-lint dependency-check psalm-security wp-smoke-test; do echo "\n===== Testing $job ====="; act -j "$job" 2>&1 | tail -15; echo "Status: $?"; done
```


# Local package size test
```bash
act -j check-package-size 2>&1 | grep -E "📦|📊|📋|🔍|✓|MB|files"
```