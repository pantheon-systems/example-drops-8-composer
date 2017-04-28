<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

  - [Drupal Console](#drupal-console)
  - [Required PHP version](#required-php-version)
  - [Drupal Console documentation](#documentation)
  - [Download Drupal Console](#download)
  - [Run Drupal Console](#run)
  - [Contributors](#contributors)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console)
[![Software License](https://img.shields.io/badge/license-GPL%202.0+-blue.svg)](https://packagist.org/packages/drupal/console)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal CLI. A tool to generate boilerplate code, interact with and debug Drupal.

## Latest Version
Details of the latest version can be found on the Drupal Console project page under https://drupalconsole.com/.

## Releases Page
All notable changes to this project will be documented in the [releases page](https://github.com/hechoendrupal/DrupalConsole/releases)

## Documentation
The most up-to-date documentation can be found at [http://docs.drupalconsole.com/](http://docs.drupalconsole.com/).

More information about using this project at the [official documentation](http://docs.drupalconsole.com/en/using/project.html).

## Required PHP Version
PHP 5.5.9 or higher is required to use the Drupal Console application.

## Download 

[Install Drupal Console Using Composer](https://docs.drupalconsole.com/en/getting/composer.html)

[Install Drupal Console Launcher](https://docs.drupalconsole.com/en/getting/launcher.html)

[Installing Drupal Console on Windows](https://docs.drupalconsole.com/en/getting/windows.html)

## Run
Using the DrupalConsole Launcher
```
drupal
``` 

We highly recommend you to install the global executable, but if is not installed, then you can run DrupalConsole by:  

```
vendor/bin/drupal
# or
vendor/drupal/console/bin/drupal
# or
bin/drupal
```

## Drupal Console Support
You can ask for support at Drupal Console gitter chat room [http://bit.ly/console-support](http://bit.ly/console-support).

## Getting The Project To Contribute

For information about how to run this project for development follow instructions at [setup instructions](https://gist.github.com/jmolivas/97bbd07f328217be3564a434c5bd2618).

## Enabling Autocomplete
```
# You can enable autocomplete by executing
drupal init

# Bash: Bash support depends on the http://bash-completion.alioth.debian.org/
# project which can be installed with your package manager of choice. Then add
# this line to your shell configuration file.
source "$HOME/.console/console.rc" 2>/dev/null

# Zsh: Add this line to your shell configuration file.
source "$HOME/.console/console.rc" 2>/dev/null

# Fish: Create a symbolic link
ln -s ~/.console/drupal.fish ~/.config/fish/completions/drupal.fish
```

## Contributors

[Full list of contributors](https://drupalconsole.com/contributors)

## Supporting Organizations

[![weKnow](https://www.drupal.org/files/weKnow-logo_5.png)](http://weknowinc.com)

[![Anexus](https://www.drupal.org/files/anexus-logo.png)](http://www.anexusit.com/)

[All supporting organizations](https://drupalconsole.com/supporting-organizations)

> Drupal is a registered trademark of Dries Buytaert.
