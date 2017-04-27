<?php

namespace Drupal\views\Tests\Wizard;

use Drupal\views\Tests\ViewTestBase;

/**
 * Views UI wizard tests.
 */
abstract class WizardTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'views_ui', 'block', 'rest'];

  protected function setUp() {
    parent::setUp();

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser(['administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view all revisions']);
    $this->drupalLogin($views_admin);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
