# SiteFarm on Pantheon

This repository can be used to set up a Composer-Managed Drupal 8 site on [Pantheon](https://pantheon.io).

[![CircleCI](https://circleci.com/gh/pantheon-systems/example-drops-8-composer.svg?style=shield)](https://circleci.com/gh/pantheon-systems/example-drops-8-composer)
[![Pantheon example-drops-8-composer](https://img.shields.io/badge/dashboard-drops_8-yellow.svg)](https://dashboard.pantheon.io/sites/c401fd14-f745-4e51-9af2-f30b45146a0c#dev/code) 
[![Dev Site example-drops-8-composer](https://img.shields.io/badge/site-drops_8-blue.svg)](http://dev-example-drops-8-composer.pantheonsite.io/)

## Overview

This project contains only the canonical resources used to build a SiteFarm Drupal site for use on Pantheon. 

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

### Install SiteFarm
```
$ terminus build-env:create-project --stability=dev ucdavis/sitefarm-pantheon my-sitefarm-site
```
Replace "my-sitefarm-site" would your desired site machine name in Pantheon.