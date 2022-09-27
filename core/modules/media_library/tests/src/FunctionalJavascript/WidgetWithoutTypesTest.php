<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Tests the media library widget when no media types are available.
 *
 * @group media_library
 */
class WidgetWithoutTypesTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

    $field_ui_uninstalled_message = 'There are no allowed media types configured for this field. Please contact the site administrator.';

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

}
