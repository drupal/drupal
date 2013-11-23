<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\EfqTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Tests the functionality of EntityQueryInterface for taxonomy entities.
 */
class EfqTest extends TaxonomyTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Taxonomy entity query',
      'description' => 'Verifies operation of a taxonomy-based Entity Query.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Tests that a basic taxonomy entity query works.
   */
  function testTaxonomyEfq() {
    $terms = array();
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($this->vocabulary);
      $terms[$term->id()] = $term;
    }
    $result = \Drupal::entityQuery('taxonomy_term')->execute();
    sort($result);
    $this->assertEqual(array_keys($terms), $result, 'Taxonomy terms were retrieved by entity query.');
    $tid = reset($result);
    $ids = (object) array(
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $this->vocabulary->id(),
    );
    $term = _field_create_entity_from_ids($ids);
    $this->assertEqual($term->id(), $tid, 'Taxonomy term can be created based on the IDs.');

    // Create a second vocabulary and five more terms.
    $vocabulary2 = $this->createVocabulary();
    $terms2 = array();
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($vocabulary2);
      $terms2[$term->id()] = $term;
    }

    $result = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary2->id())
      ->execute();
    sort($result);
    $this->assertEqual(array_keys($terms2), $result, format_string('Taxonomy terms from the %name vocabulary were retrieved by entity query.', array('%name' => $vocabulary2->name)));
    $tid = reset($result);
    $ids = (object) array(
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tid,
      'bundle' => $vocabulary2->id(),
    );
    $term = _field_create_entity_from_ids($ids);
    $this->assertEqual($term->id(), $tid, 'Taxonomy term can be created based on the IDs.');
  }
}
