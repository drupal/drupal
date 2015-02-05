<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTaxonomyVocabularyTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateTaxonomyVocabularyTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_taxonomy_vocabulary');
    $dumps = array(
      $this->getDumpDirectory() . '/Vocabulary.php',
      $this->getDumpDirectory() . '/VocabularyNodeTypes.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    for ($i = 0; $i < 3; $i++) {
      $j = $i + 1;
      $vocabulary = Vocabulary::load("vocabulary_{$j}_i_{$i}_");
      $this->assertIdentical(array($vocabulary->id()), entity_load('migration', 'd6_taxonomy_vocabulary')->getIdMap()->lookupDestinationID(array($j)));
      $this->assertIdentical($vocabulary->label(), "vocabulary $j (i=$i)");
      $this->assertIdentical($vocabulary->getDescription(), "description of vocabulary $j (i=$i)");
      $this->assertIdentical($vocabulary->getHierarchy(), $i);
      $this->assertIdentical($vocabulary->get('weight'), 4 + $i);
    }
    $vocabulary = Vocabulary::load('vocabulary_name_much_longer_than');
    $this->assertIdentical($vocabulary->label(), 'vocabulary name much longer than thirty two characters');
    $this->assertIdentical($vocabulary->getDescription(), 'description of vocabulary name much longer than thirty two characters');
    $this->assertIdentical($vocabulary->getHierarchy(), 3);
    $this->assertIdentical($vocabulary->get('weight'), 7);
  }

}
