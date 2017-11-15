# Example Drops 8 Composer

[![CircleCI](https://circleci.com/gh/pantheon-systems/example-drops-8-composer.svg?style=shield)](https://circleci.com/gh/pantheon-systems/example-drops-8-composer)
[![Pantheon example-drops-8-composer](https://img.shields.io/badge/dashboard-drops_8-yellow.svg)](https://dashboard.pantheon.io/sites/c401fd14-f745-4e51-9af2-f30b45146a0c#dev/code) 
[![Dev Site example-drops-8-composer](https://img.shields.io/badge/site-drops_8-blue.svg)](http://dev-example-drops-8-composer.pantheonsite.io/)

This repository is a start state for a Composer-based Drupal workflow with Pantheon. It is meant to be copied by the the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin) which will set up for you a brand new

* GitHub repo
* Free Pantheon sandbox site
* A CircleCI configuration to run tests and push from the source repo (GitHub) to Pantheon.

For more background information on this style of workflow, see the [Pantheon documentation](https://pantheon.io/docs/guides/github-pull-requests/).


## Installation

### Prerequisites

Before running the `terminus build:project:create` command, make sure you have all of the prerequisites:

* [A Pantheon account](https://dashboard.pantheon.io/register)
* [Terminus, the Pantheon command line tool](https://pantheon.io/docs/terminus/install/)
* [The Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin)
* An account with GitHub and an authentication token capable of creating new repos.
* An account with CircleCI and an authentication token.

You may find it easier to export the GitHub and CircleCI tokens as variables on your command line where the Build Tools Plugin can detect them automatically:

```
export GITHUB_TOKEN=[REDACTED]
export CIRCLE_TOKEN=[REDACTED]
```

### One command setup:

Once you have all of the prerequisites in place, you can create your copy of this repo with one command:

```
terminus build:project:create pantheon-systems/example-drops-8-composer my-new-site --team="Agency Org Name"
```

The parameters shown here are:

* The name of the source repo, `pantheon-systems/example-drops-8-composer`. If you are interest in other source repos like WordPress, see the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin).
* The machine name to be used by both the soon-to-be-created Pantheon site and GitHub repo. Change `my-new-site` to something meaningful for you.
* The `--team` flag is optional and refers to a Pantheon organization. Pantheon organizations are often web development agencies or Universities. Setting this parameter causes the newly created site to go within the given organization. Run the Terminus command `terminus org:list` to see the organizations you are a member of. There might not be any.


## Important files and directories

### `/web`

Pantheon will serve the site from the `/web` subdirectory due to the configuration in `pantheon.yml`, facilitating a Composer based workflow. Having your website in this subdirectory also allows for tests, scripts, and other files related to your project to be stored in your repo without polluting your web document root.

#### `/config`

One of the directories moved to the git root is `/config`. This directory holds Drupal's `.yml` configuration files. In more traditional repo structure these files would live at `/sites/default/config/`. Thanks to [this line in `settings.php`](https://github.com/pantheon-systems/example-drops-8-composer/blob/54c84275cafa66c86992e5232b5e1019954e98f3/web/sites/default/settings.php#L19), the config is moved entirely outside of the web root.

### `composer.json`

If you are just browsing this repository on GitHub, you may notice that the files of Drupal core itself are not included in this repo.  That is because Drupal core and contrib modules are installed via Composer and ignored in the `.gitignore` file. Specific contrib modules are added to the project via `composer.json` and `composer.lock` keeps track of the exact version of each modules (or other dependency). Modules, and themes are placed in the correct directories thanks to the `"installer-paths"` section of `composer.json`. `composer.json` also includes instructions for `drupal-scaffold` which takes care of placing some individual files in the correct places like `settings.pantheon.php`.

## Behat tests

So that CircleCI will have some test to run, this repository includes a configuration of Behat tests. You can add your own `.feature` files within `/tests/features/`.

## Updating your site

When using this repository to manage your Drupal site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Ensure your site is in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.








