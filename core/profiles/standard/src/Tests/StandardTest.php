<?php

/**
 * @file
 * Contains \Drupal\standard\Tests\StandardTest.
 */

namespace Drupal\standard\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\contact\Entity\ContactForm;
use Drupal\filter\Entity\FilterFormat;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests Standard installation profile expectations.
 *
 * @group standard
 */
class StandardTest extends WebTestBase {

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
  function testStandard() {
    $this->drupalGet('');
    $this->assertLink(t('Contact'));
    $this->clickLink(t('Contact'));
    $this->assertResponse(200);

    // Test anonymous user can access 'Main navigation' block.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'post comments',
      'skip comment approval',
      'create article content',
      'create page content',
    ));
    $this->drupalLogin($this->adminUser);
    // Configure the block.
    $this->drupalGet('admin/structure/block/add/system_menu_block:main/bartik');
    $this->drupalPostForm(NULL, array(
      'region' => 'sidebar_first',
      'id' => 'main_navigation',
    ), t('Save block'));
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertText('Main navigation');

    // Verify we have role = aria on system_powered_by and help_block
    // blocks.
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', array(
      ':role' => 'complementary',
      ':id' => 'block-bartik-help',
    ));

    $this->assertEqual(count($elements), 1, 'Found complementary role on help block.');

    $this->drupalGet('');
    $elements = $this->xpath('//div[@role=:role and @id=:id]', array(
      ':role' => 'complementary',
      ':id' => 'block-bartik-powered',
    ));
    $this->assertEqual(count($elements), 1, 'Found complementary role on powered by block.');

    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertText('Main navigation');

    // Ensure comments don't show in the front page RSS feed.
    // Create an article.
    $this->drupalCreateNode(array(
      'type' => 'article',
      'title' => 'Foobar',
      'promote' => 1,
      'status' => 1,
      'body' => array(array('value' => 'Then she picked out two somebodies,<br />Sally and me', 'format' => 'basic_html')),
    ));

    // Add a comment.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/1');
    $this->assertRaw('Then she picked out two somebodies,<br />Sally and me', 'Found a line break.');
    $this->drupalPostForm(NULL, array(
      'subject[0][value]' => 'Barfoo',
      'comment_body[0][value]' => 'Then she picked out two somebodies, Sally and me',
    ), t('Save'));
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
    \Drupal::service('module_installer')->uninstall(array('editor', 'ckeditor'));
    $this->rebuildContainer();
    \Drupal::service('module_installer')->install(array('editor'));
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
  }

}
