<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsTaxonomyAutocompleteTest.
 */

namespace Drupal\views\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the views taxonomy complete menu callback.
 *
 * @group views
 * @see views_ajax_autocomplete_taxonomy()
 */
class ViewsTaxonomyAutocompleteTest extends ViewTestBase {

  /**
   * The taxonomy vocabulary created for this test.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Stores the first term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * Stores the second term used in the different tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy');

  public function setUp() {
    parent::setUp();

    // Create the vocabulary for the tag field.
    $this->vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ));
    $this->vocabulary->save();

    $this->term1 = $this->createTerm('term');
    $this->term2 = $this->createTerm('another');
  }

  /**
   * Tests the views_ajax_autocomplete_taxonomy() AJAX callback.
   */
  public function testTaxonomyAutocomplete() {
    $this->user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($this->user);
    $base_autocomplete_path = 'taxonomy/autocomplete_vid/' . $this->vocabulary->vid;

    // Test that no terms returns an empty array.
    $this->assertIdentical(array(), $this->drupalGetJSON($base_autocomplete_path));

    // Test a with whole name term.
    $label = $this->term1->getName();
    $expected = array(array(
      'value' => $label,
      'label' => String::checkPlain($label),
    ));
    $this->assertIdentical($expected, $this->drupalGetJSON($base_autocomplete_path, array('query' => array('q' => $label))));
    // Test a term by partial name.
    $partial = substr($label, 0, 2);
    $this->assertIdentical($expected, $this->drupalGetJSON($base_autocomplete_path, array('query' => array('q' => $partial))));
  }

  /**
   * Returns a new term with random properties.
   *
   * @param string $name
   *   (optional) The name of the taxonomy term.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   */
  protected function createTerm($name = NULL) {
    $term = entity_create('taxonomy_term', array(
      'name' => $name ?: $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }

}
