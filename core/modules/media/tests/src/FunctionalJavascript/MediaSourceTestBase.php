<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\MediaType;

/**
 * Base class for media source tests.
 */
abstract class MediaSourceTestBase extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Let's set the canonical flag in the base class of the source tests,
    // because every source test has to check the output on the view page.
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Creates storage and field instance, attached to a given media type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $field_type
   *   The field type.
   * @param string $media_type_id
   *   The media type config entity ID.
   */
  protected function createMediaTypeField($field_name, $field_type, $media_type_id) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'media',
      'type' => $field_type,
    ]);
    $storage->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => $media_type_id,
    ])->save();

    // Make the field widget visible in the form display.
    $component = \Drupal::service('plugin.manager.field.widget')
      ->prepareConfiguration($field_type, []);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $entity_form_display = $display_repository->getFormDisplay('media', $media_type_id, 'default');
    $entity_form_display->setComponent($field_name, $component)
      ->save();

    // Use the default formatter and settings.
    $component = \Drupal::service('plugin.manager.field.formatter')
      ->prepareConfiguration($field_type, []);

    $entity_display = $display_repository->getViewDisplay('media', $media_type_id);
    $entity_display->setComponent($field_name, $component)
      ->save();
  }

  /**
   * Create a set of fields in a media type.
   *
   * @param array $fields
   *   An associative array where keys are field names and values field types.
   * @param string $media_type_id
   *   The media type config entity ID.
   */
  protected function createMediaTypeFields(array $fields, $media_type_id) {
    foreach ($fields as $field_name => $field_type) {
      $this->createMediaTypeField($field_name, $field_type, $media_type_id);
    }
  }

  /**
   * Hides a widget in the default form display config.
   *
   * @param string $field_name
   *   The field name.
   * @param string $media_type_id
   *   The media type config entity ID.
   */
  protected function hideMediaTypeFieldWidget($field_name, $media_type_id) {

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = $display_repository->getFormDisplay('media', $media_type_id, 'default');
    if ($entity_form_display->getComponent($field_name)) {
      $entity_form_display->removeComponent($field_name)->save();
    }
  }

  /**
   * Test generic media type creation.
   *
   * @param string $media_type_id
   *   The media type config entity ID.
   * @param string $source_id
   *   The media source ID.
   * @param array $provided_fields
   *   (optional) An array of field machine names this type provides.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   The created media type.
   */
  public function doTestCreateMediaType($media_type_id, $source_id, array $provided_fields = []) {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $media_type_id);
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$media_type_id}'");

    // Make sure the source is available.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', $source_id);
    $page->selectFieldOption('Media source', $source_id);
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    // Make sure the provided fields are visible on the form.
    foreach ($provided_fields as $provided_field) {
      $result = $assert_session->waitForElementVisible('css', 'select[name="field_map[' . $provided_field . ']"]');
      $this->assertNotEmpty($result);
    }

    // Save the form to create the type.
    $page->pressButton('Save');
    $assert_session->pageTextContains('The media type ' . $media_type_id . ' has been added.');
    $this->drupalGet('admin/structure/media');
    $assert_session->pageTextContains($media_type_id);

    // Bundle definitions are statically cached in the context of the test, we
    // need to make sure we have updated information before proceeding with the
    // actions on the UI.
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    return MediaType::load($media_type_id);
  }

}
