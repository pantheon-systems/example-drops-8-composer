<?php

namespace Drupal\FunctionalJavascriptTests\Core\Session;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests that sessions don't expire.
 *
 * @group session
 */
class SessionTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $menu_link_content = MenuLinkContent::create([
      'title' => 'Link to front page',
      'menu_name' => 'tools',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $menu_link_content->save();

    $this->drupalPlaceBlock('system_menu_block:tools');
  }

  /**
   * Tests that the session doesn't expire.
   *
   * Makes sure that drupal_valid_test_ua() works for multiple requests
   * performed by the Mink browser. The SIMPLETEST_USER_AGENT cookie must always
   * be valid.
   */
  public function testSessionExpiration() {
    // Visit the front page and click the link back to the front page a large
    // number of times.
    $this->drupalGet('<front>');

    $session_assert = $this->assertSession();

    $page = $this->getSession()->getPage();

    for ($i = 0; $i < 25; $i++) {
      $page->clickLink('Link to front page');
      $session_assert->statusCodeEquals(200);
    }
  }

}
