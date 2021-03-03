<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for language-aware entity tests.
 */
abstract class EntityLanguageTestBase extends EntityKernelTestBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The available language codes.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The untranslatable test field name.
   *
   * @var string
   */
  protected $untranslatableFieldName;

  protected static $modules = ['language', 'entity_test'];

  protected function setUp() {
    parent::setUp();

    $this->languageManager = $this->container->get('language_manager');

    foreach (entity_test_entity_types() as $entity_type_id) {
      // The entity_test schema is installed by the parent.
      if ($entity_type_id != 'entity_test') {
        $this->installEntitySchema($entity_type_id);
      }
    }

    $this->installConfig(['language']);

    // Create the test field.
    module_load_install('entity_test');
    entity_test_install();

    // Enable translations for the test entity type.
    $this->state->set('entity_test.translation', TRUE);

    // Create a translatable test field.
    $this->fieldName = mb_strtolower($this->randomMachineName() . '_field_name');

    // Create an untranslatable test field.
    $this->untranslatableFieldName = mb_strtolower($this->randomMachineName() . '_field_name');

    // Create field fields in all entity variations.
    foreach (entity_test_entity_types() as $entity_type) {
      FieldStorageConfig::create([
        'field_name' => $this->fieldName,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
      ])->save();
      FieldConfig::create([
        'field_name' => $this->fieldName,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
        'translatable' => TRUE,
      ])->save();

      FieldStorageConfig::create([
        'field_name' => $this->untranslatableFieldName,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
      ])->save();
      FieldConfig::create([
        'field_name' => $this->untranslatableFieldName,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
        'translatable' => FALSE,
      ])->save();
    }

    // Create the default languages.
    $this->installConfig(['language']);

    // Create test languages.
    $this->langcodes = [];
    for ($i = 0; $i < 3; ++$i) {
      $language = ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => $this->randomString(),
        'weight' => $i,
      ]);
      $this->langcodes[$i] = $language->getId();
      $language->save();
    }
  }

  /**
   * Toggles field storage translatability.
   *
   * @param string $entity_type
   *   The type of the entity fields are attached to.
   */
  protected function toggleFieldTranslatability($entity_type, $bundle) {
    $fields = [$this->fieldName, $this->untranslatableFieldName];
    foreach ($fields as $field_name) {
      $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $translatable = !$field->isTranslatable();
      $field->set('translatable', $translatable);
      $field->save();
      $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $this->assertEqual($translatable, $field->isTranslatable(), 'Field translatability changed.');
    }
    \Drupal::cache('entity')->deleteAll();
  }

}
