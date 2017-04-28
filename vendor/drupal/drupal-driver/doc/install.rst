Installation
============

To utilize the Drupal Drivers in your own project, they are installed via composer_.

.. literalinclude:: _static/snippets/composer.json
   :language: json

and then install and run composer

.. literalinclude:: _static/snippets/composer.bash
   :language: bash

.. _composer: https://getcomposer.org/

If you plan on using the Drush driver, then you need to ensure
that the behat-drush-endpoint is available in the target Drupal
site.  There are two ways to do this:

1. Copy the files manually.  The project can be found at:

https://github.com/drush-ops/behat-drush-endpoint

2. Use Composer.

If you are using Composer to manage your Drupal site, then
you only need to require drupal/drupal-driver and
composer/installers, and the behat-drush-endpoint files
will be copied to the correct location.
