<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TaxonomyTestBase.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
class TaxonomyTestBase extends WebTestBase {

  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'taxonomy';
    parent::setUp($modules);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }

  /**
   * Returns a new vocabulary with random properties.
   */
  function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'machine_name' => drupal_strtolower($this->randomName()),
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => array('article' => 'article'),
      'weight' => mt_rand(0, 10),
    ));
    taxonomy_vocabulary_save($vocabulary);
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      // Use the first available text format.
      'format' => db_query_range('SELECT format FROM {filter_format}', 0, 1)->fetchField(),
      'vid' => $vocabulary->vid,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));
    taxonomy_term_save($term);
    return $term;
  }
}
