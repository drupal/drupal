<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests multilanguage fields logic.
 *
 * The following tests will check the multilanguage logic in field handling.
 *
 * @group field
 */
class TranslationTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * The node module is required because the tests alter the node entity type.
   *
   * @var array
   */
  protected static $modules = ['language', 'node'];

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The name of the entity type to use in this test.
   *
   * @var string
   */
  protected $entityType = 'test_entity';

  /**
   * An array defining the field storage to use in this test.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * An array defining the field to use in this test.
   *
   * @var array
   */
  protected $fieldDefinition;

  /**
   * The field storage to use in this test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['language']);

    $this->fieldName = mb_strtolower($this->randomMachineName());

    $this->entityType = 'entity_test';

    $this->fieldStorageDefinition = [
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'test_field',
      'cardinality' => 4,
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->fieldDefinition = [
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ];
    $this->field = FieldConfig::create($this->fieldDefinition);
    $this->field->save();

    for ($i = 0; $i < 3; ++$i) {
      ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ])->save();
    }
  }

  /**
   * Tests translatable fields storage/retrieval.
   */
  public function testTranslatableFieldSaveLoad() {
    // Enable field translations for nodes.
    field_test_entity_info_translatable('node', TRUE);
    $entity_type = \Drupal::entityTypeManager()->getDefinition('node');
    $this->assertTrue($entity_type->isTranslatable(), 'Nodes are translatable.');

    // Prepare the field translations.
    $entity_type_id = 'entity_test';
    field_test_entity_info_translatable($entity_type_id, TRUE);
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type_id)
      ->create(['type' => $this->field->getTargetBundle()]);
    $field_translations = [];
    $available_langcodes = array_keys($this->container->get('language_manager')->getLanguages());
    $entity->langcode->value = reset($available_langcodes);
    foreach ($available_langcodes as $langcode) {
      $field_translations[$langcode] = $this->_generateTestFieldValues($this->fieldStorage->getCardinality());
      $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
      $translation->{$this->fieldName}->setValue($field_translations[$langcode]);
    }

    // Save and reload the field translations.
    $entity = $this->entitySaveReload($entity);

    // Check if the correct values were saved/loaded.
    foreach ($field_translations as $langcode => $items) {
      $result = TRUE;
      foreach ($items as $delta => $item) {
        $result = $result && $item['value'] == $entity->getTranslation($langcode)->{$this->fieldName}[$delta]->value;
      }
      $this->assertTrue($result, new FormattableMarkup('%language translation correctly handled.', ['%language' => $langcode]));
    }

    // Test default values.
    $field_name_default = mb_strtolower($this->randomMachineName() . '_field_name');
    $field_storage_definition = $this->fieldStorageDefinition;
    $field_storage_definition['field_name'] = $field_name_default;
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();

    $field_definition = $this->fieldDefinition;
    $field_definition['field_storage'] = $field_storage;
    $field_definition['default_value'] = [['value' => rand(1, 127)]];
    $field = FieldConfig::create($field_definition);
    $field->save();

    $translation_langcodes = array_slice($available_langcodes, 0, 2);
    asort($translation_langcodes);
    $translation_langcodes = array_values($translation_langcodes);

    $values = ['type' => $field->getTargetBundle(), 'langcode' => $translation_langcodes[0]];
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type_id)
      ->create($values);
    foreach ($translation_langcodes as $langcode) {
      $values[$this->fieldName][$langcode] = $this->_generateTestFieldValues($this->fieldStorage->getCardinality());
      $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
      $translation->{$this->fieldName}->setValue($values[$this->fieldName][$langcode]);
    }

    $field_langcodes = array_keys($entity->getTranslationLanguages());
    sort($field_langcodes);
    $this->assertEquals($translation_langcodes, $field_langcodes, 'Missing translations did not get a default value.');

    // @todo Test every translation once the Entity Translation API allows for
    //   multilingual defaults.
    $langcode = $entity->language()->getId();
    $this->assertEquals($field->getDefaultValueLiteral(), $entity->getTranslation($langcode)->{$field_name_default}->getValue(), new FormattableMarkup('Default value correctly populated for language %language.', ['%language' => $langcode]));

    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    // Check that explicit empty values are not overridden with default values.
    foreach ([NULL, []] as $empty_items) {
      $values = ['type' => $field->getTargetBundle(), 'langcode' => $translation_langcodes[0]];
      $entity = $storage->create($values);
      foreach ($translation_langcodes as $langcode) {
        $values[$this->fieldName][$langcode] = $this->_generateTestFieldValues($this->fieldStorage->getCardinality());
        $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
        $translation->{$this->fieldName}->setValue($values[$this->fieldName][$langcode]);
        $translation->{$field_name_default}->setValue($empty_items);
        $values[$field_name_default][$langcode] = $empty_items;
      }

      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $this->assertEquals([], $entity->getTranslation($langcode)->{$field_name_default}->getValue(), new FormattableMarkup('Empty value correctly populated for language %language.', ['%language' => $langcode]));
      }
    }
  }

  /**
   * Tests field access.
   *
   * Regression test to verify that fieldAccess() can be called while only
   * passing the required parameters.
   *
   * @see https://www.drupal.org/node/2404739
   */
  public function testFieldAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler($this->entityType);
    $this->assertTrue($access_control_handler->fieldAccess('view', $this->field));
  }

}
