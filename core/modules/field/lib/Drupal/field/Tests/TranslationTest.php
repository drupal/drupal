<?php

/**
 * @file
 * Definition of Drupal\field\Tests\TranslationTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

/**
 * Unit test class for the multilanguage fields logic.
 *
 * The following tests will check the multilanguage logic in field handling, and
 * that only the correct values are returned by field_available_languages().
 */
class TranslationTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * node is required because the tests alter node entity info.
   *
   * @var array
   */
  public static $modules = array('language', 'node');

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The name of the entity type to use in this test.
   *
   * @var string
   */
  protected $entity_type = 'test_entity';


  /**
   * An array defining the field to use in this test.
   *
   * @var array
   */
  protected $field_definition;

  /**
   * An array defining the field instance to use in this test.
   *
   * @var array
   */
  protected $instance_definition;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\Field
   */
  protected $field;

  /**
   * The field instance to use in this test.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  public static function getInfo() {
    return array(
      'name' => 'Field translations tests',
      'description' => 'Test multilanguage fields logic.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp();

    $this->installConfig(array('language'));

    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');

    $this->entity_type = 'entity_test';

    $this->field_definition = array(
      'name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'type' => 'test_field',
      'cardinality' => 4,
      'translatable' => TRUE,
    );
    entity_create('field_entity', $this->field_definition)->save();
    $this->field = field_read_field($this->entity_type, $this->field_name);

    $this->instance_definition = array(
      'field_name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'bundle' => 'entity_test',
    );
    entity_create('field_instance', $this->instance_definition)->save();
    $this->instance = field_read_instance($this->entity_type, $this->field_name, $this->entity_type);

    for ($i = 0; $i < 3; ++$i) {
      $language = new Language(array(
        'id' => 'l' . $i,
        'name' => $this->randomString(),
      ));
      language_save($language);
    }
  }

  /**
   * Ensures that only valid values are returned by field_available_languages().
   */
  function testFieldAvailableLanguages() {
    // Test 'translatable' fieldable info.
    field_test_entity_info_translatable('entity_test', FALSE);
    $field = clone($this->field);
    $field->field_name .= '_untranslatable';
    $field->save();

    // Enable field translations for the entity.
    field_test_entity_info_translatable('entity_test', TRUE);

    // Test hook_field_languages() invocation on a translatable field.
    \Drupal::state()->set('field_test.field_available_languages_alter', TRUE);
    $langcodes = field_content_languages();
    $available_langcodes = field_available_languages($this->entity_type, $this->field);
    foreach ($available_langcodes as $langcode) {
      if ($langcode != 'xx' && $langcode != 'en') {
        $this->assertTrue(in_array($langcode, $langcodes), format_string('%language is an enabled language.', array('%language' => $langcode)));
      }
    }
    $this->assertTrue(in_array('xx', $available_langcodes), format_string('%language was made available.', array('%language' => 'xx')));
    $this->assertFalse(in_array('en', $available_langcodes), format_string('%language was made unavailable.', array('%language' => 'en')));

    // Test field_available_languages() behavior for untranslatable fields.
    $this->field->translatable = FALSE;
    $this->field->save();
    $available_langcodes = field_available_languages($this->entity_type, $this->field);
    $this->assertTrue(count($available_langcodes) == 1 && $available_langcodes[0] === Language::LANGCODE_DEFAULT, 'For untranslatable fields only Language::LANGCODE_DEFAULT is available.');
  }

  /**
   * Test translatable fields storage/retrieval.
   */
  function testTranslatableFieldSaveLoad() {
    // Enable field translations for nodes.
    field_test_entity_info_translatable('node', TRUE);
    $entity_info = entity_get_info('node');
    $this->assertTrue(count($entity_info['translatable']), 'Nodes are translatable.');

    // Prepare the field translations.
    $entity_type = 'entity_test';
    field_test_entity_info_translatable($entity_type, TRUE);
    $entity = entity_create($entity_type, array('type' => $this->instance->bundle));
    $field_translations = array();
    $available_langcodes = field_available_languages($entity_type, $this->field);
    $this->assertTrue(count($available_langcodes) > 1, 'Field is translatable.');
    $available_langcodes = array_keys(language_list());
    $entity->langcode->value = reset($available_langcodes);
    foreach ($available_langcodes as $langcode) {
      $field_translations[$langcode] = $this->_generateTestFieldValues($this->field->getFieldCardinality());
      $entity->getTranslation($langcode)->{$this->field_name}->setValue($field_translations[$langcode]);
    }

    // Save and reload the field translations.
    $entity = $this->entitySaveReload($entity);

    // Check if the correct values were saved/loaded.
    foreach ($field_translations as $langcode => $items) {
      $result = TRUE;
      foreach ($items as $delta => $item) {
        $result = $result && $item['value'] == $entity->getTranslation($langcode)->{$this->field_name}[$delta]->value;
      }
      $this->assertTrue($result, format_string('%language translation correctly handled.', array('%language' => $langcode)));
    }

    // Test default values.
    $field_name_default = drupal_strtolower($this->randomName() . '_field_name');
    $field_definition = $this->field_definition;
    $field_definition['name'] = $field_name_default;
    entity_create('field_entity', $field_definition)->save();

    $instance_definition = $this->instance_definition;
    $instance_definition['field_name'] = $field_name_default;
    $instance_definition['default_value'] = array(array('value' => rand(1, 127)));
    $instance = entity_create('field_instance', $instance_definition);
    $instance->save();

    entity_info_cache_clear();

    $translation_langcodes = array_slice($available_langcodes, 0, 2);
    asort($translation_langcodes);
    $translation_langcodes = array_values($translation_langcodes);

    $values = array('type' => $instance->bundle, 'langcode' => $translation_langcodes[0]);
    $entity = entity_create($entity_type, $values);
    foreach ($translation_langcodes as $langcode) {
      $values[$this->field_name][$langcode] = $this->_generateTestFieldValues($this->field->getFieldCardinality());
      $entity->getTranslation($langcode, FALSE)->{$this->field_name}->setValue($values[$this->field_name][$langcode]);
    }

    $field_langcodes = array_keys($entity->getTranslationLanguages());
    sort($field_langcodes);
    $this->assertEqual($translation_langcodes, $field_langcodes, 'Missing translations did not get a default value.');

    // @todo Test every translation once the Entity Translation API allows for
    //   multilingual defaults.
    $langcode = $entity->language()->id;
    $this->assertEqual($entity->getTranslation($langcode)->{$field_name_default}->getValue(), $instance->default_value, format_string('Default value correctly populated for language %language.', array('%language' => $langcode)));

    // Check that explicit empty values are not overridden with default values.
    foreach (array(NULL, array()) as $empty_items) {
      $values = array('type' => $instance->bundle, 'langcode' => $translation_langcodes[0]);
      $entity = entity_create($entity_type, $values);
      foreach ($translation_langcodes as $langcode) {
        $values[$this->field_name][$langcode] = $this->_generateTestFieldValues($this->field->getFieldCardinality());
        $entity->getTranslation($langcode)->{$this->field_name}->setValue($values[$this->field_name][$langcode]);
        $entity->getTranslation($langcode)->{$field_name_default}->setValue($empty_items);
        $values[$field_name_default][$langcode] = $empty_items;
      }

      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $this->assertEqual($entity->getTranslation($langcode)->{$field_name_default}->getValue(), $empty_items, format_string('Empty value correctly populated for language %language.', array('%language' => $langcode)));
      }
    }
  }

}
