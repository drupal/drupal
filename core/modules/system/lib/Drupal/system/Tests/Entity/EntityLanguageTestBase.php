<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityLanguageTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Language\Language;
use Drupal\field\Field as FieldService;

/**
 * Base class for language-aware entity tests.
 */
abstract class EntityLanguageTestBase extends EntityUnitTestBase {

  protected $langcodes;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The untranslatable test field name.
   *
   * @var string
   */
  protected $untranslatable_field_name;

  public static $modules = array('language', 'entity_test');

  function setUp() {
    parent::setUp();

    $this->installSchema('system', 'variable');
    $this->installSchema('entity_test', array(
      'entity_test_mul',
      'entity_test_mul_property_data',
      'entity_test_rev',
      'entity_test_rev_revision',
      'entity_test_mulrev',
      'entity_test_mulrev_revision',
      'entity_test_mulrev_property_data',
      'entity_test_mulrev_property_revision',
    ));
    $this->installConfig(array('language'));

    // Create the test field.
    entity_test_install();

    // Enable translations for the test entity type.
    $this->state->set('entity_test.translation', TRUE);

    // Create a translatable test field.
    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');

    // Create an untranslatable test field.
    $this->untranslatable_field_name = drupal_strtolower($this->randomName() . '_field_name');

    // Create field instances in all entity variations.
    foreach (entity_test_entity_types() as $entity_type) {
      entity_create('field_entity', array(
        'name' => $this->field_name,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
        'translatable' => TRUE,
      ))->save();
      entity_create('field_instance', array(
        'field_name' => $this->field_name,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
      ))->save();
      $this->instance[$entity_type] = field_read_instance($entity_type, $this->field_name, $entity_type);

      entity_create('field_entity', array(
        'name' => $this->untranslatable_field_name,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
        'translatable' => FALSE,
      ))->save();
      entity_create('field_instance', array(
        'field_name' => $this->untranslatable_field_name,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
      ))->save();
    }

    // Create the default languages.
    $default_language = language_save(language_default());
    $languages = language_default_locked_languages($default_language->weight);
    foreach ($languages as $language) {
      language_save($language);
    }

    // Create test languages.
    $this->langcodes = array();
    for ($i = 0; $i < 3; ++$i) {
      $language = new Language(array(
        'id' => 'l' . $i,
        'name' => $this->randomString(),
        'weight' => $i,
      ));
      $this->langcodes[$i] = $language->id;
      language_save($language);
    }
  }

  /**
   * Toggles field translatability.
   *
   * @param string $entity_type
   *   The type of the entity fields are attached to.
   */
  protected function toggleFieldTranslatability($entity_type) {
    $fields = array($this->field_name, $this->untranslatable_field_name);
    foreach ($fields as $field_name) {
      $field = FieldService::fieldInfo()->getField($entity_type, $field_name);
      $translatable = !$field->isFieldTranslatable();
      $field->set('translatable', $translatable);
      $field->save();
      FieldService::fieldInfo()->flush();
      $field = FieldService::fieldInfo()->getField($entity_type, $field_name);
      $this->assertEqual($field->isFieldTranslatable(), $translatable, 'Field translatability changed.');
    }
    \Drupal::cache('field')->deleteAll();
  }

}
