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
      'name' => 'Entity Test Translation UI',
      'description' => 'Tests the test entity translation UI with the test entity.',
      'group' => 'Entity Translation UI',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    $this->entityType = 'entity_test';
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  function getTranslatorPermissions() {
    return array('administer entity_test content', "translate $this->entityType entities", 'edit original values');
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
