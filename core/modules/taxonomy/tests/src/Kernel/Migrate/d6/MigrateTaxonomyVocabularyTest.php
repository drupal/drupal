<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate taxonomy vocabularies to taxonomy.vocabulary.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateTaxonomyVocabularyTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_taxonomy_vocabulary');
  }

  /**
   * Tests the Drupal 6 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    for ($i = 0; $i < 3; $i++) {
      $j = $i + 1;
      $vocabulary = Vocabulary::load("vocabulary_{$j}_i_{$i}_");
      $this->assertIdentical($this->getMigration('d6_taxonomy_vocabulary')->getIdMap()->lookupDestinationID([$j]), [$vocabulary->id()]);
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
