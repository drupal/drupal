<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationSyncImageTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\Core\Entity\EntityInterface;

/**
 * Tests the field synchronization behavior for the image field.
 *
 * @group content_translation
 */
class ContentTranslationSyncImageTest extends ContentTranslationTestBase {

  /**
   * The cardinality of the image field.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * The test image files.
   *
   * @var array
   */
  protected $files;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'entity_test', 'image', 'field_ui');

  protected function setUp() {
    parent::setUp();
    $this->files = $this->drupalGetTestFiles('image');
  }

  /**
   * Creates the test image field.
   */
  protected function setupTestFields() {
    $this->fieldName = 'field_test_et_ui_image';
    $this->cardinality = 3;

    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'image',
      'cardinality' => $this->cardinality,
    ))->save();

    entity_create('field_config', array(
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->entityTypeId,
      'label' => 'Test translatable image field',
      'third_party_settings' => array(
        'content_translation' => array(
          'translation_sync' => array(
            'file' => FALSE,
            'alt' => 'alt',
            'title' => 'title',
          ),
        ),
      ),
    ))->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    // Every entity-type-specific test needs to define these.
    return array('administer entity_test_mul fields', 'administer languages', 'administer content translation');
  }

  /**
   * Tests image field field synchronization.
   */
  function testImageFieldSync() {
    // Check that the alt and title fields are enabled for the image field.
    $this->drupalLogin($this->editor);
    $this->drupalGet('entity_test_mul/structure/' . $this->entityTypeId . '/fields/' . $this->entityTypeId . '.' . $this->entityTypeId . '.' . $this->fieldName);
    $this->assertFieldChecked('edit-third-party-settings-content-translation-translation-sync-alt');
    $this->assertFieldChecked('edit-third-party-settings-content-translation-translation-sync-title');
    $edit = array(
      'third_party_settings[content_translation][translation_sync][alt]' => FALSE,
      'third_party_settings[content_translation][translation_sync][title]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    // Check that the content translation settings page reflects the changes
    // performed in the field edit page.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertNoFieldChecked('edit-settings-entity-test-mul-entity-test-mul-columns-field-test-et-ui-image-alt');
    $this->assertNoFieldChecked('edit-settings-entity-test-mul-entity-test-mul-columns-field-test-et-ui-image-title');
    $edit = array(
      'settings[entity_test_mul][entity_test_mul][fields][field_test_et_ui_image]' => TRUE,
      'settings[entity_test_mul][entity_test_mul][columns][field_test_et_ui_image][alt]' => TRUE,
      'settings[entity_test_mul][entity_test_mul][columns][field_test_et_ui_image][title]' => TRUE,
    );
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
    $errors = $this->xpath('//div[contains(@class, "messages--error")]');
    $this->assertFalse($errors, 'Settings correctly stored.');
    $this->assertFieldChecked('edit-settings-entity-test-mul-entity-test-mul-columns-field-test-et-ui-image-alt');
    $this->assertFieldChecked('edit-settings-entity-test-mul-entity-test-mul-columns-field-test-et-ui-image-title');
    $this->drupalLogin($this->translator);

    $default_langcode = $this->langcodes[0];
    $langcode = $this->langcodes[1];

    // Populate the test entity with some random initial values.
    $values = array(
      'name' => $this->randomMachineName(),
      'user_id' => mt_rand(1, 128),
      'langcode' => $default_langcode,
    );
    $entity = entity_create($this->entityTypeId, $values);

    // Create some file entities from the generated test files and store them.
    $values = array();
    for ($delta = 0; $delta < $this->cardinality; $delta++) {
      // For the default language use the same order for files and field items.
      $index = $delta;

      // Create the file entity for the image being processed and record its
      // identifier.
      $field_values = array(
        'uri' => $this->files[$index]->uri,
        'uid' => \Drupal::currentUser()->id(),
        'status' => FILE_STATUS_PERMANENT,
      );
      $file = entity_create('file', $field_values);
      $file->save();
      $fid = $file->id();
      $this->files[$index]->fid = $fid;

      // Generate the item for the current image file entity and attach it to
      // the entity.
      $item = array(
        'target_id' => $fid,
        'alt' => $default_langcode . '_' . $fid . '_' . $this->randomMachineName(),
        'title' => $default_langcode . '_' . $fid . '_' . $this->randomMachineName(),
      );
      $entity->{$this->fieldName}[] = $item;

      // Store the generated values keying them by fid for easier lookup.
      $values[$default_langcode][$fid] = $item;
    }
    $entity = $this->saveEntity($entity);

    // Create some field translations for the test image field. The translated
    // items will be one less than the original values to check that only the
    // translated ones will be preserved. In fact we want the same fids and
    // items order for both languages.
    $translation = $entity->addTranslation($langcode);
    for ($delta = 0; $delta < $this->cardinality - 1; $delta++) {
      // Simulate a field reordering: items are shifted of one position ahead.
      // The modulo operator ensures we start from the beginning after reaching
      // the maximum allowed delta.
      $index = ($delta + 1) % $this->cardinality;

      // Generate the item for the current image file entity and attach it to
      // the entity.
      $fid = $this->files[$index]->fid;
      $item = array(
        'target_id' => $fid,
        'alt' => $langcode . '_' . $fid . '_' . $this->randomMachineName(),
        'title' => $langcode . '_' . $fid . '_' . $this->randomMachineName(),
      );
      $translation->{$this->fieldName}[] = $item;

      // Again store the generated values keying them by fid for easier lookup.
      $values[$langcode][$fid] = $item;
    }

    // Perform synchronization: the translation language is used as source,
    // while the default language is used as target.
    $this->manager->getTranslationMetadata($translation)->setSource($default_langcode);
    $entity = $this->saveEntity($translation);
    $translation = $entity->getTranslation($langcode);

    // Check that one value has been dropped from the original values.
    $assert = count($entity->{$this->fieldName}) == 2;
    $this->assertTrue($assert, 'One item correctly removed from the synchronized field values.');

    // Check that fids have been synchronized and translatable column values
    // have been retained.
    $fids = array();
    foreach ($entity->{$this->fieldName} as $delta => $item) {
      $value = $values[$default_langcode][$item->target_id];
      $source_item = $translation->{$this->fieldName}->get($delta);
      $assert = $item->target_id == $source_item->target_id && $item->alt == $value['alt'] && $item->title == $value['title'];
      $this->assertTrue($assert, format_string('Field item @fid has been successfully synchronized.', array('@fid' => $item->target_id)));
      $fids[$item->target_id] = TRUE;
    }

    // Check that the dropped value is the right one.
    $removed_fid = $this->files[0]->fid;
    $this->assertTrue(!isset($fids[$removed_fid]), format_string('Field item @fid has been correctly removed.', array('@fid' => $removed_fid)));

    // Add back an item for the dropped value and perform synchronization again.
    $values[$langcode][$removed_fid] = array(
      'target_id' => $removed_fid,
      'alt' => $langcode . '_' . $removed_fid . '_' . $this->randomMachineName(),
      'title' => $langcode . '_' . $removed_fid . '_' . $this->randomMachineName(),
    );
    $translation->{$this->fieldName}->setValue(array_values($values[$langcode]));
    $entity = $this->saveEntity($translation);
    $translation = $entity->getTranslation($langcode);

    // Check that the value has been added to the default language.
    $assert = count($entity->{$this->fieldName}->getValue()) == 3;
    $this->assertTrue($assert, 'One item correctly added to the synchronized field values.');

    foreach ($entity->{$this->fieldName} as $delta => $item) {
      // When adding an item its value is copied over all the target languages,
      // thus in this case the source language needs to be used to check the
      // values instead of the target one.
      $fid_langcode = $item->target_id != $removed_fid ? $default_langcode : $langcode;
      $value = $values[$fid_langcode][$item->target_id];
      $source_item = $translation->{$this->fieldName}->get($delta);
      $assert = $item->target_id == $source_item->target_id && $item->alt == $value['alt'] && $item->title == $value['title'];
      $this->assertTrue($assert, format_string('Field item @fid has been successfully synchronized.', array('@fid' => $item->target_id)));
    }
  }

  /**
   * Saves the passed entity and reloads it, enabling compatibility mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be saved.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The saved entity.
   */
  protected function saveEntity(EntityInterface $entity) {
    $entity->save();
    $entity = entity_test_mul_load($entity->id(), TRUE);
    return $entity;
  }

}
