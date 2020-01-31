# Example Drops 8 Composer

[![CircleCI](https://circleci.com/gh/pantheon-systems/example-drops-8-composer.svg?style=shield)](https://circleci.com/gh/pantheon-systems/example-drops-8-composer)
[![Pantheon example-drops-8-composer](https://img.shields.io/badge/dashboard-drops_8-yellow.svg)](https://dashboard.pantheon.io/sites/c401fd14-f745-4e51-9af2-f30b45146a0c#dev/code) 
[![Dev Site example-drops-8-composer](https://img.shields.io/badge/site-drops_8-blue.svg)](http://dev-example-drops-8-composer.pantheonsite.io/)

This repository is a reference implementation and start state for a modern Drupal 8 workflow utilizing [Composer](https://getcomposer.org/), Continuous Integration (CI), Automated Testing, and Pantheon. Even though this is a good starting point, you will need to customize and maintain the CI/testing set up for your projects.

This repository is meant to be copied one-time by the the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin) but can also be used as a template. It should not be cloned or forked directly.

The Terminus Build Tools plugin will scaffold a new project, including:

* A Git repository
* A free Pantheon sandbox site
* Continuous Integration configuration/credential set up

For more details and instructions on creating a new project, see the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin/).

## Important files and directories

### `/web`

Pantheon will serve the site from the `/web` subdirectory due to the configuration in `pantheon.yml`. This is necessary for a Composer based workflow. Having your website in this subdirectory also allows for tests, scripts, and other files related to your project to be stored in your repo without polluting your web document root or being web accessible from Pantheon. They may still be accessible from your version control project if it is public. See [the `pantheon.yml`](https://pantheon.io/docs/pantheon-yml/#nested-docroot) documentation for details.

#### `/config`

One of the directories moved to the git root is `/config`. This directory holds Drupal's `.yml` configuration files. In more traditional repo structure these files would live at `/sites/default/config/`. Thanks to [this line in `settings.php`](https://github.com/pantheon-systems/example-drops-8-composer/blob/54c84275cafa66c86992e5232b5e1019954e98f3/web/sites/default/settings.php#L19), the config is moved entirely outside of the web root.

### `composer.json`
This project uses Composer to manage third-party PHP dependencies.

The `require` section of `composer.json` should be used for any dependencies your web project needs, even those that might only be used on non-Live environments. All dependencies in `require` will be pushed to Pantheon. 

The `require-dev` section should be used for dependencies that are not a part of the web application but are necesarry to build or test the project. Some example are `php_codesniffer` and `phpunit`. Dev dependencies will not be deployed to Pantheon.

If you are just browsing this repository on GitHub, you may not see some of the directories mentioned above. That is because Drupal core and contrib modules are installed via Composer and ignored in the `.gitignore` file.

This project uses the following required dependencies:

- **composer/installers**: Relocates the installation location of certain Composer projects by type; for example, this component allows Drupal modules to be installed to the `modules` directory rather than `vendor`.

- **drupal/core-composer-scaffold**: Allows certain necessary files, e.g. index.php, to be copied into the required location at installation time.

- **drupal/core-recommended**: This package contains Drupal itself, including the Drupal scaffold files.

- **pantheon-systems/drupal-integrations**: This package provides additional scaffold files required to install this site on the Pantheon platform. These files do nothing if the site is deployed elsewhere.

The following optional dependencies are also included as suggestions:

- **pantheon-systems/quicksilver-pushback**: This component allows commits from the Pantheon Dashboard to be automatically pushed back to GitHub for sites using the Build Tools Workflow. This package does nothing if that workflow has not been set up for this site.

- **drush/drush**: Drush is a commandline tool that provides ways to interact with site maintenance from the command line.

- **drupal/console**: Drupal Console is similar to and an alternative for Drush. You may use either or both.

- **cweagans/composer-patches**: Allows a site to be altered with patch files at installation time.

- **drupal/config_direct_save**: Provides a way to export configuration directly to the filesystem (in SFTP mode) directly from the Drupal admin interface. This is a convenient way to manage configuration files.

- **drupal/config_installer**: Allows a site to be re-installed through the Drupal web installer using existing exported configuration files.

- **drush-ops/behat-drush-endpoint**: Used by Behat tests.

- **rvtraveller/qs-composer-installer**: Allows a site to install quicksilver hooks from a Composer package.

- **zaporylie/composer-drupal-optimizations**: This package makes `composer update` operations run more quickly when updating Drupal and Drupal's dependencies.

Any of the optional dependencies may be removed if they are not needed or desired.

### `.ci`
This `.ci` directory is where all of the scripts that run on Continuous Integration are stored. Provider specific configuration files, such as `.circle/config.yml` and `.gitlab-ci.yml`, make use of these scripts.

The scripts are organized into subdirectories of `.ci` according to their function: `build`, `deploy`, or `test`.

#### Build Scripts `.ci/build`
Steps for building an artifact suitable for deployment. Feel free to add other build scripts here, such as installing Node dependencies, depending on your needs.

- `.ci/build/php` installs PHP dependencies with Composer

#### Build Scripts `.ci/deploy`
Scripts for facilitating code deployment to Pantheon.

- `.ci/deploy/pantheon/create-multidev` creates a new [Pantheon multidev environment](https://pantheon.io/docs/multidev/) for branches other than the default Git branch
  - Note that not all users have multidev access. Please consult [the multidev FAQ doc](https://pantheon.io/docs/multidev-faq/) for details.
- `.ci/deploy/pantheon/dev-multidev` deploys the built artifact to either the Pantheon `dev` or a multidev environment, depending on the Git branch

#### Automated Test Scripts `.ci/tests`
Scripts that run automated tests. Feel free to add or remove scripts here depending on your testing needs.

**Static Testing** `.ci/test/static` and `tests/unit`
Static tests analyze code without executing it. It is good at detecting syntax error but not functionality.

- `.ci/test/static/run` Runs [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with [Drupal coding standards](https://www.drupal.org/project/coder), PHP Unit, and [PHP syntax checking](https://www.php.net/manual/en/function.php-check-syntax.php).
- `tests/unit/bootstrap.php` Bootstraps the Composer autoloader
- `tests/unit/TestAssert.php` An example Unit test. Project specific test files will need to be created in `tests/unit`.

**Visual Regression Testing** `.ci/test/visual-regression`
Visual regression testing uses a headless browser to take screenshots of web pages and compare them for visual differences.

- `.ci/test/visual-regression/run` Runs [BackstopJS](https://github.com/garris/BackstopJS) visual regression testing.
- `.ci/test/visual-regression/backstopConfig.js` The [BackstopJS](https://github.com/garris/BackstopJS) configuration file. Setting here will need to be updated for your project. For example, the `pathsToTest` variable determines the URLs to test.

**Behat Testing** `.ci/test/behat` and `tests/behat`
[Behat](http://behat.org/en/latest/) is an acceptance/end-to-end testing framework written in PHP. It faciliates testing the fully built Drupal site on Pantheon infrastucture. [The Drupal Behat Extension](https://www.drupal.org/project/drupalextension) is used to help with integrating Behat and Drupal.

- `.ci/test/behat/initialize` creates a backup of the environment to be tested
- `.ci/test/behat/run` sets the `BEHAT_PARAMS` environment variable with dynamic information necessary for Behat and configure it to use Drush via [Terminus](https://pantheon.io/docs/terminus/) and starts headless Chrome, and runs Behat
- `.ci/test/behat/cleanup` restores the previously made database backup and saves screenshots taken by Behat
- `tests/behat/behat-pantheon.yml` Behat configuration file compatible with running tests against a Pantheon site
- `tests/behat/tests/behat/features` Where Behat test files, with the `.feature` extension, should be stored. The provided example tests will need to be replaced with project specific tests.
  - `tests/behat/tests/behat/features/content.feature` A Behat test file which logs into the Drupal dashboard, creates nodes, users and terms, and verifies their existience in the Drupal admin interface and the front end of the site


## Updating your site

When using this repository to manage your Drupal site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Ensure your site is in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.
