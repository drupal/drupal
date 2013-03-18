<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\ConfigTestTranslationUITest.
 */

namespace Drupal\translation_entity\Tests;

/**
 * Tests the Config Test Translation UI.
 */
class ConfigTestTranslationUITest extends EntityTranslationUITest {

  /**
   * Modules to enable.
   *
   * config_test.module has a (soft) dependency on image.module.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'image', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Config Test Translation UI',
      'description' => 'Tests the test entity translation UI with the test config entity.',
      'group' => 'Entity Translation UI',
    );
  }

  function setUp() {
    $this->entityType = 'config_test';
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::enableTranslation().
   */
  protected function enableTranslation() {
    $this->container->get('state')->set('config_test.translatable', TRUE);
    parent::enableTranslation();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    return array(
      'id' => $this->randomName(),
      'label' => $this->randomName(),
      'style' => 'large',
    );
  }

  /**
   * Overrides EntityTranslationTest::testTranslationUI().
   *
   * @todo This override is a copy-paste of parts of the parent method. Turn
   *   ConfigTest into a properly translatable entity and remove this override.
   */
  function testTranslationUI() {
    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $id = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity = entity_load($this->entityType, $id, TRUE);
    $this->assertTrue($entity, t('Entity found in the database.'));

    $translation = $this->getTranslation($entity, $default_langcode);
    foreach ($values[$default_langcode] as $property => $value) {
      $stored_value = $this->getValue($translation, $property, $default_langcode);
      $value = is_array($value) ? $value[0]['value'] : $value;
      $message = format_string('@property correctly stored in the default language.', array('@property' => $property));
      $this->assertIdentical($stored_value, $value, $message);
    }
  }

  /**
   * Overrides EntityTranslationUITest::setupTestFields().
   *
   * The config_test entity is not fieldable.
   */
  protected function setupTestFields() {}

}
