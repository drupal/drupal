<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermTranslationUITest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the Term Translation UI.
 */
class TermTranslationUITest extends ContentTranslationUITest {

  /**
   * The name of the test taxonomy term.
   */
  protected $name;

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term translation UI',
      'description' => 'Tests the basic term translation UI.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    $this->entityTypeId = 'taxonomy_term';
    $this->bundle = 'tags';
    $this->name = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::setupBundle().
   */
  protected function setupBundle() {
    parent::setupBundle();

    // Create a vocabulary.
    $this->vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->bundle,
      'description' => $this->randomName(),
      'vid' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $this->vocabulary->save();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer taxonomy'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Term name is not translatable hence we use a fixed value.
    return array('name' => $this->name) + parent::getNewEntityValues($langcode);
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = parent::getEditValues($values, $langcode, $new);

    // To be able to post values for the configurable base fields (name,
    // description) have to be suffixed with [0][value].
    foreach ($edit as $property => $value) {
      foreach (array('name', 'description') as $key) {
        if ($property == $key) {
          $edit[$key . '[0][value]'] = $value;
          unset($edit[$property]);
        }
      }
    }
    return $edit;
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::testTranslationUI().
   */
  public function testTranslationUI() {
    parent::testTranslationUI();

    // Make sure that no row was inserted for taxonomy vocabularies which do
    // not have translations enabled.
    $rows = db_query('SELECT * FROM {content_translation}')->fetchAll();
    foreach ($rows as $row) {
      $this->assertEqual('taxonomy_term', $row->entity_type, 'Row contains a taxonomy term.');
    }
  }

  /**
   * Tests translate link on vocabulary term list.
   */
  function testTranslateLinkVocabularyAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), array('access administration pages', 'administer taxonomy')));
    $this->drupalLogin($this->admin_user);

    $values = array(
      'name' => $this->randomName(),
    );
    $translatable_tid = $this->createEntity($values, $this->langcodes[0], $this->vocabulary->id());

    // Create an untranslatable vocabulary.
    $untranslatable_vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'untranslatable_voc',
      'description' => $this->randomName(),
      'vid' => 'untranslatable_voc',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $untranslatable_vocabulary->save();

    $values = array(
      'name' => $this->randomName(),
    );
    $untranslatable_tid = $this->createEntity($values, $this->langcodes[0], $untranslatable_vocabulary->id());

    // Verify translation links.
    $this->drupalGet('admin/structure/taxonomy/manage/' .  $this->vocabulary->id() . '/overview');
    $this->assertResponse(200, 'The translatable vocabulary page was found.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/translations', 0, 'The translations link exists for a translatable vocabulary.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/edit', 0, 'The edit link exists for a translatable vocabulary.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $untranslatable_vocabulary->id() . '/overview');
    $this->assertResponse(200);
    $this->assertLinkByHref('term/' . $untranslatable_tid . '/edit');
    $this->assertNoLinkByHref('term/' . $untranslatable_tid . '/translations');
  }

}
