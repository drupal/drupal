<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TaxonomyTestBase.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class TaxonomyTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  function setUp() {
    parent::setUp();

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
      'vid' => drupal_strtolower($this->randomName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => array(
        'value' => $this->randomName(),
        // Use the first available text format.
        'format' => $format->format,
      ),
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }
}
