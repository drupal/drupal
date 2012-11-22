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
  protected function createEntity($values, $langcode, $vocabulary_name = NULL) {
    if (!isset($vocabulary_name)) {
      $vocabulary_name = $this->bundle;
    }
    $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
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

  /**
   * Tests translate link on vocabulary term list.
   */
  function testTranslateLinkVocabularyAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer taxonomy', 'translate any entity'));
    $this->drupalLogin($this->admin_user);

    $translatable_vocabulary_name = taxonomy_vocabulary_machine_name_load($this->bundle)->name;
    $translatable_tid = $this->createEntity(array(), $this->langcodes[0]);

    // Create an untranslatable vocabulary.
    $untranslatable_vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'untranslatable_voc',
      'description' => $this->randomName(),
      'machine_name' => 'untranslatable_voc',
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'help' => '',
      'weight' => mt_rand(0, 10),
    ));
    taxonomy_vocabulary_save($untranslatable_vocabulary);

    $untranslatable_vocabulary_name = $untranslatable_vocabulary->name;
    $untranslatable_tid = $this->createEntity(array(), $this->langcodes[0], $untranslatable_vocabulary_name);

    // Verify translation links.
    $this->drupalGet('admin/structure/taxonomy/' . $translatable_vocabulary_name);
    $this->assertResponse(200);
    $this->assertLinkByHref('term/' . $translatable_tid . '/translations');
    $this->assertLinkByHref('term/' . $translatable_tid . '/edit');

    $this->drupalGet('admin/structure/taxonomy/' . $untranslatable_vocabulary_name);
    $this->assertResponse(200);
    $this->assertLinkByHref('term/' . $untranslatable_tid . '/edit');
    $this->assertNoLinkByHref('term/' . $untranslatable_tid . '/translations');
  }

}
