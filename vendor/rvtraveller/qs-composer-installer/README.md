# Quicksilver Composer Installer #

Creates a new "type" in Composer for `quicksilver-script`s so you can treat them separately in Composer installations.  This allows you to include Quicksilver scripts as part of a composer based project on Pantheon[https://pantheon.io].

To use this custom installer, require it in your project (root-level) composer.json file. Then, any Composer project of type `quicksiver-script` will be placed in the directory `web/private/scripts/quicksilver`. This path may be customized in the `installer-paths` item in `extras`.

The `web/private/scripts/quicksilver` path (or your customized path) should be added to your project's .gitignore.

## Example composer.json file ##

```
{
  "require": {
    "rvtraveller/qs-composer-installer": "1.0"
  },
  "extra": {
    "installer-paths": {
      "web/private/scripts/quicksilver/{$name}": ["type:quicksilver-script"]
    }
  }
}
```
