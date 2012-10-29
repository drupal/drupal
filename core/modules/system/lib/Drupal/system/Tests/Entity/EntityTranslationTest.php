<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityTranslationTest.
 */

namespace Drupal\system\Tests\Entity;

use Exception;
use InvalidArgumentException;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entity translation.
 */
class EntityTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'locale');

  protected $langcodes;

  public static function getInfo() {
    return array(
      'name' => 'Entity Translation',
      'description' => 'Tests entity translation functionality.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp();
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
      $language = new Language(array(
        'langcode' => 'l' . $i,
        'name' => $this->randomString(),
      ));
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
      'user_id' => $GLOBALS['user']->uid,
    ));
    $this->assertEqual($entity->language()->langcode, LANGUAGE_NOT_SPECIFIED, 'Entity language not specified.');
    $this->assertFalse($entity->getTranslationLanguages(FALSE), 'No translations are available');

    // Set the value in default language.
    $entity->set($this->field_name, array(0 => array('value' => 'default value')));
    // Get the value.
    $this->assertEqual($entity->getTranslation(LANGUAGE_DEFAULT)->get($this->field_name)->value, 'default value', 'Untranslated value retrieved.');

    // Set the value in a certain language. As the entity is not
    // language-specific it should use the default language and so ignore the
    // specified language.
    $entity->getTranslation($this->langcodes[1])->set($this->field_name, array(0 => array('value' => 'default value2')));
    $this->assertEqual($entity->get($this->field_name)->value, 'default value2', 'Untranslated value updated.');
    $this->assertFalse($entity->getTranslationLanguages(FALSE), 'No translations are available');

    // Test getting a field value using a specific language for a not
    // language-specific entity.
    $this->assertEqual($entity->getTranslation($this->langcodes[1])->get($this->field_name)->value, 'default value2', 'Untranslated value retrieved.');

    // Now, make the entity language-specific by assigning a language and test
    // translating it.
    $entity->langcode->value = $this->langcodes[0];
    $entity->{$this->field_name} = array();
    $this->assertEqual($entity->language(), language_load($this->langcodes[0]), 'Entity language retrieved.');
    $this->assertFalse($entity->getTranslationLanguages(FALSE), 'No translations are available');

    // Set the value in default language.
    $entity->set($this->field_name, array(0 => array('value' => 'default value')));
    // Get the value.
    $this->assertEqual($entity->get($this->field_name)->value, 'default value', 'Untranslated value retrieved.');

    // Set a translation.
    $entity->getTranslation($this->langcodes[1])->set($this->field_name, array(0 => array('value' => 'translation 1')));
    $this->assertEqual($entity->getTranslation($this->langcodes[1])->{$this->field_name}->value, 'translation 1', 'Translated value set.');

    // Make sure the untranslated value stays.
    $this->assertEqual($entity->get($this->field_name)->value, 'default value', 'Untranslated value stays.');

    $translations[$this->langcodes[1]] = language_load($this->langcodes[1]);
    $this->assertEqual($entity->getTranslationLanguages(FALSE), $translations, 'Translations retrieved.');

    // Try to get a not available translation.
    $this->assertNull($entity->getTranslation($this->langcodes[2])->get($this->field_name)->value, 'A translation that is not available is NULL.');

    // Try to get a value using an invalid language code.
    try {
      $entity->getTranslation('invalid')->get($this->field_name)->value;
      $this->fail('Getting a translation for an invalid language is NULL.');
    }
    catch (InvalidArgumentException $e) {
      $this->pass('A translation for an invalid language is NULL.');
    }

    // Try to get an unstranslatable value from a translation in strict mode.
    try {
      $field_name = 'field_test_text';
      $value = $entity->getTranslation($this->langcodes[1])->get($field_name);
      $this->fail('Getting an unstranslatable value from a translation in strict mode throws an exception.');
    }
    catch (InvalidArgumentException $e) {
      $this->pass('Getting an unstranslatable value from a translation in strict mode throws an exception.');
    }

    // Try to get an unstranslatable value from a translation in non-strict
    // mode.
    $entity->set($field_name, array(0 => array('value' => 'default value')));
    $value = $entity->getTranslation($this->langcodes[1], FALSE)->get($field_name)->value;
    $this->assertEqual($value, 'default value', 'Untranslated value retrieved from translation in non-strict mode.');

    // Try to set a value using an invalid language code.
    try {
      $entity->getTranslation('invalid')->set($this->field_name, NULL);
      $this->fail("Setting a translation for an invalid language throws an exception.");
    }
    catch (InvalidArgumentException $e) {
      $this->pass("Setting a translation for an invalid language throws an exception.");
    }

    // Try to set an unstranslatable value into a translation in strict mode.
    try {
      $entity->getTranslation($this->langcodes[1])->set($field_name, NULL);
      $this->fail("Setting an unstranslatable value into a translation in strict mode throws an exception.");
    }
    catch (InvalidArgumentException $e) {
      $this->pass("Setting an unstranslatable value into a translation in strict mode throws an exception.");
    }

    // Set the value in default language.
    $entity->getTranslation($this->langcodes[1], FALSE)->set($field_name, array(0 => array('value' => 'default value2')));
    // Get the value.
    $this->assertEqual($entity->get($field_name)->value, 'default value2', 'Untranslated value set into a translation in non-strict mode.');

    // Test setting the user_id to 0 using setValue method.
    $entity = entity_create('entity_test', array(
      'name' => 'test',
      'user_id' => $GLOBALS['user']->uid,
    ));
    $translation = $entity->getTranslation($this->langcodes[1], FALSE);
    $translation->user_id->setValue(0);
    $this->assertIdentical($entity->get('user_id')->value, 0);
  }

  /**
   * Tests multilingual properties.
   */
  function testMultilingualProperties() {
    $name = $this->randomName();
    $uid = mt_rand(0, 127);
    $langcode = $this->langcodes[0];

    // Create a language neutral entity and check that properties are stored
    // as language neutral.
    $entity = entity_create('entity_test', array('name' => $name, 'user_id' => $uid));
    $entity->save();
    $entity = entity_test_load($entity->id());
    $this->assertEqual($entity->language()->langcode, LANGUAGE_NOT_SPECIFIED, 'Entity created as language neutral.');
    $this->assertEqual($name, $entity->getTranslation(LANGUAGE_DEFAULT)->get('name')->value, 'The entity name has been correctly stored as language neutral.');
    $this->assertEqual($uid, $entity->getTranslation(LANGUAGE_DEFAULT)->get('user_id')->value, 'The entity author has been correctly stored as language neutral.');
    // As fields, translatable properties should ignore the given langcode and
    // use neutral language if the entity is not translatable.
    $this->assertEqual($name, $entity->getTranslation($langcode)->get('name')->value, 'The entity name defaults to neutral language.');
    $this->assertEqual($uid, $entity->getTranslation($langcode)->get('user_id')->value, 'The entity author defaults to neutral language.');
    $this->assertEqual($name, $entity->get('name')->value, 'The entity name can be retrieved without specifying a language.');
    $this->assertEqual($uid, $entity->get('user_id')->value, 'The entity author can be retrieved without specifying a language.');

    // Create a language-aware entity and check that properties are stored
    // as language-aware.
    $entity = entity_create('entity_test', array('name' => $name, 'user_id' => $uid, 'langcode' => $langcode));
    $entity->save();
    $entity = entity_test_load($entity->id());
    $this->assertEqual($entity->language()->langcode, $langcode, 'Entity created as language specific.');
    $this->assertEqual($name, $entity->getTranslation($langcode)->get('name')->value, 'The entity name has been correctly stored as a language-aware property.');
    $this->assertEqual($uid, $entity->getTranslation($langcode)->get('user_id')->value, 'The entity author has been correctly stored as a language-aware property.');
    // Translatable properties on a translatable entity should use default
    // language if LANGUAGE_NOT_SPECIFIED is passed.
    $this->assertEqual($name, $entity->getTranslation(LANGUAGE_NOT_SPECIFIED)->get('name')->value, 'The entity name defaults to the default language.');
    $this->assertEqual($uid, $entity->getTranslation(LANGUAGE_NOT_SPECIFIED)->get('user_id')->value, 'The entity author defaults to the default language.');
    $this->assertEqual($name, $entity->get('name')->value, 'The entity name can be retrieved without specifying a language.');
    $this->assertEqual($uid, $entity->get('user_id')->value, 'The entity author can be retrieved without specifying a language.');

    // Create property translations.
    $properties = array();
    $default_langcode = $langcode;
    foreach ($this->langcodes as $langcode) {
      if ($langcode != $default_langcode) {
        $properties[$langcode] = array(
          'name' => array(0 => $this->randomName()),
          'user_id' => array(0 => mt_rand(0, 127)),
        );
      }
      else {
        $properties[$langcode] = array(
          'name' => array(0 => $name),
          'user_id' => array(0 => $uid),
        );
      }
      $entity->getTranslation($langcode)->setPropertyValues($properties[$langcode]);
    }
    $entity->save();

    // Check that property translation were correctly stored.
    $entity = entity_test_load($entity->id());
    foreach ($this->langcodes as $langcode) {
      $args = array('%langcode' => $langcode);
      $this->assertEqual($properties[$langcode]['name'][0], $entity->getTranslation($langcode)->get('name')->value, format_string('The entity name has been correctly stored for language %langcode.', $args));
      $this->assertEqual($properties[$langcode]['user_id'][0], $entity->getTranslation($langcode)->get('user_id')->value, format_string('The entity author has been correctly stored for language %langcode.', $args));
    }

    // Test query conditions (cache is reset at each call).
    $translated_id = $entity->id();
    // Create an additional entity with only the uid set. The uid for the
    // original language is the same of one used for a translation.
    $langcode = $this->langcodes[1];
    entity_create('entity_test', array(
      'user_id' => $properties[$langcode]['user_id'],
      'name' => 'some name',
    ))->save();

    $entities = entity_test_load_multiple();
    $this->assertEqual(count($entities), 3, 'Three entities were created.');
    $entities = entity_test_load_multiple(array($translated_id));
    $this->assertEqual(count($entities), 1, 'One entity correctly loaded by id.');
    $entities = entity_load_multiple_by_properties('entity_test', array('name' => $name));
    $this->assertEqual(count($entities), 2, 'Two entities correctly loaded by name.');
    // @todo The default language condition should go away in favor of an
    // explicit parameter.
    $entities = entity_load_multiple_by_properties('entity_test', array('name' => $properties[$langcode]['name'][0], 'default_langcode' => 0));
    $this->assertEqual(count($entities), 1, 'One entity correctly loaded by name translation.');
    $entities = entity_load_multiple_by_properties('entity_test', array('langcode' => $default_langcode, 'name' => $name));
    $this->assertEqual(count($entities), 1, 'One entity correctly loaded by name and language.');

    $entities = entity_load_multiple_by_properties('entity_test', array('langcode' => $langcode, 'name' => $properties[$langcode]['name'][0]));
    $this->assertEqual(count($entities), 0, 'No entity loaded by name translation specifying the translation language.');
    $entities = entity_load_multiple_by_properties('entity_test', array('langcode' => $langcode, 'name' => $properties[$langcode]['name'][0], 'default_langcode' => 0));
    $this->assertEqual(count($entities), 1, 'One entity loaded by name translation and language specifying to look for translations.');
    $entities = entity_load_multiple_by_properties('entity_test', array('user_id' => $properties[$langcode]['user_id'][0], 'default_langcode' => NULL));
    $this->assertEqual(count($entities), 2, 'Two entities loaded by uid without caring about property translatability.');

    // Test property conditions and orders with multiple languages in the same
    // query.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'entity_test');
    $query->entityCondition('langcode', $default_langcode);
    $query->propertyCondition('user_id', $properties[$default_langcode]['user_id'], NULL, 'original');
    $query->propertyCondition('name', $properties[$default_langcode]['name'], NULL, 'original');
    $query->propertyLanguageCondition($default_langcode, NULL, 'original');
    $query->propertyCondition('name', $properties[$langcode]['name'], NULL, 'translation');
    $query->propertyLanguageCondition($langcode, NULL, 'translation');
    $query->propertyOrderBy('name', 'ASC', 'original');
    $result = $query->execute();
    $this->assertEqual(count($result), 1, 'One entity loaded by name and uid using different language meta conditions.');

    // Test mixed property and field conditions.
    $entity = entity_load('entity_test', key($result['entity_test']), TRUE);
    $field_value = $this->randomString();
    $entity->getTranslation($langcode)->set($this->field_name, array(array('value' => $field_value)));
    $entity->save();
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'entity_test');
    $query->entityCondition('langcode', $default_langcode);
    $query->propertyCondition('user_id', $properties[$default_langcode]['user_id'], NULL, 'original');
    $query->propertyCondition('name', $properties[$default_langcode]['name'], NULL, 'original');
    $query->propertyLanguageCondition($default_langcode, NULL, 'original');
    $query->propertyCondition('name', $properties[$langcode]['name'], NULL, 'translation');
    $query->propertyLanguageCondition($langcode, NULL, 'translation');
    $query->fieldCondition($this->field_name, 'value', $field_value, NULL, NULL, 'translation');
    $query->fieldLanguageCondition($this->field_name, $langcode, NULL, NULL, 'translation');
    $query->propertyOrderBy('name', 'ASC', 'original');
    $result = $query->execute();
    $this->assertEqual(count($result), 1, 'One entity loaded by name, uid and field value using different language meta conditions.');
  }
}
