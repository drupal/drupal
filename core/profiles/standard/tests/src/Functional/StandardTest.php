<?php

namespace Drupal\Tests\standard\Functional;

use Drupal\Component\Utility\Html;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Url;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\user\Entity\Role;

/**
 * Tests Standard installation profile expectations.
 *
 * @group standard
 */
class StandardTest extends BrowserTestBase {

  use SchemaCheckTestTrait;
  use RequirementsPageTrait;

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
    $this->assertSession()->linkExists('Contact');
    $this->clickLink('Contact');
    $this->assertSession()->statusCodeEquals(200);

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
    $this->submitForm([
      'region' => 'sidebar_first',
      'id' => 'main_navigation',
    ], 'Save block');
    // Verify admin user can see the block.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Main navigation');

    // Verify we have role = complementary on help_block blocks.
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->elementAttributeContains('xpath', "//div[@id='block-bartik-help']", 'role', 'complementary');

    // Verify anonymous user can see the block.
    $this->drupalLogout();
    $this->assertSession()->pageTextContains('Main navigation');

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
    // Verify that a line break is present.
    $this->assertRaw('Then she picked out two somebodies,<br />Sally and me');
    $this->submitForm([
      'subject[0][value]' => 'Barfoo',
      'comment_body[0][value]' => 'Then she picked out two somebodies, Sally and me',
    ], 'Save');
    // Fetch the feed.
    $this->drupalGet('rss.xml');
    $this->assertSession()->responseContains('Foobar');
    $this->assertSession()->responseNotContains('Then she picked out two somebodies, Sally and me');

    // Ensure block body exists.
    $this->drupalGet('block/add');
    $this->assertSession()->fieldExists('body[0][value]');

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
    $this->assertEquals(['simpletest@example.com'], $recipients);

    $role = Role::create([
      'id' => 'admin_theme',
      'label' => 'Admin theme',
    ]);
    $role->grantPermission('view the administration theme');
    $role->save();
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');

    // Make sure the optional image styles are not installed.
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertSession()->pageTextNotContains('Max 325x325');
    $this->assertSession()->pageTextNotContains('Max 650x650');
    $this->assertSession()->pageTextNotContains('Max 1300x1300');
    $this->assertSession()->pageTextNotContains('Max 2600x2600');

    // Make sure the optional image styles are installed after enabling
    // the responsive_image module.
    \Drupal::service('module_installer')->install(['responsive_image']);
    $this->rebuildContainer();
    $this->drupalGet('admin/config/media/image-styles');
    $this->assertSession()->pageTextContains('Max 325x325');
    $this->assertSession()->pageTextContains('Max 650x650');
    $this->assertSession()->pageTextContains('Max 1300x1300');
    $this->assertSession()->pageTextContains('Max 2600x2600');

    // Verify certain routes' responses are cacheable by Dynamic Page Cache, to
    // ensure these responses are very fast for authenticated users.
    $this->dumpHeaders = TRUE;
    $this->drupalLogin($this->adminUser);
    $url = Url::fromRoute('contact.site_page');
    $this->drupalGet($url);
    // Verify that site-wide contact page cannot be cached by Dynamic Page
    // Cache.
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'UNCACHEABLE');

    $url = Url::fromRoute('<front>');
    $this->drupalGet($url);
    $this->drupalGet($url);
    // Verify that frontpage is cached by Dynamic Page Cache.
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');

    $url = Url::fromRoute('entity.node.canonical', ['node' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    // Verify that full node page is cached by Dynamic Page Cache.
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');

    $url = Url::fromRoute('entity.user.canonical', ['user' => 1]);
    $this->drupalGet($url);
    $this->drupalGet($url);
    // Verify that user profile page is cached by Dynamic Page Cache.
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');

    // Make sure the editorial workflow is installed after enabling the
    // content_moderation module.
    \Drupal::service('module_installer')->install(['content_moderation']);
    $role = Role::create([
      'id' => 'admin_workflows',
      'label' => 'Admin workflow',
    ]);
    $role->grantPermission('administer workflows');
    $role->save();
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
    $this->rebuildContainer();
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial');
    $this->assertSession()->pageTextContains('Draft');
    $this->assertSession()->pageTextContains('Published');
    $this->assertSession()->pageTextContains('Archived');
    $this->assertSession()->pageTextContains('Create New Draft');
    $this->assertSession()->pageTextContains('Publish');
    $this->assertSession()->pageTextContains('Archive');
    $this->assertSession()->pageTextContains('Restore to Draft');
    $this->assertSession()->pageTextContains('Restore');

    \Drupal::service('module_installer')->install(['media']);
    $role = Role::create([
      'id' => 'admin_media',
      'label' => 'Admin media',
    ]);
    $role->grantPermission('administer media');
    $role->grantPermission('administer media display');
    $role->save();
    $this->adminUser->addRole($role->id());
    $this->adminUser->save();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    /** @var \Drupal\media\Entity\MediaType $media_type */
    foreach (MediaType::loadMultiple() as $media_type) {
      $media_type_machine_name = $media_type->id();
      $this->drupalGet('media/add/' . $media_type_machine_name);
      // Get the form element, and its HTML representation.
      $form_selector = '#media-' . Html::cleanCssIdentifier($media_type_machine_name) . '-add-form';
      $form = $assert_session->elementExists('css', $form_selector);
      $form_html = $form->getOuterHtml();

      // The name field should be hidden.
      $assert_session->fieldNotExists('Name', $form);
      // The source field should be shown before the vertical tabs.
      $test_source_field = $assert_session->fieldExists($media_type->getSource()->getSourceFieldDefinition($media_type)->getLabel(), $form)->getOuterHtml();
      $vertical_tabs = $assert_session->elementExists('css', '.form-type-vertical-tabs', $form)->getOuterHtml();
      $this->assertGreaterThan(strpos($form_html, $test_source_field), strpos($form_html, $vertical_tabs));
      // The "Published" checkbox should be the last element.
      $date_field = $assert_session->fieldExists('Date', $form)->getOuterHtml();
      $published_checkbox = $assert_session->fieldExists('Published', $form)->getOuterHtml();
      $this->assertGreaterThan(strpos($form_html, $date_field), strpos($form_html, $published_checkbox));
      if (is_a($media_type->getSource(), Image::class, TRUE)) {
        // Assert the default entity view display is configured with an image
        // style.
        $this->drupalGet('/admin/structure/media/manage/' . $media_type->id() . '/display');
        $assert_session->fieldValueEquals('fields[field_media_image][type]', 'image');
        $assert_session->elementTextContains('css', 'tr[data-drupal-selector="edit-fields-field-media-image"]', 'Image style: Large (480×480)');
        // By default for media types with an image source, only the image
        // component should be enabled.
        $assert_session->elementsCount('css', 'input[name$="_settings_edit"]', 1);
      }

    }
  }

}
