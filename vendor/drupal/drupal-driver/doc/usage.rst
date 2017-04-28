Usage
=====

Drupal API driver
-----------------

.. literalinclude:: _static/snippets/usage-drupal.php
   :language: php
   :linenos:
   :emphasize-lines: 14-15

Drush driver
------------

.. literalinclude:: _static/snippets/usage-drush.php
   :language: php
   :linenos:
   :emphasize-lines: 7-8

Blackbox
--------

Note, the blackbox driver has no ability to control Drupal, and is provided as a fallback for when some tests can run without such access.

Any testing application should catch unsupported driver exceptions.

.. literalinclude:: _static/snippets/usage-blackbox.php
   :language: php
   :linenos:
   :emphasize-lines: 8,19

Practical example with PHPUnit
------------------------------

By using the phpunit/mink project in conjunction with the Drupal Driver, one can use PHPUnit to drive browser sessions and control Drupal.

To install:

.. literalinclude:: _static/snippets/phpunit-composer.json
   :language: json
   :linenos:

and then, in the tests directory, a sample test:

.. literalinclude:: _static/snippets/phpunitDrupalDriver.php
   :language: php
   :linenos:
