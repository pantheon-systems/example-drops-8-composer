# Quicksilver Pushback
This Quicksilver project is used in conjunction with the various suite of [Terminus Build Tools](https://github.com/pantheon-systems/terminus-build-tools-plugin)-based example repositories to push any commits made on the Pantheon dashboard back to the original GitHub repository for the site.

This Quicksilver script only works with Pantheon sites that have been configured to use a GitHub PR workflow.

### Example composer.json

This project is designed to be included from a site's composer.json file, and placed in its appropriate installation directory by [Composer Installers](https://github.com/composer/installers).

In order for this to work, you should have the following in your composer.json file:

```json
{
  "require": {
    "composer/installers": "^1.0.20"
  },
  "extra": {
    "installer-paths": {
      "web/private/scripts/quicksilver": ["type:quicksilver-script"]
    }
  }
}
```

If you are using one of the example PR workflow projects as a starting point for your site, these entries should already be present in your composer.json.

### Example `pantheon.yml`

Here's an example of what your `pantheon.yml` would look like if this were the only Quicksilver operation you wanted to use.

```yaml
api_version: 1

workflows:
  sync_code:
    after:
      - type: webphp
        description: Push changes back to GitHub if needed
        script: private/scripts/quicksilver/quicksilver-pushback/push-back-to-github.php
```
If you are using one of the example PR workflow projects as a starting point for your site, this entry should already be present in your pantheon.yml.
