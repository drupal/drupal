<?php

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Verifies operation of a taxonomy-based Entity Query.
 *
 * @group taxonomy
 */
class EfqTest extends TaxonomyTestBase {

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Tests that a basic taxonomy entity query works.
   */
  public function testTaxonomyEfq() {
    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($this->vocabulary);
      $terms[$term->id()] = $term;
    }
    $result = \Drupal::entityQuery('taxonomy_term')->execute();
    sort($result);
    $this->assertEqual(array_keys($terms), $result, 'Taxonomy terms were retrieved by entity query.');
    $tid = reset($result);
    $ids = (object) [
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $this->vocabulary->id(),
    ];
    $term = _field_create_entity_from_ids($ids);
    $this->assertEqual($term->id(), $tid, 'Taxonomy term can be created based on the IDs.');

    // Create a second vocabulary and five more terms.
    $vocabulary2 = $this->createVocabulary();
    $terms2 = [];
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($vocabulary2);
      $terms2[$term->id()] = $term;
    }

    $result = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary2->id())
      ->execute();
    sort($result);
    $this->assertEqual(array_keys($terms2), $result, format_string('Taxonomy terms from the %name vocabulary were retrieved by entity query.', ['%name' => $vocabulary2->label()]));
    $tid = reset($result);
    $ids = (object) [
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $vocabulary2->id(),
    ];
    $term = _field_create_entity_from_ids($ids);
    $this->assertEqual($term->id(), $tid, 'Taxonomy term can be created based on the IDs.');
  }

}
