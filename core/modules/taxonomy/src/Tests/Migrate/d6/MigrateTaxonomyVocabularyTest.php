<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateTaxonomyVocabularyTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group taxonomy
 */
class MigrateTaxonomyVocabularyTest extends MigrateDrupal6TestBase {

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
    $this->loadDumps(['Vocabulary.php', 'VocabularyNodeTypes.php']);
    $this->executeMigration('d6_taxonomy_vocabulary');
  }

  /**
   * Tests the Drupal 6 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    for ($i = 0; $i < 3; $i++) {
      $j = $i + 1;
      $vocabulary = Vocabulary::load("vocabulary_{$j}_i_{$i}_");
      $this->assertIdentical(entity_load('migration', 'd6_taxonomy_vocabulary')->getIdMap()->lookupDestinationID(array($j)), array($vocabulary->id()));
      $this->assertIdentical("vocabulary $j (i=$i)", $vocabulary->label());
      $this->assertIdentical("description of vocabulary $j (i=$i)", $vocabulary->getDescription());
      $this->assertIdentical($i, $vocabulary->getHierarchy());
      $this->assertIdentical(4 + $i, $vocabulary->get('weight'));
    }
    $vocabulary = Vocabulary::load('vocabulary_name_much_longer_than');
    $this->assertIdentical('vocabulary name much longer than thirty two characters', $vocabulary->label());
    $this->assertIdentical('description of vocabulary name much longer than thirty two characters', $vocabulary->getDescription());
    $this->assertIdentical(3, $vocabulary->getHierarchy());
    $this->assertIdentical(7, $vocabulary->get('weight'));
  }

}
