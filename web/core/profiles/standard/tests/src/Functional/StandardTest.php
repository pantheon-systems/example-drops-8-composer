<?php

namespace Drupal\Tests\standard\Functional;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Url;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests Standard installation profile expectations.
 *
 * @group standard
 */
class StandardTest extends BrowserTestBase {

  use SchemaCheckTestTrait;

  protected $profile = 'standard';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Tests Standard installation profile.
   */
  public function testStandard() {
    $this->drupalGet('');
    $this->assertLink(t('Contact'));
    $this->clickLink(t('Contact'));
    $this->assertResponse(200);

    // Test anonymous user can access 'Main navigation' block.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'post comments',
      'skip comment approval',
      'create article content',
      'create page content',
    ]);
    $this->drupalLogin($this->adminUser);
    // Configure the block.
    $this->drupalGet('admin/structure/block/add/system_menu_block:main/bartik');
    $this->drupalPostForm(NULL, [
      'region' => 'sidebar_first',
      'id' => 'main_navigation',
    ], t('Save block'));
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertText('Main navigation');

    // Verify we have role = aria on system_powered_by and help_block
    // blocks.
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', [
      ':role' => 'complementary',
      ':id' => 'block-bartik-help',
    ]);

    $this->assertEqual(count($elements), 1, 'Found complementary role on help block.');

    $this->drupalGet('');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', [
      ':role' => 'complementary',
      ':id' => 'block-bartik-powered',
    ]);
    $this->assertEqual(count($elements), 1, 'Found complementary role on powered by block.');

    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertText('Main navigation');

    // Ensure comments don't show in the front page RSS feed.
    // Create an article.
    $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Foobar',
      'promote' => 1,
      'status' => 1,
      'body' => [['value' => 'Then she picked out two somebodies,<br />Sally and me', 'format' => 'basic_html']],
    ]);

    // Add a comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/1');
    $this->assertRaw('Then she picked out two somebodies,<br />Sally and me', 'Found a line break.');
    $this->drupalPostForm(NULL, [
      'subject[0][value]' => 'Barfoo',
      'comment_body[0][value]' => 'Then she picked out two somebodies, Sally and me',
    ], t('Save'));
    // Fetch the feed.
    $this->drupalGet('rss.xml');
    $this->assertText('Foobar');
    $this->assertNoText('Then she picked out two somebodies, Sally and me');

    // Ensure block body exists.
    $this->drupalGet('block/add');
    $this->assertFieldByName('body[0][value]');

    // Now we have all configuration imported, test all of them for schema
    // conformance. Ensures all imported default configuration is valid when
    // standard profile modules are enabled.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }

    // Ensure that configuration from the Standard profile is not reused when
    // enabling a module again since it contains configuration that can not be
    // installed. For example, editor.editor.basic_html is editor configuration
    // that depends on the ckeditor module. The ckeditor module can not be
    // installed before the editor module since it depends on the editor module.
    // The installer does not have this limitation since it ensures that all of
    // the install profiles dependencies are installed before creating the
    // editor configuration.
    foreach (FilterFormat::loadMultiple() as $filter) {
      // Ensure that editor can be uninstalled by removing use in filter
      // formats. It is necessary to prime the filter collection before removing
      // the filter.
      $filter->filters();
      $filter->removeFilter('editor_file_reference');
      $filter->save();
    }
    \Drupal::service('module_installer')->uninstall(['editor', 'ckeditor']);
    $this->rebuildContainer();
    \Drupal::service('module_installer')->install(['editor']);
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = ContactForm::load('feedback');
    $recipients = $contact_form->getRecipients();
    $this->assertEqual(['simpletest@example.com'], $recipients);

    $role = Role::create([
      'id' => 'admin_theme',
      'label' => 'Admin theme',
    ]);
    $role->grantPermission('view the administration theme');
    $role->save();
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
    $this->drupalGet('node/add');
    $this->assertResponse(200);

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->assertText('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');

    // Make sure the optional image styles are not installed.
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertNoText('Max 325x325');
    $this->assertNoText('Max 650x650');
    $this->assertNoText('Max 1300x1300');
    $this->assertNoText('Max 2600x2600');

    // Make sure the optional image styles are installed after enabling
    // the responsive_image module.
    \Drupal::service('module_installer')->install(['responsive_image']);
    $this->rebuildContainer();
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertText('Max 325x325');
    $this->assertText('Max 650x650');
    $this->assertText('Max 1300x1300');
    $this->assertText('Max 2600x2600');

    // Verify certain routes' responses are cacheable by Dynamic Page Cache, to
    // ensure these responses are very fast for authenticated users.
    $this->dumpHeaders = TRUE;
    $this->drupalLogin($this->adminUser);
    $url = Url::fromRoute('contact.site_page');
    $this->drupalGet($url);
    $this->assertEqual('UNCACHEABLE', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Site-wide contact page cannot be cached by Dynamic Page Cache.');

    $url = Url::fromRoute('<front>');
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Frontpage is cached by Dynamic Page Cache.');

    $url = Url::fromRoute('entity.node.canonical', ['node' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Full node page is cached by Dynamic Page Cache.');

    $url = Url::fromRoute('entity.user.canonical', ['user' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'User profile page is cached by Dynamic Page Cache.');
  }

}
