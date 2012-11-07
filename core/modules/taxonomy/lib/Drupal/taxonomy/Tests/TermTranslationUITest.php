<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermTranslationUITest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\translation_entity\Tests\EntityTranslationUITest;

/**
 * Tests the Term Translation UI.
 */
class TermTranslationUITest extends EntityTranslationUITest {

  /**
   * The name of the test taxonomy term.
   */
  protected $name;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term translation UI',
      'description' => 'Tests the basic term translation UI.',
      'group' => 'Taxonomy',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    $this->entityType = 'taxonomy_term';
    $this->bundle = 'tags';
    $this->name = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::setupBundle().
   */
  protected function setupBundle() {
    parent::setupBundle();

    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->bundle,
      'description' => $this->randomName(),
      'machine_name' => $this->bundle,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'help' => '',
      'weight' => mt_rand(0, 10),
    ));
    taxonomy_vocabulary_save($vocabulary);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  function getTranslatorPermissions() {
    return array('administer taxonomy', "translate $this->entityType entities", 'edit original values');
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::createEntity().
   */
  protected function createEntity($values, $langcode) {
    $vocabulary = taxonomy_vocabulary_machine_name_load($this->bundle);
    $values['vid'] = $vocabulary->id();
    return parent::createEntity($values, $langcode);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Term name is not translatable hence we use a fixed value.
    return array('name' => $this->name) + parent::getNewEntityValues($langcode);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::testTranslationUI().
   */
  public function testTranslationUI() {
    parent::testTranslationUI();

    // Make sure that no row was inserted for taxonomy vocabularies, which do
    // not have translations enabled.
    $rows = db_query('SELECT * FROM {translation_entity}')->fetchAll();
    $this->assertEqual(2, count($rows));
    $this->assertEqual('taxonomy_term', $rows[0]->entity_type);
    $this->assertEqual('taxonomy_term', $rows[1]->entity_type);
  }
}
