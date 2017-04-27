# Example Drops 8 Composer

This repository can be used to set up a Composer-Managed Drupal 8 site on [Pantheon](https://pantheon.io).

[![CircleCI](https://circleci.com/gh/pantheon-systems/example-drops-8-composer.svg?style=shield)](https://circleci.com/gh/pantheon-systems/example-drops-8-composer)
[![Pantheon example-drops-8-composer](https://img.shields.io/badge/dashboard-drops_8-yellow.svg)](https://dashboard.pantheon.io/sites/c401fd14-f745-4e51-9af2-f30b45146a0c#dev/code) 
[![Dev Site example-drops-8-composer](https://img.shields.io/badge/site-drops_8-blue.svg)](http://dev-example-drops-8-composer.pantheonsite.io/)

## Overview

This project contains only the canonical resources used to build a Drupal site for use on Pantheon. There are two different ways that it can be used:

- Create a separate canonical repository on GitHub; maintain using a pull request workflow. **RECOMMENDED**
- Build the full Drupal site and then install it on Pantheon; maintain using `terminus composer` and on-server development.

The setup instructions vary based on which of these options you select.

## Pull Request Workflow

When using a pull request workflow, only the canonical resources (code, configuration, etc.) exists in the master repository, stored on GitHub. A build step is used to create the full Drupal site and automatically deploy it to Pantheon. This is the recommended way to use this project.

### Setup

For setup instructions, please see [Using GitHub Pull Requests with Composer and Drupal 8](https://pantheon.io/docs/guides/github-pull-requests/).

### Environment Variables

The [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin) automatically configures Circle CI to build your site. The following environment variables are defined:

- TERMINUS_TOKEN: The Terminus Machine token previously created.
- GITHUB_TOKEN: Used by CircleCI to post comments on pull requests.
- TERMINUS_SITE: The name of the Pantheon site that will be used to test your site.
- TEST_SITE_NAME: Used to set the name of the test  site when installing Drupal.
- ADMIN_EMAIL: Used to configure the email address to use when installing Drupal.
- ADMIN_PASSWORD: Used to set the password for the uid 1 user during site installation.
- GIT_EMAIL: Used to configure the git user’s email address for commits we make.

If you need to modify any of these values, you may do so in the [Circle CI Environment Variable](https://circleci.com/docs/1.0/environment-variables/) configuration page.

### SSH Keys

A [public/private key pair](https://pantheon.io/docs/ssh-keys/) is created and added to Circle CI (the private key) and the Pantheon site (the public key). If you need to update your public key, you may do so with Terminus:
```
$ terminus ssh-key:add ~/.ssh/id_rsa.pub
```

## Pantheon "Standalone" Development

This project can also be used to do traditional "standalone" development on Pantheon using on-server development. In this mode, the canonical repository is immediately built out into a full Drupal site, and the results are committed to the Pantheon repository. Thereafter, no canoncial repository is used; all development will be done exclusively using the Pantheon database.

When doing "standalone" development, this project can either be used as an upstream repository, or it can be set up manually. The instructions for doing either follows in the section below.

### As an Upstream

Create a custom upstream for this project following the instructions in the [Pantheon Custom Upstream documentation](https://pantheon.io/docs/custom-upstream/). When you do this, Pantheon will automatically run composer install to populate the web and vendor directories each time you create a site.

### Manual Setup

Enter the commands below to create a a new site on Pantheon and push a copy of this project up to it.
```
$ SITE="my-site"
$ terminus site:create $SITE "My Site" "Drupal 8" --org="My Team"
$ composer create-project pantheon-systems/example-drops-8-composer $SITE
$ cd $SITE
$ composer prepare-for-pantheon
$ git init
$ git add -A .
$ git commit -m "Initial commit"
$ terminus  connection:set $SITE.dev git
$ PANTHEON_REPO=$(terminus connection:info $SITE.dev --field=git_url)
$ git remote add origin $PANTHEON_REPO
$ git push --force origin master
$ terminus drush $SITE.dev -- site-install --site-name="My Drupal Site"
$ terminus dashboard:view $SITE
```
Replace my-site with the name that you gave your Pantheon site. Customize the parameters of the `site:create` and `site-install` lines to suit.

### Installing Drupal

Note that this example repository sets the installation profile to 'standard' in settings.php, so that the installer will not need to modify the settings file. If you would like to install a different profile, modify settings.php appropriately before installing your site.

### Updating Your Site

When using this repository to manage your Drupal site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Updates can be applied either directly on Pantheon, by using Terminus, or on your local machine.

#### Update with Terminus

Install [Terminus 1](https://pantheon.io/docs/terminus/) and the [Terminus Composer plugin](https://github.com/pantheon-systems/terminus-composer-plugin).  Then, to update your site, ensure it is in SFTP mode, and then run:
```
terminus composer <sitename>.<dev> update
```
Other commands will work as well; for example, you may install new modules using `terminus composer <sitename>.<dev> require drupal/pathauto`.

#### Update on your local machine

You may also place your site in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.
