<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTaxonomyTermTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Upgrade taxonomy terms.
 *
 * @group migrate_drupal
 */
class MigrateTaxonomyTermTest extends MigrateDrupal6TestBase {

  static $modules = array('taxonomy', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $this->prepareMigrations(array(
      'd6_taxonomy_vocabulary' => array(
        array(array(1), array('vocabulary_1_i_0_')),
        array(array(2), array('vocabulary_2_i_1_')),
        array(array(3), array('vocabulary_3_i_2_')),
    )));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_taxonomy_term');
    $dumps = array(
      $this->getDumpDirectory() . '/TermData.php',
      $this->getDumpDirectory() . '/TermHierarchy.php',
      $this->getDumpDirectory() . '/Vocabulary.php',
      $this->getDumpDirectory() . '/VocabularyNodeTypes.php',
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
        'parent' => array(0),
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
      $this->assertIdentical("term {$tid} of vocabulary {$values['source_vid']}", $term->name->value);
      $this->assertIdentical("description of term {$tid} of vocabulary {$values['source_vid']}", $term->description->value);
      $this->assertIdentical($values['vid'], $term->vid->target_id);
      $this->assertIdentical((string) $values['weight'], $term->weight->value);
      if ($values['parent'] === array(0)) {
        $this->assertNull($term->parent->target_id);
      }
      else {
        $parents = array();
        foreach (taxonomy_term_load_parents($tid) as $parent) {
          $parents[] = (int) $parent->id();
        }
        $this->assertIdentical($parents, $values['parent']);
      }
    }
  }

}
