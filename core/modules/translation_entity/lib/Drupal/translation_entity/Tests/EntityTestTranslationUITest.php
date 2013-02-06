<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityTestTranslationUITest.
 */

namespace Drupal\translation_entity\Tests;

/**
 * Tests the Entity Test Translation UI.
 */
class EntityTestTranslationUITest extends EntityTranslationUITest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity Test translation UI',
      'description' => 'Tests the test entity translation UI with the test entity.',
      'group' => 'Entity Translation UI',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    // Use the entity_test_mul as this has multilingual property support.
    $this->entityType = 'entity_test_mul';
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer entity_test content'));
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    return array(
      'name' => $this->randomName(),
      'user_id' => mt_rand(1, 128),
    ) + parent::getNewEntityValues($langcode);
  }

}
