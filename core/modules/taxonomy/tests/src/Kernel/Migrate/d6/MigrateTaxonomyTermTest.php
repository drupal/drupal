<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade taxonomy terms.
 *
 * @group migrate_drupal_6
 */
class MigrateTaxonomyTermTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations(['d6_taxonomy_vocabulary', 'd6_taxonomy_term']);
  }

  /**
   * Tests the Drupal 6 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerms(): void {
    $expected_results = [
      '1' => [
        'source_vid' => 1,
        'vid' => 'vocabulary_1_i_0_',
        'weight' => 0,
        'parent' => [0],
        'language' => 'zu',
      ],
      '2' => [
        'source_vid' => 2,
        'vid' => 'vocabulary_2_i_1_',
        'weight' => 3,
        'parent' => [0],
        'language' => 'fr',
      ],
      '3' => [
        'source_vid' => 2,
        'vid' => 'vocabulary_2_i_1_',
        'weight' => 4,
        'parent' => [2],
        'language' => 'fr',
      ],
      '4' => [
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 6,
        'parent' => [0],
      ],
      '5' => [
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 7,
        'parent' => [4],
      ],
      '6' => [
        'source_vid' => 3,
        'vid' => 'vocabulary_3_i_2_',
        'weight' => 8,
        'parent' => [4, 5],
      ],
    ];
    $terms = Term::loadMultiple(array_keys($expected_results));

    // Find each term in the tree.
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $vids = array_unique(array_column($expected_results, 'vid'));
    $tree_terms = [];
    foreach ($vids as $vid) {
      foreach ($storage->loadTree($vid) as $term) {
        $tree_terms[$term->tid] = $term;
      }
    }

    foreach ($expected_results as $tid => $values) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $terms[$tid];
      $language = isset($values['language']) ? $values['language'] . ' - ' : '';
      $this->assertSame("{$language}term {$tid} of vocabulary {$values['source_vid']}", $term->name->value);
      $this->assertSame("{$language}description of term {$tid} of vocabulary {$values['source_vid']}", $term->description->value);
      $this->assertSame($values['vid'], $term->vid->target_id);
      $this->assertSame((string) $values['weight'], $term->weight->value);
      if ($values['parent'] === [0]) {
        $this->assertSame(0, (int) $term->parent->target_id);
      }
      else {
        $parents = [];
        foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tid) as $parent) {
          $parents[] = (int) $parent->id();
        }
        $this->assertSame($parents, $values['parent']);
      }

      $this->assertArrayHasKey($tid, $tree_terms, "Term $tid exists in vocabulary tree");
      $tree_term = $tree_terms[$tid];

      // PostgreSQL, MySQL and SQLite may not return the parent terms in the
      // same order so sort before testing.
      $expected_parents = $values['parent'];
      sort($expected_parents);
      $actual_parents = $tree_term->parents;
      sort($actual_parents);
      $this->assertEquals($expected_parents, $actual_parents, "Term $tid has correct parents in vocabulary tree");
    }
  }

}
