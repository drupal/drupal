<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\ContentTestTranslationUITest.
 */

namespace Drupal\content_translation\Tests;

/**
 * Tests the Entity Test Translation UI.
 */
class ContentTestTranslationUITest extends ContentTranslationUITest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity Test translation UI',
      'description' => 'Tests the test content translation UI with the test entity.',
      'group' => 'Content Translation UI',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    // Use the entity_test_mul as this has multilingual property support.
    $this->entityTypeId = 'entity_test_mul';
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer entity_test content'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    $user = $this->drupalCreateUser();
    return array(
      'name' => $this->randomName(),
      'user_id' => $user->id(),
    ) + parent::getNewEntityValues($langcode);
  }

}
