# GIF image for landing page
```bash
convert -dither None -colors 256 \
  \( screens/gutenberg/gutenberg-link-and-button.png -set delay 500 \) \
  \( screens/settings/settings-general.png -set delay 200 \) \
  \( screens/settings/settings-automation.png -set delay 200 \) \
  \( screens/settings/settings-event-overview.png -set delay 200 \) \
  -loop 0 \
  screens/umami-connect-demo.gif
```


# Local package size test
```bash
act -j check-package-size 2>&1 | grep -E "📦|📊|📋|🔍|✓|MB|files"
```



# Test local using act
Install using `brew install act`

### Running `js-lint`
```bash
act -j js-lint --container-architecture linux/amd64
```

### Running `php-lint`
```bash
act -j php-lint --container-architecture linux/amd64
``` 

### Running `dependency-check`
```bash
act -j dependency-check --container-architecture linux/amd64
```

### Running `psalm-security`
```bash
act -j psalm-security --container-architecture linux/amd64
```

### Running `wp-smoke-test`
```bash
act -j wp-smoke-test --container-architecture linux/amd64
```

### Running `structure-check`
```bash
act -j structure-check --container-architecture linux/amd64
```