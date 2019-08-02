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

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Verify that the media name does not contain a link.
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    // Verify that there are links to edit and delete media items.
    $assert_session->elementExists('css', '.media-library-item .media-library-item__edit');
    $assert_session->elementExists('css', '.media-library-item .media-library-item__remove');

    // Test that users can filter by type.
    $page->selectFieldOption('Media type', 'Type One');
    $page->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Turtle');
    $page->selectFieldOption('Media type', 'Type Two');
    $page->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Test that selecting elements as a part of bulk operations works.
    $page->selectFieldOption('Media type', '- Any -');
    $page->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select-trigger a")[4].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $this->submitForm([], 'Delete');
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');

    // Test 'Select all media'.
    $this->getSession()->getPage()->checkField('Select all media');
    $this->getSession()->getPage()->selectFieldOption('Action', 'media_delete_action');
    $this->submitForm([], 'Apply to selected items');
    $this->getSession()->getPage()->pressButton('Delete');

    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Test empty text.
    $assert_session->pageTextContains('No media available.');

    // Verify that the "Table" link is present, click it and check address.
    $assert_session->linkExists('Table');
    $page->clickLink('Table');
    $assert_session->addressEquals('admin/content/media-table');
    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');
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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array. No types are allowed in this
    // case.
    $assert_session->elementTextContains('css', '.field--name-field-empty-types-media', $default_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is not shown when the target_bundles setting for
    // the entity reference field is null. All types are allowed in this case.
    $assert_session->elementTextNotContains('css', '.field--name-field-null-types-media', 'There are no allowed media types configured for this field.');
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_null_types_media"]');

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
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementTextContains('css', '.field--name-field-empty-types-media', $default_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for
    // the entity reference field is null.
    $assert_session->elementTextContains('css', '.field--name-field-null-types-media', $default_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_null_types_media"]');

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
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_empty_types_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is null.
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_null_types_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_null_types_media"]');

    // Assert the messages are also shown in the default value section of the
    // field edit form.
    $this->drupalGet($field_empty_types_url);
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_empty_types_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_empty_types_media"]');
    $this->drupalGet($field_null_types_url);
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_null_types_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_null_types_media"]');

    // Uninstall the Field UI and check if the link is removed from the message.
    \Drupal::service('module_installer')->uninstall(['field_ui']);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $field_ui_uninstalled_message = 'There are no allowed media types configured for this field. Edit the field settings to select the allowed media types.';

    // Assert the link is now longer part of the message.
    $assert_session->elementNotExists('named', ['link', 'Edit the field settings']);
    // Assert a properly configured field still shows a message.
    $assert_session->elementContains('css', '.field--name-field-twin-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_twin_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is an empty array.
    $assert_session->elementContains('css', '.field--name-field-empty-types-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_empty_types_media"]');
    // Assert that the message is shown when the target_bundles setting for the
    // entity reference field is null.
    $assert_session->elementContains('css', '.field--name-field-null-types-media', $field_ui_uninstalled_message);
    $assert_session->elementNotExists('css', '.media-library-open-button[name^="field_null_types_media"]');
  }

  /**
   * Tests that the integration with Views works correctly.
   */
  public function testViewsAdmin() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Assert that the widget can be seen and that there are 8 items.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementsCount('css', '.media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.media-library-view .view-filters')->fillField('name', 'snake');
    $page->find('css', '.media-library-view .view-filters')->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementsCount('css', '.media-library-item', 1);

    // Test the same routine but in the view for the table wiget.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget_table');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementsCount('css', '.media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.media-library-view .view-filters')->fillField('name', 'snake');
    $page->find('css', '.media-library-view .view-filters')->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementsCount('css', '.media-library-item', 1);

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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $this->assertFalse($assert_session->elementExists('css', '.media-library-select-all')->isVisible());
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when more than 1 type is
    // configured for the field.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $menu = $assert_session->elementExists('css', '.media-library-menu');
    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertFalse($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertFalse($menu->hasLink('Type Four'));
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that there are no links in the media library view.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item__edit');
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item__remove');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that the media type menu is available when the target_bundles
    // setting for the entity reference field is null. All types should be
    // allowed in this case.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_null_types_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $menu = $assert_session->elementExists('css', '.media-library-menu');
    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertTrue($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertTrue($menu->hasLink('Type Four'));
    $this->assertTrue($menu->hasLink('Type Five'));
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is not available when only 1 type is
    // configured for the field.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_single_media_type"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 of 1 item selected');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertGreaterThanOrEqual(1, count($checkboxes));
    $checkboxes[0]->click();
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 1 item selected');
    $assert_session->elementNotExists('css', '.media-library-menu');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the menu links can be sorted through the widget configuration.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $links = $page->findAll('css', '.media-library-menu a');
    $link_titles = [];
    foreach ($links as $link) {
      $link_titles[] = $link->getText();
    }
    $expected_link_titles = ['Show Type Three media (selected)', 'Show Type One media', 'Show Type Two media', 'Show Type Four media'];
    $this->assertSame($link_titles, $expected_link_titles);
    $this->drupalGet('admin/structure/types/manage/basic_page/form-display');
    $assert_session->buttonExists('field_twin_media_settings_edit')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->buttonExists('Show row weights')->press();
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_one][weight]')->selectOption(0);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_three][weight]')->selectOption(1);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_four][weight]')->selectOption(2);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_two][weight]')->selectOption(3);
    $assert_session->buttonExists('Save')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->buttonExists('Hide row weights')->press();
    $this->drupalGet('node/add/basic_page');
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $link_titles = array_map(function ($link) {
      return $link->getText();
    }, $page->findAll('css', '.media-library-menu a'));
    $this->assertSame($link_titles, ['Show Type One media (selected)', 'Show Type Three media', 'Show Type Four media', 'Show Type Two media']);
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the announcements for media type navigation in the media library.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForText('Showing Type Three media.'));
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForText('Showing Type One media.'));
    // Assert the links can be triggered by via the spacebar.
    $assert_session->elementExists('named', ['link', 'Type Three'])->keyPress(32);
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForText('Showing Type Three media.'));
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert media is only visible on the tab for the related media type.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForText('Showing Type Three media.'));
    $assert_session->elementExists('named', ['link', 'Show Type Three media (selected)']);
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the exposed name filter of the view.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $session = $this->getSession();
    $session->getPage()->fillField('Name', 'Dog');
    $session->getPage()->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $session->getPage()->fillField('Name', '');
    $session->getPage()->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert adding a single media item and removing it.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertGreaterThanOrEqual(1, count($checkboxes));
    $checkboxes[0]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');
    // Assert the weight field can be focused via a mouse click.
    $assert_session->elementExists('named', ['button', 'Show media item weights'])->click();
    $assert_session->elementExists('css', '#field_twin_media-media-library-wrapper .media-library-item__weight')->click();
    $assert_session->elementExists('css', '#field_twin_media-media-library-wrapper .js-media-library-widget-toggle-weight')->click();
    // Remove the selected item.
    $assert_session->elementAttributeContains('css', '.media-library-item__remove', 'aria-label', 'Remove Dog');
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $this->assertNotEmpty($assert_session->waitForText('Removed Dog.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');

    // Assert we can select the same media item twice.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('Select Dog');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('Select Dog');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the same has been added twice and remove the items again.
    $this->assertCount(2, $page->findAll('css', '.field--name-field-twin-media .media-library-item'));
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][0][target_id]', 4);
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][1][target_id]', 4);
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $this->assertNotEmpty($assert_session->waitForText('Removed Dog.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $this->assertNotEmpty($assert_session->waitForText('Removed Dog.'));
    $assert_session->assertWaitOnAjaxRequest();

    // Assert the selection is persistent in the media library modal, and
    // the number of selected items is displayed correctly.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the number of selected items is displayed correctly.
    $assert_session->elementExists('css', '.media-library-selected-count');
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 of 2 items selected');
    $assert_session->elementAttributeContains('css', '.media-library-selected-count', 'role', 'status');
    $assert_session->elementAttributeContains('css', '.media-library-selected-count', 'aria-live', 'polite');
    $assert_session->elementAttributeContains('css', '.media-library-selected-count', 'aria-atomic', 'true');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertCount(4, $checkboxes);
    $checkboxes[0]->click();
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    // Assert the number of selected items is displayed correctly.
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    // Select another item and assert the number of selected items is updated.
    $checkboxes[1]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4,3');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the selected items are updated when deselecting an item.
    $checkboxes[0]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3');
    // Assert deselected items are available again.
    $this->assertFalse($checkboxes[2]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[3]->hasAttribute('disabled'));
    // The selection should be persisted when navigating to other media types in
    // the modal.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $selected_checkboxes = [];
    foreach ($checkboxes as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getValue();
      }
    }
    $this->assertCount(1, $selected_checkboxes);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', implode(',', $selected_checkboxes));
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    // Add to selection from another type.
    $page->clickLink('Type Two');
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertCount(4, $checkboxes);
    $checkboxes[0]->click();
    // Assert the selection is updated correctly.
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3,8');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertFalse($checkboxes[0]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the checkboxes are also disabled on other pages.
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($checkboxes[0]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Select the items.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added 2 media items.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the open button is disabled.
    $open_button = $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]');
    $this->assertTrue($open_button->hasAttribute('data-disabled-focus'));
    $this->assertTrue($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .media-library-open-button").is(":disabled")');

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');

    // Remove "Cat" (happens to be the first remove button on the page).
    $assert_session->elementAttributeContains('css', '.media-library-item__remove', 'aria-label', 'Remove Cat');
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $this->assertNotEmpty($assert_session->waitForText('Removed Cat.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the focus is set to the wrapper of the other selected item.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .media-library-item").is(":focus")');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    // Assert the open button is no longer disabled.
    $open_button = $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]');
    $this->assertFalse($open_button->hasAttribute('data-disabled-focus'));
    $this->assertFalse($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .media-library-open-button").is(":not(:disabled)")');

    // Open the media library again and select another item.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertGreaterThanOrEqual(1, count($checkboxes));
    $checkboxes[0]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');
    // Assert the open button is disabled.
    $this->assertTrue($assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->hasAttribute('data-disabled-focus'));
    $this->assertTrue($assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .media-library-open-button").is(":disabled")');

    // Assert the selection is cleared when the modal is closed.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    // Nothing is selected yet.
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 items selected');
    // Select the first 2 items.
    $checkboxes[0]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 item selected');
    $checkboxes[1]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 items selected');
    $this->assertTrue($checkboxes[0]->isChecked());
    $this->assertTrue($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    // Close the dialog, reopen it and assert not is selected again.
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    // Select all media items of type one (should also contain Dog, again).
    $checkbox_selector = '.media-library-view .js-click-to-select-checkbox input';
    $checkboxes = $page->findAll('css', $checkbox_selector);
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    $checkboxes[0]->click();
    $checkboxes[1]->click();
    $checkboxes[2]->click();
    $checkboxes[3]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added 4 media items.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
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

    // Assert the media library contains header links to switch between the grid
    // and table display.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-view .media-library-item--grid');
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--table');
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // Assert the 'Apply filter' button is not moved to the button pane.
    $button_pane = $assert_session->elementExists('css', '.ui-dialog-buttonpane');
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);

    // Assert the pager works as expected.
    $assert_session->elementTextContains('css', '.media-library-view .pager__item.is-active', 'Page 1');
    $assert_session->elementsCount('css', '.media-library-view .js-click-to-select-checkbox input', 24);
    $page->clickLink('Next page');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementTextContains('css', '.media-library-view .pager__item.is-active', 'Page 2');
    $assert_session->elementsCount('css', '.media-library-view .js-click-to-select-checkbox input', 1);
    $page->clickLink('Previous page');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementTextContains('css', '.media-library-view .pager__item.is-active', 'Page 1');
    $assert_session->elementsCount('css', '.media-library-view .js-click-to-select-checkbox input', 24);

    // Assert the display change is correctly announced for screen readers.
    $page->clickLink('Table');
    $this->assertNotEmpty($assert_session->waitForText('Loading table view.'));
    $this->assertNotEmpty($assert_session->waitForText('Changed to table view.'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.media-library-view .media-library-item--table'));
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--grid');

    // Assert the 'Apply filter' button is not moved to the button pane.
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');

    // Assert the exposed filters can be applied.
    $page->fillField('Name', 'Dog');
    $page->pressButton('Apply filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->checkField('Select Dog');
    $assert_session->linkExists('Table');
    $page->clickLink('Grid');
    // Assert the display change is correctly announced for screen readers.
    $this->assertNotEmpty($assert_session->waitForText('Loading grid view.'));
    $this->assertNotEmpty($assert_session->waitForText('Changed to grid view.'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.media-library-view .media-library-item--grid'));
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--table');

    // Assert the exposed filters are persisted when changing display.
    $this->assertSame('Dog', $page->findField('Name')->getValue());
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // Select the item.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
  }

  /**
   * Tests that the widget works as expected for anonymous users.
   */
  public function testWidgetAnonymous() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Select the first media item (should be Dog).
    $page->find('css', '.media-library-view .js-click-to-select-checkbox input')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Dog');

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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the upload form is not visible for default tab type_three without
    // the proper permissions.
    $assert_session->elementNotExists('css', '.media-library-add-form');

    // Assert the upload form is not visible for the non-file based media type
    // type_one.
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '.media-library-add-form');

    // Assert the upload form is visible for type_four.
    $page->clickLink('Type Four');
    $assert_session->assertWaitOnAjaxRequest();
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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the upload form is now visible for default tab type_three.
    $assert_session->elementExists('css', '.media-library-add-form');
    $assert_session->fieldExists('Add files');

    // Assert we can upload a file to the default tab type_three.
    $assert_session->elementExists('css', '.media-library-add-form--without-input');
    $assert_session->elementNotExists('css', '.media-library-add-form--with-input');
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertJsCondition('jQuery(".media-library-add-form__added-media").is(":focus")');
    $assert_session->pageTextContains('The media item has been created but has not yet been saved. Fill in any required fields and save to add it to the media library.');
    $assert_session->elementAttributeContains('css', '.media-library-add-form__added-media', 'aria-label', 'Added media items');
    $assert_session->elementExists('css', '.media-library-add-form--with-input');
    $assert_session->elementNotExists('css', '.media-library-add-form--without-input');
    // We do not have a pre-selected items, so the container should not be added
    // to the form.
    $assert_session->elementNotExists('css', '.media-library-add-form__selected-media');
    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());
    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);
    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
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
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);

    // Remove the item.
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains($png_image->filename);

    // Assert we can also directly insert uploaded files in the widget.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $png_uri_2 = $file_system->copy($png_image->uri, 'public://');
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_2));
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and insert');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($file_system->basename($png_uri_2));

    // Also make sure that we can upload to the unlimited cardinality field.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $existing_media_name = $file_system->basename($png_uri_2);
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $png_uri_3 = $file_system->copy($png_image->uri, 'public://');
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_3));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->checkboxChecked("Select $existing_media_name");
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
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
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $selected_checkboxes = [];
    foreach ($checkboxes as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added 2 media items.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Unlimited Cardinality Image');

    // Assert we can now only upload one more media item.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Four');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $png_uri_4 = $file_system->copy($png_image->uri, 'public://');
    $page->attachFileToField('Add file', $file_system->realpath($png_uri_4));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Only files with the following extensions are allowed');
    // Assert that jpg files are accepted by type four.
    $jpg_uri_2 = $file_system->copy($jpg_image->uri, 'public://');
    $page->attachFileToField('Add file', $file_system->realpath($jpg_uri_2));
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $jpg_uri_3 = $file_system->copy($jpg_image->uri, 'public://');
    $page->attachFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_uri_3));
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure the media item was saved to the library and automatically
    // selected.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));
    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));

    // Assert we can also remove selected items from the selection area in the
    // upload form.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    $png_uri_5 = $file_system->copy($png_image->uri, 'public://');
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_5));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the pre-selected items are shown.
    $selection_area = $assert_session->elementExists('css', '.media-library-add-form__selected-media');
    $assert_session->elementExists('css', 'summary', $selection_area)->click();
    $assert_session->checkboxChecked("Select $existing_media_name", $selection_area);
    $page->uncheckField("Select $existing_media_name");
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $this->click('details.media-library-add-form__selected-media summary');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxNotChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($file_system->basename($png_uri_5));

    // Assert removing an uploaded media item before save works as expected.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the focus is shifted to the added media items.
    $this->assertJsCondition('jQuery(".media-library-add-form__added-media").is(":focus")');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $assert_session->elementExists('css', '.media-library-add-form__fields');
    $assert_session->elementNotExists('css', '.media-library-menu');
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $assert_session->elementExists('css', '.media-library-add-form__remove-button')->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the remove message is shown.
    $assert_session->pageTextContains("The media item $png_image->filename has been removed.");
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertJsCondition('jQuery("#media-library-add-form-wrapper :tabbable").is(":focus")');
    $assert_session->elementNotExists('css', '.media-library-add-form__fields');
    $assert_session->elementExists('css', '.media-library-menu');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert uploading multiple files.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the existing items are remembered when adding and removing media.
    $checkbox = $page->findField("Select $existing_media_name");
    $checkbox->click();
    // Assert we can add multiple files.
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 3) as $i) {
      $path = $file_system->copy($png_image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $assert_session->elementExists('css', '.media-library-add-form__fields');
    $assert_session->elementNotExists('css', '.media-library-menu');
    // Assert all files have been added.
    $assert_session->fieldValueEquals('media[0][fields][name][0][value]', $filenames[0]);
    $assert_session->fieldValueEquals('media[1][fields][name][0][value]', $filenames[1]);
    $assert_session->fieldValueEquals('media[2][fields][name][0][value]', $filenames[2]);
    // Assert the pre-selected items are shown.
    $selection_area = $assert_session->elementExists('css', '.media-library-add-form__selected-media');
    $assert_session->elementExists('css', 'summary', $selection_area)->click();
    $assert_session->checkboxChecked("Select $existing_media_name", $selection_area);
    // Set alt texts for items 1 and 2, leave the alt text empty for item 3 to
    // assert the field validation does not stop users from removing items.
    $page->fillField('media[0][fields][field_media_test_image][0][alt]', $filenames[0]);
    $page->fillField('media[1][fields][field_media_test_image][0][alt]', $filenames[1]);
    // Remove the second file and assert the focus is shifted to the container
    // of the next media item and field values are still correct.
    $page->pressButton('media-1-remove-button');
    $this->assertJsCondition('jQuery(".media-library-add-form__media[data-media-library-added-delta=2]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[1] . ' has been removed.');
    // The second media item should be removed (this has the delta 1 since we
    // start counting from 0).
    $assert_session->elementNotExists('css', '.media-library-add-form__media[data-media-library-added-delta=1]');
    $media_item_one = $assert_session->elementExists('css', '.media-library-add-form__media[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
    $media_item_three = $assert_session->elementExists('css', '.media-library-add-form__media[data-media-library-added-delta=2]');
    $assert_session->fieldValueEquals('Name', $filenames[2], $media_item_three);
    $assert_session->fieldValueEquals('Alternative text', '', $media_item_three);
    // Assert the pre-selected items are still shown.
    $selection_area = $assert_session->elementExists('css', '.media-library-add-form__selected-media');
    $assert_session->elementExists('css', 'summary', $selection_area)->click();
    $assert_session->checkboxChecked("Select $existing_media_name", $selection_area);
    // Remove the last file and assert the focus is shifted to the container
    // of the first media item and field values are still correct.
    $page->pressButton('media-2-remove-button');
    $this->assertJsCondition('jQuery(".media-library-add-form__media[data-media-library-added-delta=0]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[2] . ' has been removed.');
    $assert_session->elementNotExists('css', '.media-library-add-form__media[data-media-library-added-delta=1]');
    $assert_session->elementNotExists('css', '.media-library-add-form__media[data-media-library-added-delta=2]');
    $media_item_one = $assert_session->elementExists('css', '.media-library-add-form__media[data-media-library-added-delta=0]');
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
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Add Type Five via URL', $youtube_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $youtube_title);
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();

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
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($youtube_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert the video is available on the tab.
    $assert_session->pageTextContains($youtube_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Could not retrieve the oEmbed resource.');

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
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Name', 'Custom video title');
    $selection_area = $assert_session->elementExists('css', '.media-library-add-form__selected-media');
    $assert_session->checkboxChecked("Select $youtube_title", $selection_area);
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();

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
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $selected_checkboxes = [];
    foreach ($checkboxes as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added 2 media items.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');

    // Assert we can directly insert added oEmbed media in the widget.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Add Type Five via URL', $vimeo_url);
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and insert');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($vimeo_title);

    // Assert we can remove selected items from the selection area in the oEmbed
    // form.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();
    $checkbox = $page->findField("Select $vimeo_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Name', 'Another video');
    $selection_area = $assert_session->elementExists('css', '.media-library-add-form__selected-media');
    $assert_session->elementExists('css', 'summary', $selection_area)->click();
    $assert_session->checkboxChecked("Select $vimeo_title", $selection_area);
    $page->uncheckField("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $this->click('details.media-library-add-form__selected-media summary');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and select');
    $assert_session->assertWaitOnAjaxRequest();
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked('Select Another video');
    $assert_session->checkboxNotChecked("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForText('Added one media item.'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Another video');

    // Assert removing an added oEmbed media item before save works as expected.
    $assert_session->elementExists('css', '.media-library-open-button[name^="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the focus is shifted to the added media items.
    $this->assertJsCondition('jQuery(".media-library-add-form__added-media").is(":focus")');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $assert_session->elementExists('css', '.media-library-add-form__fields');
    $assert_session->elementNotExists('css', '.media-library-menu');
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $assert_session->elementExists('css', '.media-library-add-form__remove-button')->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the remove message is shown.
    $assert_session->pageTextContains("The media item $youtube_title has been removed.");
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertJsCondition('jQuery("#media-library-add-form-wrapper :tabbable").is(":focus")');
    $assert_session->elementNotExists('css', '.media-library-add-form__fields');
    $assert_session->elementExists('css', '.media-library-menu');
  }

}
