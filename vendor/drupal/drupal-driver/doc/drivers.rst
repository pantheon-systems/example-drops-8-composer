Comparison of Drivers
=====================

The available drivers for interacting with your site, which are
compatible with Drupal 7, and 8. Each driver has its own limitiations.

+-----------------------+----------+-------+------------+
| Feature               | Blackbox | Drush | Drupal API |
+=======================+==========+=======+============+
| Create users          | No       | Yes   | Yes        |
+-----------------------+----------+-------+------------+
| Create nodes          | No       | [*]   | Yes        |
+-----------------------+----------+-------+------------+
| Create vocabularies   | No       | No    | Yes        |
+-----------------------+----------+-------+------------+
| Create taxonomy terms | No       | [*]   | Yes        |
+-----------------------+----------+-------+------------+
| Run tests and site    |          |       |            |
| on different servers  | Yes      | Yes   | No         |
+-----------------------+----------+-------+------------+

[*] Possible if behat.d7.drush.inc or behat.d8.drush.inc,
    as appropriate, is installed in the target Drupal site.
