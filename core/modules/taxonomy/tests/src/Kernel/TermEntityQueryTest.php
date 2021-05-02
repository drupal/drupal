<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Verifies operation of a taxonomy-based Entity Query.
 *
 * @group taxonomy
 */
class TermEntityQueryTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * Tests that a basic taxonomy entity query works.
   */
  public function testTermEntityQuery() {
    $this->installEntitySchema('taxonomy_term');
    $vocabulary = $this->createVocabulary();

    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($vocabulary);
      $terms[$term->id()] = $term;
    }
    $result = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->execute();
    sort($result);
    $this->assertEquals(array_keys($terms), $result);
    $tid = reset($result);
    $ids = (object) [
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $vocabulary->id(),
    ];
    $term = _field_create_entity_from_ids($ids);
    $this->assertEquals($tid, $term->id());

    // Create a second vocabulary and five more terms.
    $vocabulary2 = $this->createVocabulary();
    $terms2 = [];
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($vocabulary2);
      $terms2[$term->id()] = $term;
    }

    $result = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('vid', $vocabulary2->id())
      ->execute();
    sort($result);
    $this->assertEqual(array_keys($terms2), $result);
    $tid = reset($result);
    $ids = (object) [
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $vocabulary2->id(),
    ];
    $term = _field_create_entity_from_ids($ids);
    $this->assertEquals($tid, $term->id());
  }

}
