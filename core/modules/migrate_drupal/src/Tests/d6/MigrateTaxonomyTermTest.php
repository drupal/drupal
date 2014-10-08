<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTaxonomyTermTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Upgrade taxonomy terms.
 *
 * @group migrate_drupal
 */
class MigrateTaxonomyTermTest extends MigrateDrupalTestBase {

  static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->prepareMigrations(array(
      'd6_taxonomy_vocabulary' => array(
        array(array(1), array('vocabulary_1_i_0_')),
        array(array(2), array('vocabulary_2_i_1_')),
        array(array(3), array('vocabulary_3_i_2_')),
    )));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_taxonomy_term');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6TaxonomyTerm.php',
      $this->getDumpDirectory() . '/Drupal6TaxonomyVocabulary.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerms() {
    $expected_results = array(
      '1' => array(
        'source_vid' => 1,
        'vid' => 'vocabulary_1_i_0_',
        'weight' => 0,
        'parent' => array(2),
      ),
      '2' => array(
        'source_vid' => 2,
        'vid' => 'vocabulary_2_i_1_',
        'weight' => 3,
        'parent' => array(0),
      ),
      '3' => array(
        'source_vid' => 2,
        'vid' => 'vocabulary_2_i_1_',
        'weight' => 4,
        'parent' => array(2),
      ),
      '4' => array(
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 6,
        'parent' => array(0),
      ),
      '5' => array(
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 7,
        'parent' => array(4),
      ),
      '6' => array(
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 8,
        'parent' => array(4, 5),
      ),
    );
    $terms = Term::loadMultiple(array_keys($expected_results));
    foreach ($expected_results as $tid => $values) {
      /** @var Term $term */
      $term = $terms[$tid];
      $this->assertIdentical($term->name->value, "term {$tid} of vocabulary {$values['source_vid']}");
      $this->assertIdentical($term->description->value, "description of term {$tid} of vocabulary {$values['source_vid']}");
      $this->assertEqual($term->vid->target_id, $values['vid']);
      $this->assertEqual($term->weight->value, $values['weight']);
      if ($values['parent'] === array(0)) {
        $this->assertEqual($term->parent->target_id, 0);
      }
      else {
        $parents = array();
        foreach (taxonomy_term_load_parents($tid) as $parent) {
          $parents[] = $parent->id();
        }
        $this->assertEqual($values['parent'], $parents);
      }
    }
  }

}
