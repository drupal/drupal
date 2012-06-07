<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityTranslationTest.
 */

namespace Drupal\entity\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests entity translation.
 */
class EntityTranslationTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Entity Translation',
      'description' => 'Tests entity translation functionality.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp('entity_test', 'language', 'locale');
    // Enable translations for the test entity type.
    variable_set('entity_test_translation', TRUE);

    // Create a translatable test field.
    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');
    $field = array(
      'field_name' => $this->field_name,
      'type' => 'text',
      'cardinality' => 4,
      'translatable' => TRUE,
    );
    field_create_field($field);
    $this->field = field_read_field($this->field_name);

    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    field_create_instance($instance);
    $this->instance = field_read_instance('entity_test', $this->field_name, 'entity_test');

    // Create test languages.
    $this->langcodes = array();
    for ($i = 0; $i < 3; ++$i) {
      $language = (object) array(
        'langcode' => 'l' . $i,
        'name' => $this->randomString(),
      );
      $this->langcodes[$i] = $language->langcode;
      language_save($language);
    }
  }

  /**
   * Tests language related methods of the Entity class.
   */
  function testEntityLanguageMethods() {
    $entity = entity_create('entity_test', array(
      'name' => 'test',
      'uid' => $GLOBALS['user']->uid,
    ));
    $this->assertFalse($entity->language(), 'No entity language has been specified.');
    $this->assertFalse($entity->translations(), 'No translations are available');

    // Set the value in default language.
    $entity->set($this->field_name, array(0 => array('value' => 'default value')));
    // Get the value.
    $value = $entity->get($this->field_name);
    $this->assertEqual($value, array(0 => array('value' => 'default value')), 'Untranslated value retrieved.');

    // Set the value in a certain language. As the entity is not
    // language-specific it should use the default language and so ignore the
    // specified language.
    $entity->set($this->field_name, array(0 => array('value' => 'default value2')), $this->langcodes[1]);
    $value = $entity->get($this->field_name);
    $this->assertEqual($value, array(0 => array('value' => 'default value2')), 'Untranslated value updated.');
    $this->assertFalse($entity->translations(), 'No translations are available');

    // Test getting a field value using the default language for a not
    // language-specific entity.
    $value = $entity->get($this->field_name, $this->langcodes[1]);
    $this->assertEqual($value, array(0 => array('value' => 'default value2')), 'Untranslated value retrieved.');

    // Now, make the entity language-specific by assigning a language and test
    // translating it.
    $entity->langcode = $this->langcodes[0];
    $entity->{$this->field_name} = array();
    $this->assertEqual($entity->language(), language_load($this->langcodes[0]), 'Entity language retrieved.');
    $this->assertFalse($entity->translations(), 'No translations are available');

    // Set the value in default language.
    $entity->set($this->field_name, array(0 => array('value' => 'default value')));
    // Get the value.
    $value = $entity->get($this->field_name);
    $this->assertEqual($value, array(0 => array('value' => 'default value')), 'Untranslated value retrieved.');

    // Set a translation.
    $entity->set($this->field_name, array(0 => array('value' => 'translation 1')), $this->langcodes[1]);
    $value = $entity->get($this->field_name, $this->langcodes[1]);
    $this->assertEqual($value, array(0 => array('value' => 'translation 1')), 'Translated value set.');
    // Make sure the untranslated value stays.
    $value = $entity->get($this->field_name);
    $this->assertEqual($value, array(0 => array('value' => 'default value')), 'Untranslated value stays.');

    $translations[$this->langcodes[1]] = language_load($this->langcodes[1]);
    $this->assertEqual($entity->translations(), $translations, 'Translations retrieved.');

    // Try to get a not available translation.
    $value = $entity->get($this->field_name, $this->langcodes[2]);
    $this->assertNull($value, 'A translation that is not available is NULL.');
  }
}
