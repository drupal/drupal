<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\Tests\media\Traits\OEmbedTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Contains Media Library integration tests.
 *
 * @group media_library
 */
class MediaLibraryTest extends WebDriverTestBase {

  use TestFileCreationTrait;
  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */

  protected static $modules = [
    'block',
    'media_library_test',
    'field_ui',
    'views',
    'views_ui',
    'media_test_oembed',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->lockHttpClientToFixtures();

    // Create a few example media items for use in selection.
    $media = [
      'type_one' => [
        'Horse',
        'Bear',
        'Cat',
        'Dog',
      ],
      'type_two' => [
        'Crocodile',
        'Lizard',
        'Snake',
        'Turtle',
      ],
    ];

    $time = time();
    foreach ($media as $type => $names) {
      foreach ($names as $name) {
        $entity = Media::create(['name' => $name, 'bundle' => $type]);
        $source_field = $type === 'type_one' ? 'field_media_test' : 'field_media_test_1';
        $entity->setCreatedTime(++$time);
        $entity->set($source_field, $name);
        $entity->save();
      }
    }

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own basic_page content',
      'create basic_page content',
      'create media',
      'update any media',
      'delete any media',
      'view media',
      'administer node form display',
      'administer views',
    ]);
    $this->drupalLogin($user);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests that the Media Library's administration page works as expected.
   */
  public function testAdministrationPage() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Visit the administration page.
    $this->drupalGet('admin/content/media');

    // There should be links to both the grid and table displays.
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // We should see the table view and a link to add media.
    $assert_session->elementExists('css', '.view-media .views-table');
    $assert_session->linkExists('Add media');

    // Go to the grid display for the rest of the test.
    $page->clickLink('Grid');
    $assert_session->addressEquals('admin/content/media-grid');

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Verify that the media name does not contain a link.
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    // Verify that there are links to edit and delete media items.
    $assert_session->linkExists('Edit Dog');
    $assert_session->linkExists('Delete Turtle');

    // Test that users can filter by type.
    $page->selectFieldOption('Media type', 'Type One');
    $page->pressButton('Apply filters');
    $this->waitForNoText('Turtle');
    $assert_session->pageTextContains('Dog');
    $page->selectFieldOption('Media type', 'Type Two');
    $page->pressButton('Apply filters');
    $this->waitForText('Turtle');
    $assert_session->pageTextNotContains('Dog');

    // Test that selecting elements as a part of bulk operations works.
    $page->selectFieldOption('Media type', '- Any -');
    $assert_session->elementExists('css', '#views-exposed-form-media-library-page')->submit();
    $this->waitForText('Dog');

    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select-trigger a")[4].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    // For reasons that are not clear, deleting media items by pressing the
    // "Delete" button can fail (the button is found, but never actually pressed
    // by the Mink driver). This workaround allows the delete form to be
    // submitted.
    $assert_session->elementExists('css', 'form')->submit();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');

    // Test the 'Select all media' checkbox and assert that it makes the
    // expected announcements.
    $select_all = $this->waitForFieldExists('Select all media');
    $select_all->check();
    $this->waitForText('All 7 items selected');
    $select_all->uncheck();
    $this->waitForText('Zero items selected');
    $select_all->check();
    $page->selectFieldOption('Action', 'media_delete_action');
    $this->submitForm([], 'Apply to selected items');
    // For reasons that are not clear, deleting media items by pressing the
    // "Delete" button can fail (the button is found, but never actually pressed
    // by the Mink driver). This workaround allows the delete form to be
    // submitted.
    $assert_session->elementExists('css', 'form')->submit();

    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Test empty text.
    $assert_session->pageTextContains('No media available.');
  }

  /**
   * Tests that the widget works as expected when media types are deleted.
   */
  public function testWidgetWithoutMediaTypes() {
    $assert_session = $this->assertSession();

    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);

    $default_message = 'There are no allowed media types configured for this field. Please contact the site administrator.';

    $this->drupalGet('node/add/basic_page');

    // Assert a properly configured field does not show a message.
    $assert_session->elementTextNotContains('css', '.field--name-field-twin-media', 'There are no allowed media types configured for this field.');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array. No types are allowed in this
    // case.
    $assert_session->elementTextContains('css', '.field--name-field-empty-types-media', $default_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is not shown when the target_bundles setting for
    // the entity reference field is null. All types are allowed in this case.
    $assert_session->elementTextNotContains('css', '.field--name-field-null-types-media', 'There are no allowed media types configured for this field.');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_null_types_media"]');

    // Delete all media and media types.
    $entity_type_manager = \Drupal::entityTypeManager();
    $media_storage = $entity_type_manager->getStorage('media');
    $media_type_storage = $entity_type_manager->getStorage('media_type');
    $media_storage->delete($media_storage->loadMultiple());
    $media_type_storage->delete($media_type_storage->loadMultiple());

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Assert a properly configured field now shows a message.
    $assert_session->elementTextContains('css', '.field--name-field-twin-media', $default_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementTextContains('css', '.field--name-field-empty-types-media', $default_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for
    // the entity reference field is null.
    $assert_session->elementTextContains('css', '.field--name-field-null-types-media', $default_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_null_types_media"]');

    // Assert a different message is shown when the user is allowed to
    // administer the fields.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'view media',
      'administer node fields',
    ]);
    $this->drupalLogin($user);

    $route_bundle_params = FieldUI::getRouteBundleParameter(\Drupal::entityTypeManager()->getDefinition('node'), 'basic_page');

    $field_twin_url = new Url('entity.field_config.node_field_edit_form', [
      'field_config' => 'node.basic_page.field_twin_media',
    ] + $route_bundle_params);
    $field_twin_message = 'There are no allowed media types configured for this field. <a href="' . $field_twin_url->toString() . '">Edit the field settings</a> to select the allowed media types.';

    $field_empty_types_url = new Url('entity.field_config.node_field_edit_form', [
      'field_config' => 'node.basic_page.field_empty_types_media',
    ] + $route_bundle_params);
    $field_empty_types_message = 'There are no allowed media types configured for this field. <a href="' . $field_empty_types_url->toString() . '">Edit the field settings</a> to select the allowed media types.';

    $field_null_types_url = new Url('entity.field_config.node_field_edit_form', [
        'field_config' => 'node.basic_page.field_null_types_media',
      ] + $route_bundle_params);
    $field_null_types_message = 'There are no allowed media types configured for this field. <a href="' . $field_null_types_url->toString() . '">Edit the field settings</a> to select the allowed media types.';

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Assert a properly configured field still shows a message.
    $assert_session->elementContains('css', '.field--name-field-twin-media', $field_twin_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_empty_types_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is null.
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_null_types_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_null_types_media"]');

    // Assert the messages are also shown in the default value section of the
    // field edit form.
    $this->drupalGet($field_empty_types_url);
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_empty_types_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_empty_types_media"]');
    $this->drupalGet($field_null_types_url);
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_null_types_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_null_types_media"]');

    // Uninstall the Field UI and check if the link is removed from the message.
    \Drupal::service('module_installer')->uninstall(['field_ui']);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $field_ui_uninstalled_message = 'There are no allowed media types configured for this field. Edit the field settings to select the allowed media types.';

    // Assert the link is now longer part of the message.
    $assert_session->elementNotExists('named', ['link', 'Edit the field settings']);
    // Assert a properly configured field still shows a message.
    $assert_session->elementContains('css', '.field--name-field-twin-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is null.
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.js-media-library-open-button[name^="field_null_types_media"]');
  }

  /**
   * Tests that the integration with Views works correctly.
   */
  public function testViewsAdmin() {
    $page = $this->getSession()->getPage();

    // Assert that the widget can be seen and that there are 8 items.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget');
    $this->waitForElementsCount('css', '.js-media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.js-media-library-view .view-filters')->fillField('name', 'snake');
    $page->find('css', '.js-media-library-view .view-filters')->pressButton('Apply filters');
    $this->waitForElementsCount('css', '.js-media-library-item', 1);

    // Test the same routine but in the view for the table wiget.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget_table');
    $this->waitForElementsCount('css', '.js-media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.js-media-library-view .view-filters')->fillField('name', 'snake');
    $page->find('css', '.js-media-library-view .view-filters')->pressButton('Apply filters');
    $this->waitForElementsCount('css', '.js-media-library-item', 1);

    // We cannot test clicking the 'Insert selected' button in either view
    // because we expect an AJAX error, which would always throw an exception
    // on ::tearDown even if we try to catch it here. If there is an API for
    // marking certain elements 'unsuitable for previewing', we could test that
    // here.
    // @see https://www.drupal.org/project/drupal/issues/3060852
  }

  /**
   * Tests that the widget access works as expected.
   */
  public function testWidgetAccess() {
    $assert_session = $this->assertSession();

    $this->drupalLogout();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('view media');
    $role->save();

    // Create a working state.
    $allowed_types = ['type_one', 'type_two', 'type_three', 'type_four'];
    // The opener parameters are not relevant to the test, but the opener
    // expects them to be there or it will deny access.
    $state = MediaLibraryState::create('media_library.opener.field_widget', $allowed_types, 'type_three', 2, [
      'entity_type_id' => 'node',
      'bundle' => 'basic_page',
      'field_name' => 'field_unlimited_media',
    ]);
    $url_options = ['query' => $state->all()];

    // Verify that unprivileged users can't access the widget view.
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('admin/content/media-widget-table', $url_options);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', $url_options);
    $assert_session->responseContains('Access denied');

    // Allow users with 'view media' permission to access the media library view
    // and controller. Since we are using the node entity type in the state
    // object, ensure the user also has permission to work with those.
    $this->grantPermissions($role, [
      'create basic_page content',
      'view media',
    ]);
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $this->drupalGet('admin/content/media-widget-table', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    // Assert the user does not have access to the media add form if the user
    // does not have the 'create media' permission.
    $assert_session->fieldNotExists('files[upload][]');

    // Assert users can not access the widget displays of the media library view
    // without a valid media library state.
    $this->drupalGet('admin/content/media-widget');
    $assert_session->responseContains('Access denied');
    $this->drupalGet('admin/content/media-widget-table');
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library');
    $assert_session->responseContains('Access denied');

    // Assert users with the 'create media' permission can access the media add
    // form.
    $this->grantPermissions($role, [
      'create media',
    ]);
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $assert_session->fieldExists('Add files');

    // Assert the media library can not be accessed if the required state
    // parameters are changed without changing the hash.
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_opener_id' => 'fail']),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_allowed_types' => ['type_one', 'type_two']]),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_selected_type' => 'type_one']),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_remaining' => 3]),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['hash' => 'fail']),
    ]);
    $assert_session->responseContains('Access denied');
  }

  /**
   * Tests that the Media library's widget works as expected.
   */
  public function testWidget() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Assert that media widget instances are present.
    $assert_session->pageTextContains('Unlimited media');
    $assert_session->pageTextContains('Twin media');
    $assert_session->pageTextContains('Single media type');
    $assert_session->pageTextContains('Empty types media');

    // Assert generic media library elements.
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when more than 1 type is
    // configured for the field.
    $menu = $this->openMediaLibraryForField('field_unlimited_media');
    $this->assertTrue($menu->hasLink('Show Type One media (selected)'));
    $this->assertFalse($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertFalse($menu->hasLink('Type Four'));
    $this->switchToMediaType('Three');
    // Assert the active tab is set correctly.
    $this->assertFalse($menu->hasLink('Show Type One media (selected)'));
    $this->assertTrue($menu->hasLink('Show Type Three media (selected)'));
    // Assert the focus is set to the first tabbable element when a vertical tab
    // is clicked.
    $this->assertJsCondition('jQuery("#media-library-content :tabbable:first").is(":focus")');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that there are no links in the media library view.
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    $assert_session->elementNotExists('css', '.view-media-library .media-library-item__edit');
    $assert_session->elementNotExists('css', '.view-media-library .media-library-item__remove');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when the target_bundles
    // setting for the entity reference field is null. All types should be
    // allowed in this case.
    $menu = $this->openMediaLibraryForField('field_null_types_media');
    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertTrue($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertTrue($menu->hasLink('Type Four'));
    $this->assertTrue($menu->hasLink('Type Five'));
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is not available when only 1 type is
    // configured for the field.
    $this->openMediaLibraryForField('field_single_media_type', '#media-library-wrapper');
    $this->waitForElementTextContains('.media-library-selected-count', '0 of 1 item selected');

    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $this->selectMediaItem(0);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    $this->assertSelectedMediaCount('1 of 1 item selected');
    $assert_session->elementNotExists('css', '.js-media-library-menu');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the menu links can be sorted through the widget configuration.
    $this->openMediaLibraryForField('field_twin_media');
    $links = $this->getTypesMenu()->findAll('css', 'a');
    $link_titles = [];
    foreach ($links as $link) {
      $link_titles[] = $link->getText();
    }
    $expected_link_titles = ['Show Type Three media (selected)', 'Show Type One media', 'Show Type Two media', 'Show Type Four media'];
    $this->assertSame($link_titles, $expected_link_titles);
    $this->drupalGet('admin/structure/types/manage/basic_page/form-display');
    $assert_session->buttonExists('field_twin_media_settings_edit')->press();
    $this->assertElementExistsAfterWait('css', '#field-twin-media .tabledrag-toggle-weight')->press();
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_one][weight]')->selectOption(0);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_three][weight]')->selectOption(1);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_four][weight]')->selectOption(2);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_two][weight]')->selectOption(3);
    $assert_session->buttonExists('Save')->press();

    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');
    $link_titles = array_map(function ($link) {
      return $link->getText();
    }, $links);
    $this->assertSame($link_titles, ['Show Type One media (selected)', 'Show Type Three media', 'Show Type Four media', 'Show Type Two media']);
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the announcements for media type navigation in the media library.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->assertNotEmpty($assert_session->waitForText('Showing Type Three media.'));
    $this->switchToMediaType('One');
    $this->assertNotEmpty($assert_session->waitForText('Showing Type One media.'));
    // Assert the links can be triggered by via the spacebar.
    $assert_session->elementExists('named', ['link', 'Type Three'])->keyPress(32);
    $this->waitForText('Showing Type Three media.');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert media is only visible on the tab for the related media type.
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $this->switchToMediaType('Three');
    $this->assertNotEmpty($assert_session->waitForText('Showing Type Three media.'));
    $assert_session->elementExists('named', ['link', 'Show Type Three media (selected)']);
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the exposed name filter of the view.
    $this->openMediaLibraryForField('field_unlimited_media');
    $session = $this->getSession();
    $session->getPage()->fillField('Name', 'Dog');
    $session->getPage()->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForNoText('Bear');
    $session->getPage()->fillField('Name', '');
    $session->getPage()->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForText('Bear');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert adding a single media item and removing it.
    $this->openMediaLibraryForField('field_twin_media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');

    // Assert that we can toggle the visibility of the weight inputs.
    $wrapper = $assert_session->elementExists('css', '.field--name-field-twin-media');
    $wrapper->pressButton('Show media item weights');
    $assert_session->fieldExists('Weight', $wrapper)->click();
    $wrapper->pressButton('Hide media item weights');

    // Remove the selected item.
    $button = $assert_session->buttonExists('Remove', $wrapper);
    $this->assertSame('Remove Dog', $button->getAttribute('aria-label'));
    $button->press();
    $this->waitForText('Removed Dog.');
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');

    // Assert we can select the same media item twice.
    $this->openMediaLibraryForField('field_twin_media');
    $page->checkField('Select Dog');
    $this->pressInsertSelected('Added one media item.');
    $this->openMediaLibraryForField('field_twin_media');
    $page->checkField('Select Dog');
    $this->pressInsertSelected('Added one media item.');

    // Assert the same has been added twice and remove the items again.
    $this->waitForElementsCount('css', '.field--name-field-twin-media [data-media-library-item-delta]', 2);
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][0][target_id]', 4);
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][1][target_id]', 4);
    $wrapper->pressButton('Remove');
    $this->waitForText('Removed Dog.');
    $wrapper->pressButton('Remove');
    $this->waitForText('Removed Dog.');
    $result = $wrapper->waitFor(10, function ($wrapper) {
      /** @var \Behat\Mink\Element\NodeElement $wrapper */
      return $wrapper->findButton('Remove') == NULL;
    });
    $this->assertTrue($result);

    // Assert the selection is persistent in the media library modal, and
    // the number of selected items is displayed correctly.
    $this->openMediaLibraryForField('field_twin_media');
    // Assert the number of selected items is displayed correctly.
    $this->assertSelectedMediaCount('0 of 2 items selected');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $this->getCheckboxes();
    $this->assertCount(4, $checkboxes);
    $this->selectMediaItem(0, '1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    // Select another item and assert the number of selected items is updated.
    $this->selectMediaItem(1, '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4,3');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the selected items are updated when deselecting an item.
    $checkboxes[0]->click();
    $this->assertSelectedMediaCount('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3');
    // Assert deselected items are available again.
    $this->assertFalse($checkboxes[2]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[3]->hasAttribute('disabled'));
    // The selection should be persisted when navigating to other media types in
    // the modal.
    $this->switchToMediaType('Three');
    $this->switchToMediaType('One');
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getValue();
      }
    }
    $this->assertCount(1, $selected_checkboxes);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', implode(',', $selected_checkboxes));
    $this->assertSelectedMediaCount('1 of 2 items selected');
    // Add to selection from another type.
    $this->switchToMediaType('Two');
    $checkboxes = $this->getCheckboxes();
    $this->assertCount(4, $checkboxes);
    $this->selectMediaItem(0, '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3,8');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertFalse($checkboxes[0]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the checkboxes are also disabled on other pages.
    $this->switchToMediaType('One');
    $this->assertTrue($checkboxes[0]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Select the items.
    $this->pressInsertSelected('Added 2 media items.');
    // Assert the open button is disabled.
    $open_button = $this->assertElementExistsAfterWait('css', '.js-media-library-open-button[name^="field_twin_media"]');
    $this->assertTrue($open_button->hasAttribute('data-disabled-focus'));
    $this->assertTrue($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":disabled")');

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');

    // Remove "Cat" (happens to be the first remove button on the page).
    $button = $assert_session->buttonExists('Remove', $wrapper);
    $this->assertSame('Remove Cat', $button->getAttribute('aria-label'));
    $button->press();
    $this->waitForText('Removed Cat.');
    // Assert the focus is set to the wrapper of the other selected item.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper [data-media-library-item-delta]").is(":focus")');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    // Assert the open button is no longer disabled.
    $open_button = $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    $this->assertFalse($open_button->hasAttribute('data-disabled-focus'));
    $this->assertFalse($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":not(:disabled)")');

    // Open the media library again and select another item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');
    $this->waitForElementTextContains('#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');
    // Assert the open button is disabled.
    $this->assertTrue($assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]')->hasAttribute('data-disabled-focus'));
    $this->assertTrue($assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]')->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":disabled")');

    // Assert the selection is cleared when the modal is closed.
    $this->openMediaLibraryForField('field_unlimited_media');
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    // Nothing is selected yet.
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $this->assertSelectedMediaCount('0 items selected');
    // Select the first 2 items.
    $checkboxes[0]->click();
    $this->assertSelectedMediaCount('1 item selected');
    $checkboxes[1]->click();
    $this->assertSelectedMediaCount('2 items selected');
    $this->assertTrue($checkboxes[0]->isChecked());
    $this->assertTrue($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    // Close the dialog, reopen it and assert not is selected again.
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();
    $this->openMediaLibraryForField('field_unlimited_media');
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Finally, save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_twin_media[selection][0][weight]' => '3',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    // We removed this item earlier.
    $assert_session->pageTextNotContains('Cat');
    // This item was never selected.
    $assert_session->pageTextNotContains('Snake');
    // "Turtle" should come after "Dog", since we changed the weight.
    $assert_session->elementExists('css', '.field--name-field-twin-media > .field__items > .field__item:last-child:contains("Turtle")');
    // Make sure everything that was selected shows up.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Re-edit the content and make a new selection.
    $this->drupalGet('node/1/edit');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
    $this->openMediaLibraryForField('field_unlimited_media');
    // Select all media items of type one (should also contain Dog, again).
    $this->selectMediaItem(0);
    $this->selectMediaItem(1);
    $this->selectMediaItem(2);
    $this->selectMediaItem(3);
    $this->pressInsertSelected('Added 4 media items.');
    $this->waitForText('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
  }

  /**
   * Tests that the views in the Media library's widget work as expected.
   */
  public function testWidgetViews() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create more media items for use in selection. We want to have more than
    // 24 items to trigger a pager in the widget view.
    $media_names = [
      'Goat',
      'Sheep',
      'Pig',
      'Cow',
      'Chicken',
      'Duck',
      'Donkey',
      'Llama',
      'Mouse',
      'Goldfish',
      'Rabbit',
      'Turkey',
      'Dove',
      'Giraffe',
      'Tiger',
      'Hamster',
      'Parrot',
      'Monkey',
      'Koala',
      'Panda',
      'Kangaroo',
    ];

    $time = time();
    foreach ($media_names as $name) {
      $entity = Media::create(['name' => $name, 'bundle' => 'type_one']);
      $entity->setCreatedTime(++$time);
      $entity->set('field_media_test', $name);
      $entity->save();
    }

    $this->drupalGet('node/add/basic_page');

    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the 'Apply filter' button is not moved to the button pane.
    $button_pane = $assert_session->elementExists('css', '.ui-dialog-buttonpane');
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);

    // Assert the pager works as expected.
    $assert_session->elementTextContains('css', '.js-media-library-view .pager__item.is-active', 'Page 1');
    $this->assertCount(24, $this->getCheckboxes());
    $page->clickLink('Next page');
    $this->waitForElementTextContains('.js-media-library-view .pager__item.is-active', 'Page 2');
    $this->assertCount(1, $this->getCheckboxes());
    $page->clickLink('Previous page');
    $this->waitForElementTextContains('.js-media-library-view .pager__item.is-active', 'Page 1');
    $this->assertCount(24, $this->getCheckboxes());

    $this->switchToMediaLibraryTable();

    // Assert the 'Apply filter' button is not moved to the button pane.
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');

    // Assert the exposed filters can be applied.
    $page->fillField('Name', 'Dog');
    $page->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForNoText('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->checkField('Select Dog');
    $assert_session->linkExists('Table');
    $this->switchToMediaLibraryGrid();

    // Assert the exposed filters are persisted when changing display.
    $this->assertSame('Dog', $page->findField('Name')->getValue());
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->linkExists('Grid');
    $this->switchToMediaLibraryTable();

    // Select the item.
    $this->pressInsertSelected('Added one media item.');
    // Ensure that the selection completed successfully.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
  }

  /**
   * Tests that the widget works as expected for anonymous users.
   */
  public function testWidgetAnonymous() {
    $assert_session = $this->assertSession();

    $this->drupalLogout();

    // Allow the anonymous user to create pages and view media.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, [
      'access content',
      'create basic_page content',
      'view media',
    ]);

    // Ensure the widget works as an anonymous user.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Select the first media item (should be Dog).
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');

    // Ensure that the selection completed successfully.
    $this->waitForText('Dog');

    // Save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_unlimited_media[selection][0][weight]' => '0',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    $assert_session->pageTextContains('Dog');
  }

  /**
   * Tests that uploads in the Media library's widget works as expected.
   *
   * Note that this test will occasionally fail with SQLite until
   * https://www.drupal.org/node/3066447 is addressed.
   */
  public function testWidgetUpload() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'png') {
        $png_image = $image;
      }
      elseif ($extension === 'jpg') {
        $jpg_image = $image;
      }
    }

    if (!isset($png_image) || !isset($jpg_image)) {
      $this->fail('Expected test files not present.');
    }

    // Create a user that can only add media of type four.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_one media',
      'create type_four media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is not visible for default tab type_three without
    // the proper permissions.
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is not visible for the non-file based media type
    // type_one.
    $this->switchToMediaType('One');
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is visible for type_four.
    $this->switchToMediaType('Four');
    $assert_session->fieldExists('Add files');
    $assert_session->pageTextContains('Maximum 2 files.');

    // Create a user that can create media for all media types.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $file_storage = $this->container->get('entity_type.manager')->getStorage('file');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // Add to the twin media field.
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is now visible for default tab type_three.
    $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldExists('Add files');

    // Assert we can upload a file to the default tab type_three.
    $assert_session->elementNotExists('css', '.js-media-library-add-form[data-input]');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $this->assertMediaAdded();
    $assert_session->elementExists('css', '.js-media-library-add-form[data-input]');
    // We do not have pre-selected items, so the container should not be added
    // to the form.
    $assert_session->pageTextNotContains('Additional selected media');
    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());
    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);
    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $this->pressSaveButton(TRUE);
    $this->waitForText('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    $this->assertJsCondition('jQuery("input[name=\'media_library_select_form[0]\']").is(":focus")');
    // The file should be permanent now.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertFalse($file->isTemporary());
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->pageTextContains('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($png_image->filename);

    // Remove the item.
    $assert_session->elementExists('css', '.field--name-field-twin-media')->pressButton('Remove');
    $this->waitForNoText($png_image->filename);

    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Three');
    $png_uri_2 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    $this->pressSaveButton();
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_2));

    // Also make sure that we can upload to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $existing_media_name = $file_system->basename($png_uri_2);
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $png_uri_3 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_3));
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Unlimited Cardinality Image');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Unlimited Cardinality Image');

    // Assert we can now only upload one more media item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Four');
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $png_uri_4 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($png_uri_4), FALSE);
    $this->waitForText('Only files with the following extensions are allowed');
    // Assert that jpg files are accepted by type four.
    $jpg_uri_2 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($jpg_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $jpg_uri_3 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_uri_3));
    $this->waitForText($file_system->basename($jpg_uri_3));
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));
    $this->pressSaveButton();
    // Ensure the media item was saved to the library and automatically
    // selected.
    $this->waitForText('Add or select media');
    $this->waitForText($file_system->basename($jpg_uri_2));
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));

    // Assert we can also remove selected items from the selection area in the
    // upload form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    $png_uri_5 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_5));
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    $page->uncheckField('media_library_select_form[2]');
    $this->waitForText('1 item selected');
    $this->waitForText("Select $existing_media_name");
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxNotChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_5));

    // Assert removing an uploaded media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->waitForFieldExists('Alternative text');
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $png_image->filename has been removed.");
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertNoMediaAdded();
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert uploading multiple files.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    // Assert the existing items are remembered when adding and removing media.
    $checkbox = $page->findField("Select $existing_media_name");
    $checkbox->click();
    // Assert we can add multiple files.
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 4) as $i) {
      $path = $file_system->copy($png_image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    $assert_session->fieldValueEquals('media[0][fields][name][0][value]', $filenames[0]);
    $assert_session->fieldValueEquals('media[1][fields][name][0][value]', $filenames[1]);
    $assert_session->fieldValueEquals('media[2][fields][name][0][value]', $filenames[2]);
    $assert_session->fieldValueEquals('media[3][fields][name][0][value]', $filenames[3]);
    // Set alt texts for items 1 and 2, leave the alt text empty for items 3
    // and 4 to assert the field validation does not stop users from removing
    // items.
    $page->fillField('media[0][fields][field_media_test_image][0][alt]', $filenames[0]);
    $page->fillField('media[1][fields][field_media_test_image][0][alt]', $filenames[1]);
    // Assert the file is available in the file storage.
    $files = $file_storage->loadByProperties(['filename' => $filenames[1]]);
    $this->assertCount(1, $files);
    $file_1_uri = reset($files)->getFileUri();
    // Remove the second file and assert the focus is shifted to the container
    // of the next media item and field values are still correct.
    $page->pressButton('media-1-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=2]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[1] . ' has been removed.');
    // Assert the file was deleted.
    $this->assertEmpty($file_storage->loadByProperties(['filename' => $filenames[1]]));
    $this->assertFileNotExists($file_1_uri);

    // When a file is already in usage, it should not be deleted. To test,
    // let's add a usage for $filenames[3] (now in the third position).
    $files = $file_storage->loadByProperties(['filename' => $filenames[3]]);
    $this->assertCount(1, $files);
    $target_file = reset($files);
    Media::create([
      'bundle' => 'type_three',
      'name' => 'Disturbing',
      'field_media_test_image' => [
        ['target_id' => $target_file->id()],
      ],
    ])->save();
    // Remove $filenames[3] (now in the third position) and assert the focus is
    // shifted to the container of the previous media item and field values are
    // still correct.
    $page->pressButton('media-3-remove-button');
    $this->assertTrue($assert_session->waitForText('The media item ' . $filenames[3] . ' has been removed.'));
    // Assert the file was not deleted, due to being in use elsewhere.
    $this->assertNotEmpty($file_storage->loadByProperties(['filename' => $filenames[3]]));
    $this->assertFileExists($target_file->getFileUri());

    // The second media item should be removed (this has the delta 1 since we
    // start counting from 0).
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
    $media_item_three = $assert_session->elementExists('css', '[data-media-library-added-delta=2]');
    $assert_session->fieldValueEquals('Name', $filenames[2], $media_item_three);
    $assert_session->fieldValueEquals('Alternative text', '', $media_item_three);
  }

  /**
   * Tests that uploads in the widget's advanced UI works as expected.
   *
   * Note that this test will occasionally fail with SQLite until
   * https://www.drupal.org/node/3066447 is addressed.
   *
   * @todo Merge this with testWidgetUpload() in
   *   https://www.drupal.org/project/drupal/issues/3087227
   */
  public function testWidgetUploadAdvancedUi() {
    $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'png') {
        $png_image = $image;
      }
      elseif ($extension === 'jpg') {
        $jpg_image = $image;
      }
    }

    if (!isset($png_image) || !isset($jpg_image)) {
      $this->fail('Expected test files not present.');
    }

    // Create a user that can only add media of type four.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_one media',
      'create type_four media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is not visible for default tab type_three without
    // the proper permissions.
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is not visible for the non-file based media type
    // type_one.
    $this->switchToMediaType('One');
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is visible for type_four.
    $this->switchToMediaType('Four');
    $assert_session->fieldExists('Add files');
    $assert_session->pageTextContains('Maximum 2 files.');

    // Create a user that can create media for all media types.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $file_storage = $this->container->get('entity_type.manager')->getStorage('file');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // Add to the twin media field.
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is now visible for default tab type_three.
    $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldExists('Add files');

    // Assert we can upload a file to the default tab type_three.
    $assert_session->elementNotExists('css', '.js-media-library-add-form[data-input]');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $this->assertMediaAdded();
    $assert_session->elementExists('css', '.js-media-library-add-form[data-input]');
    // We do not have a pre-selected items, so the container should not be added
    // to the form.
    $assert_session->elementNotExists('css', 'details summary:contains(Additional selected media)');
    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());
    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);
    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $this->saveAnd('select');
    $this->waitForText('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $this->saveAnd('select');
    $this->assertJsCondition('jQuery("input[name=\'media_library_select_form[0]\']").is(":focus")');
    // The file should be permanent now.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertFalse($file->isTemporary());
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->pageTextContains('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($png_image->filename);

    // Remove the item.
    $assert_session->elementExists('css', '.field--name-field-twin-media')->pressButton('Remove');
    $this->waitForNoText($png_image->filename);

    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Three');
    $png_uri_2 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // Assert we can also directly insert uploaded files in the widget.
    $this->saveAnd('insert');
    $this->waitForText('Added one media item.');
    $this->waitForNoText('Add or select media');
    $this->waitForText($file_system->basename($png_uri_2));

    // Also make sure that we can upload to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $existing_media_name = $file_system->basename($png_uri_2);
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $png_uri_3 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_3));
    $this->waitForText('The media item has been created but has not yet been saved.');
    $assert_session->checkboxChecked("Select $existing_media_name");
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $this->saveAnd('select');
    $this->waitForNoText('Save and select');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Unlimited Cardinality Image');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Unlimited Cardinality Image');

    // Assert we can now only upload one more media item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Four');
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $png_uri_4 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($png_uri_4), FALSE);
    $this->waitForText('Only files with the following extensions are allowed');
    // Assert that jpg files are accepted by type four.
    $jpg_uri_2 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($jpg_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $jpg_uri_3 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_uri_3));
    $this->waitForText($file_system->basename($jpg_uri_3));
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));
    $this->saveAnd('select');
    // Ensure the media item was saved to the library and automatically
    // selected.
    $this->waitForText('Add or select media');
    $this->waitForText($file_system->basename($jpg_uri_2));
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));

    // Assert we can also remove selected items from the selection area in the
    // upload form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    $png_uri_5 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_5));
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    // Assert the pre-selected items are shown.
    $selection_area = $this->getSelectionArea();
    $assert_session->checkboxChecked("Select $existing_media_name", $selection_area);
    $selection_area->uncheckField("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $selection_area->find('css', 'summary')->click();
    $this->saveAnd('select');
    $this->waitForText("Select $existing_media_name");
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxNotChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_5));

    // Assert removing an uploaded media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $png_image->filename has been removed.");
    $this->assertNoMediaAdded();
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert uploading multiple files.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    // Assert the existing items are remembered when adding and removing media.
    $checkbox = $page->findField("Select $existing_media_name");
    $checkbox->click();
    // Assert we can add multiple files.
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 4) as $i) {
      $path = $file_system->copy($png_image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    $assert_session->fieldValueEquals('media[0][fields][name][0][value]', $filenames[0]);
    $assert_session->fieldValueEquals('media[1][fields][name][0][value]', $filenames[1]);
    $assert_session->fieldValueEquals('media[2][fields][name][0][value]', $filenames[2]);
    $assert_session->fieldValueEquals('media[3][fields][name][0][value]', $filenames[3]);
    // Assert the pre-selected items are shown.
    $assert_session->checkboxChecked("Select $existing_media_name", $this->getSelectionArea());
    // Set alt texts for items 1 and 2, leave the alt text empty for items 3
    // and 4 to assert the field validation does not stop users from removing
    // items.
    $page->fillField('media[0][fields][field_media_test_image][0][alt]', $filenames[0]);
    $page->fillField('media[1][fields][field_media_test_image][0][alt]', $filenames[1]);
    // Assert the file is available in the file storage.
    $files = $file_storage->loadByProperties(['filename' => $filenames[1]]);
    $this->assertCount(1, $files);
    $file_1_uri = reset($files)->getFileUri();
    // Remove the second file and assert the focus is shifted to the container
    // of the next media item and field values are still correct.
    $page->pressButton('media-1-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=2]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[1] . ' has been removed.');
    // Assert the file was deleted.
    $this->assertEmpty($file_storage->loadByProperties(['filename' => $filenames[1]]));
    $this->assertFileNotExists($file_1_uri);

    // When a file is already in usage, it should not be deleted. To test,
    // let's add a usage for $filenames[3] (now in the third position).
    $files = $file_storage->loadByProperties(['filename' => $filenames[3]]);
    $this->assertCount(1, $files);
    $target_file = reset($files);
    Media::create([
      'bundle' => 'type_three',
      'name' => 'Disturbing',
      'field_media_test_image' => [
        ['target_id' => $target_file->id()],
      ],
    ])->save();
    // Remove $filenames[3] (now in the third position) and assert the focus is
    // shifted to the container of the previous media item and field values are
    // still correct.
    $page->pressButton('media-3-remove-button');
    $this->assertTrue($assert_session->waitForText('The media item ' . $filenames[3] . ' has been removed.'));
    // Assert the file was not deleted, due to being in use elsewhere.
    $this->assertNotEmpty($file_storage->loadByProperties(['filename' => $filenames[3]]));
    $this->assertFileExists($target_file->getFileUri());

    // The second media item should be removed (this has the delta 1 since we
    // start counting from 0).
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
    $media_item_three = $assert_session->elementExists('css', '[data-media-library-added-delta=2]');
    $assert_session->fieldValueEquals('Name', $filenames[2], $media_item_three);
    $assert_session->fieldValueEquals('Alternative text', '', $media_item_three);
    // Assert the pre-selected items are still shown.
    $assert_session->checkboxChecked("Select $existing_media_name", $this->getSelectionArea());

    // Remove the last file and assert the focus is shifted to the container
    // of the first media item and field values are still correct.
    $page->pressButton('media-2-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=0]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[2] . ' has been removed.');
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=2]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
  }

  /**
   * Tests that oEmbed media can be added in the Media library's widget.
   */
  public function testWidgetOEmbed() {
    $this->hijackProviderEndpoints();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $youtube_title = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $youtube_url = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    $vimeo_title = "Drupal Rap Video - Schipulcon09";
    $vimeo_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($youtube_url, $this->getFixturesDirectory() . '/video_youtube.json');
    ResourceController::setResourceUrl($vimeo_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    ResourceController::setResource404('https://www.youtube.com/watch?v=PWjcqE3QKBg1');

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited media field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $this->switchToMediaType('Three');
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $youtube_title);
    $this->pressSaveButton();
    $this->waitForText('Add Type Five via URL');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($youtube_title);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the created oEmbed video is correctly added to the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($youtube_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    // Assert the video is available on the tab.
    $assert_session->pageTextContains($youtube_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('Could not retrieve the oEmbed resource.');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $checkbox = $page->findField("Select $youtube_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);

    // Assert we can add a oEmbed video with a custom name.
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Custom video title');
    $assert_session->elementNotExists('css', '.media-library-add-form__selected-media');
    $this->pressSaveButton();

    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select Custom video title");
    $assert_session->checkboxChecked("Select $youtube_title");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Custom video title');

    // Assert we can directly insert added oEmbed media in the widget.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $vimeo_url);
    $page->pressButton('Add');
    $this->waitForText('The media item has been created but has not yet been saved.');
    $this->pressSaveButton();
    $this->waitForText('Add or select media');
    $this->pressInsertSelected();
    $this->waitForText($vimeo_title);

    // Assert we can remove selected items from the selection area in the oEmbed
    // form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $checkbox = $page->findField("Select $vimeo_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved');
    $page->fillField('Name', 'Another video');
    $this->pressSaveButton();
    $page->uncheckField('media_library_select_form[1]');
    $this->waitForText('1 item selected');
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText('Another video');

    // Assert removing an added oEmbed media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $youtube_title has been removed.");
    $this->assertNoMediaAdded();
  }

  /**
   * Tests that oEmbed media can be added in the widget's advanced UI.
   *
   * @todo Merge this with testWidgetOEmbed() in
   *   https://www.drupal.org/project/drupal/issues/3087227
   */
  public function testWidgetOEmbedAdvancedUi() {
    $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();

    $this->hijackProviderEndpoints();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $youtube_title = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $youtube_url = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    $vimeo_title = "Drupal Rap Video - Schipulcon09";
    $vimeo_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($youtube_url, $this->getFixturesDirectory() . '/video_youtube.json');
    ResourceController::setResourceUrl($vimeo_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    ResourceController::setResource404('https://www.youtube.com/watch?v=PWjcqE3QKBg1');

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited media field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $this->switchToMediaType('Three');
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $youtube_title);
    $this->saveAnd('select');
    $this->waitForText('Add Type Five via URL');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($youtube_title);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the created oEmbed video is correctly added to the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($youtube_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    // Assert the video is available on the tab.
    $assert_session->pageTextContains($youtube_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('Could not retrieve the oEmbed resource.');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $checkbox = $page->findField("Select $youtube_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);

    // Assert we can add a oEmbed video with a custom name.
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Custom video title');
    $assert_session->checkboxChecked("Select $youtube_title", $this->getSelectionArea());
    $this->saveAnd('select');
    $this->waitForNoText('Save and select');

    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select Custom video title");
    $assert_session->checkboxChecked("Select $youtube_title");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Custom video title');

    // Assert we can directly insert added oEmbed media in the widget.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $vimeo_url);
    $page->pressButton('Add');
    $this->waitForText('The media item has been created but has not yet been saved.');

    $this->saveAnd('insert');
    $this->waitForText('Added one media item.');
    $this->waitForNoText('Add or select media');
    $this->waitForText($vimeo_title);

    // Assert we can remove selected items from the selection area in the oEmbed
    // form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $checkbox = $page->findField("Select $vimeo_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved');
    $page->fillField('Name', 'Another video');
    $selection_area = $this->getSelectionArea();
    $assert_session->checkboxChecked("Select $vimeo_title", $selection_area);
    $page->uncheckField("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $selection_area->find('css', 'summary')->click();
    $this->saveAnd('select');

    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $this->waitForText('1 item selected');
    $assert_session->checkboxChecked('Select Another video');
    $assert_session->checkboxNotChecked("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText('Another video');

    // Assert removing an added oEmbed media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $youtube_title has been removed.");
    $this->assertNoMediaAdded();
  }

  /**
   * Tests field UI integration for media library widget.
   */
  public function testFieldUiIntegration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalCreateContentType(['type' => 'article']);
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer node fields',
      'administer node form display',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/types/manage/article/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'field_ui:entity_reference:media');
    $this->assertNotNull($assert_session->waitForField('label'));
    $page->fillField('label', 'Shatner');
    $this->waitForText('field_shatner');
    $page->pressButton('Save and continue');
    $page->pressButton('Save field settings');
    $assert_session->pageTextNotContains('Undefined index: target_bundles');
    $this->waitForFieldExists('Type One')->check();
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_one]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_two]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_two]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_three]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_three]"][checked="checked"]');
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Shatner configuration.');
  }

  /**
   * Asserts that text does not appear on page after a wait.
   *
   * @param string $text
   *   The text that should not be on the page.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForNoText($text, $timeout = 10000) {
    $page = $this->getSession()->getPage();
    $result = $page->waitFor($timeout / 1000, function ($page) use ($text) {
      $actual = preg_replace('/\s+/u', ' ', $page->getText());
      $regex = '/' . preg_quote($text, '/') . '/ui';
      return (bool) !preg_match($regex, $actual);
    });
    $this->assertNotEmpty($result, "\"$text\" was found but shouldn't be there.");
  }

  /**
   * Asserts that text appears on page after a wait.
   *
   * @param string $text
   *   The text that should appear on the page.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForText($text, $timeout = 10000) {
    $result = $this->assertSession()->waitForText($text, $timeout);
    $this->assertNotEmpty($result, "\"$text\" not found");
  }

  /**
   * Asserts that text appears in an element after a wait.
   *
   * @param string $selector
   *   The CSS selector of the element to check.
   * @param string $text
   *   The text that should appear in the element.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForElementTextContains($selector, $text, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement('css', "$selector:contains('$text')", $timeout);
    $this->assertNotEmpty($element);
  }

  /**
   * Waits for the specified selector and returns it if not empty.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The page element node if found. If not found, the test fails.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function assertElementExistsAfterWait($selector, $locator, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement($selector, $locator, $timeout);
    $this->assertNotEmpty($element);
    return $element;
  }

  /**
   * Gets the menu of available media types.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The menu of available media types.
   */
  protected function getTypesMenu() {
    return $this->assertSession()
      ->elementExists('css', '.js-media-library-menu');
  }

  /**
   * Clicks a media type tab and waits for it to appear.
   */
  protected function switchToMediaType($type) {
    $link = $this->assertSession()
      ->elementExists('named', ['link', "Type $type"], $this->getTypesMenu());

    if ($link->hasClass('active')) {
      // There is nothing to do as the type is already active.
      return;
    }

    $link->click();
    $result = $link->waitFor(10, function ($link) {
      /** @var \Behat\Mink\Element\NodeElement $link */
      return $link->hasClass('active');
    });
    $this->assertNotEmpty($result);

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Checks for a specified number of specific elements on page after wait.
   *
   * @param string $selector_type
   *   Element selector type (css, xpath)
   * @param string|array $selector
   *   Element selector.
   * @param int $count
   *   Expected count.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForElementsCount($selector_type, $selector, $count, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    $start = microtime(TRUE);
    $end = $start + ($timeout / 1000);
    do {
      $nodes = $page->findAll($selector_type, $selector);
      if (count($nodes) === $count) {
        return;
      }
      usleep(100000);
    } while (microtime(TRUE) < $end);

    $this->assertSession()->elementsCount($selector_type, $selector, $count);
  }

  /**
   * Checks for the existence of a field on page after wait.
   *
   * @param string $field
   *   The field to find.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The element if found, otherwise null.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForFieldExists($field, $timeout = 10000) {
    $assert_session = $this->assertSession();
    $assert_session->waitForField($field, $timeout);
    return $assert_session->fieldExists($field);
  }

  /**
   * Waits for a file field to exist before uploading.
   */
  public function addMediaFileToField($locator, $path) {
    $page = $this->getSession()->getPage();
    $this->waitForFieldExists($locator);
    $page->attachFileToField($locator, $path);
  }

  /**
   * Clicks "Save and select||insert" button and waits for AJAX completion.
   *
   * @param string $operation
   *   The final word of the button to be clicked.
   */
  protected function saveAnd($operation) {
    $this->assertElementExistsAfterWait('css', '.ui-dialog-buttonpane')->pressButton("Save and $operation");

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks "Save" button and waits for AJAX completion.
   *
   * @param bool $expect_errors
   *   Whether validation errors are expected after the "Save" button is
   *   pressed. Defaults to FALSE.
   */
  protected function pressSaveButton($expect_errors = FALSE) {
    $buttons = $this->assertElementExistsAfterWait('css', '.ui-dialog-buttonpane');
    $buttons->pressButton('Save');

    // If no validation errors are expected, wait for the "Insert selected"
    // button to return.
    if (!$expect_errors) {
      $result = $buttons->waitFor(10, function ($buttons) {
        /** @var \Behat\Mink\Element\NodeElement $buttons */
        return $buttons->findButton('Insert selected');
      });
      $this->assertNotEmpty($result);
    }

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks a button that opens a media widget and confirms it is open.
   *
   * @param string $field_name
   *   The machine name of the field for which to open the media library.
   * @param string $after_open_selector
   *   The selector to look for after the button is clicked.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The NodeElement found via $after_open_selector.
   */
  protected function openMediaLibraryForField($field_name, $after_open_selector = '.js-media-library-menu') {
    $this->assertElementExistsAfterWait('css', "#$field_name-media-library-wrapper.js-media-library-widget")
      ->pressButton('Add media');
    $this->waitForText('Add or select media');

    // Assert that the grid display is visible and the links to toggle between
    // the grid and table displays are present.
    $this->assertMediaLibraryGrid();
    $assert_session = $this->assertSession();
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // The "select all" checkbox should never be present in the modal.
    $assert_session->elementNotExists('css', '.media-library-select-all');

    return $this->assertElementExistsAfterWait('css', $after_open_selector);
  }

  /**
   * Gets the "Additional selected media" area after adding new media.
   *
   * @param bool $open
   *   Whether or not to open the area before returning it. Defaults to TRUE.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The "additional selected media" area.
   */
  protected function getSelectionArea($open = TRUE) {
    $summary = $this->assertElementExistsAfterWait('css', 'summary:contains("Additional selected media")');
    if ($open) {
      $summary->click();
    }
    return $summary->getParent();
  }

  /**
   * Asserts a media item was added, but not yet saved.
   *
   * @param int $index
   *   (optional) The index of the media item, if multiple items can be added at
   *   once. Defaults to 0.
   */
  protected function assertMediaAdded($index = 0) {
    $selector = '.js-media-library-add-form-added-media';

    // Assert that focus is shifted to the new media items.
    $this->assertJsCondition('jQuery("' . $selector . '").is(":focus")');

    $assert_session = $this->assertSession();
    $assert_session->pageTextMatches('/The media items? ha(s|ve) been created but ha(s|ve) not yet been saved. Fill in any required fields and save to add (it|them) to the media library./');
    $assert_session->elementAttributeContains('css', $selector, 'aria-label', 'Added media items');

    $fields = $this->assertElementExistsAfterWait('css', '[data-drupal-selector="edit-media-' . $index . '-fields"]');
    $assert_session->elementNotExists('css', '.js-media-library-menu');

    // Assert extraneous components were removed in
    // FileUploadForm::hideExtraSourceFieldComponents().
    $assert_session->elementNotExists('css', '[data-drupal-selector$="preview"]', $fields);
    $assert_session->buttonNotExists('Remove', $fields);
    $assert_session->elementNotExists('css', '[data-drupal-selector$="filename"]', $fields);
    $assert_session->elementNotExists('css', '.file-size', $fields);
  }

  /**
   * Asserts that media was not added, i.e. due to a validation error.
   */
  protected function assertNoMediaAdded() {
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertJsCondition('jQuery("#media-library-add-form-wrapper :tabbable").is(":focus")');

    $this->assertSession()
      ->elementNotExists('css', '[data-drupal-selector="edit-media-0-fields"]');
    $this->getTypesMenu();
  }

  /**
   * Presses the modal's "Insert selected" button.
   *
   * @param string $expected_announcement
   *   (optional) The expected screen reader announcement once the modal is
   *   closed.
   *
   * @todo Consider requiring screen reader assertion every time "Insert
   *   selected" is pressed in
   *   https://www.drupal.org/project/drupal/issues/3087227.
   */
  protected function pressInsertSelected($expected_announcement = NULL) {
    $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->pressButton('Insert selected');
    $this->waitForNoText('Add or select media');

    if ($expected_announcement) {
      $this->waitForText($expected_announcement);
    }
  }

  /**
   * Gets all available media item checkboxes.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The available checkboxes.
   */
  protected function getCheckboxes() {
    return $this->getSession()
      ->getPage()
      ->findAll('css', '.js-media-library-view .js-click-to-select-checkbox input');
  }

  /**
   * Selects an item in the media library modal.
   *
   * @param int $index
   *   The zero-based index of the item to select.
   * @param string $expected_selected_count
   *   (optional) The expected text of the selection counter.
   */
  protected function selectMediaItem($index, $expected_selected_count = NULL) {
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThan($index, count($checkboxes));
    $checkboxes[$index]->check();

    if ($expected_selected_count) {
      $this->assertSelectedMediaCount($expected_selected_count);
    }
  }

  /**
   * Switches to the grid display of the widget view.
   */
  protected function switchToMediaLibraryGrid() {
    $this->getSession()->getPage()->clickLink('Grid');
    // Assert the display change is correctly announced for screen readers.
    $this->waitForText('Loading grid view.');
    $this->waitForText('Changed to grid view.');
    $this->assertMediaLibraryGrid();
  }

  /**
   * Switches to the table display of the widget view.
   */
  protected function switchToMediaLibraryTable() {
    $this->getSession()->getPage()->clickLink('Table');
    // Assert the display change is correctly announced for screen readers.
    $this->waitForText('Loading table view.');
    $this->waitForText('Changed to table view.');
    $this->assertMediaLibraryTable();
  }

  /**
   * Asserts that the grid display of the widget view is visible.
   */
  protected function assertMediaLibraryGrid() {
    $this->assertSession()
      ->elementExists('css', '.js-media-library-view[data-view-display-id="widget"]');
  }

  /**
   * Asserts that the table display of the widget view is visible.
   */
  protected function assertMediaLibraryTable() {
    $this->assertSession()
      ->elementExists('css', '.js-media-library-view[data-view-display-id="widget_table"]');
  }

  /**
   * Asserts the current text of the selected item counter.
   *
   * @param string $text
   *   The expected text of the counter.
   */
  protected function assertSelectedMediaCount($text) {
    $selected_count = $this->assertSession()
      ->elementExists('css', '.js-media-library-selected-count');

    $this->assertSame('status', $selected_count->getAttribute('role'));
    $this->assertSame('polite', $selected_count->getAttribute('aria-live'));
    $this->assertSame('true', $selected_count->getAttribute('aria-atomic'));
    $this->assertSame($text, $selected_count->getText());
  }

}
