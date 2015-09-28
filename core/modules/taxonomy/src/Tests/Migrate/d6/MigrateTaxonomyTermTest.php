<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateTaxonomyTermTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\taxonomy\Entity\Term;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade taxonomy terms.
 *
 * @group migrate_drupal_6
 */
class MigrateTaxonomyTermTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations(['d6_taxonomy_vocabulary', 'd6_taxonomy_term']);
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
        foreach (\Drupal::entityManager()->getStorage('taxonomy_term')->loadParents($tid) as $parent) {
          $parents[] = (int) $parent->id();
        }
        $this->assertIdentical($parents, $values['parent']);
      }
    }
  }

}
