<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\EfqTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\entity\EntityFieldQuery;

/**
 * Tests the functionality of EntityFieldQuery for taxonomy entities.
 */
class EfqTest extends TaxonomyTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Taxonomy EntityFieldQuery',
      'description' => 'Verifies operation of a taxonomy-based EntityFieldQuery.',
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
   * Tests that a basic taxonomy EntityFieldQuery works.
   */
  function testTaxonomyEfq() {
    $terms = array();
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($this->vocabulary);
      $terms[$term->tid] = $term;
    }
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'taxonomy_term');
    $result = $query->execute();
    $result = $result['taxonomy_term'];
    asort($result);
    $this->assertEqual(array_keys($terms), array_keys($result), 'Taxonomy terms were retrieved by EntityFieldQuery.');

    // Create a second vocabulary and five more terms.
    $vocabulary2 = $this->createVocabulary();
    $terms2 = array();
    for ($i = 0; $i < 5; $i++) {
      $term = $this->createTerm($vocabulary2);
      $terms2[$term->tid] = $term;
    }

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'taxonomy_term');
    $query->entityCondition('bundle', $vocabulary2->machine_name);
    $result = $query->execute();
    $result = $result['taxonomy_term'];
    asort($result);
    $this->assertEqual(array_keys($terms2), array_keys($result), format_string('Taxonomy terms from the %name vocabulary were retrieved by EntityFieldQuery.', array('%name' => $vocabulary2->name)));
  }
}
