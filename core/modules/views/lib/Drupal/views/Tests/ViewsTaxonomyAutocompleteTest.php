<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsTaxonomyAutocompleteTest.
 */

namespace Drupal\views\Tests;

use Drupal\taxonomy\Tests\Views\TaxonomyTestBase;
use Drupal\Component\Utility\MapArray;

/**
 * Tests the views taxonomy complete menu callback.
 *
 * @see views_ajax_autocomplete_taxonomy()
 */
class ViewsTaxonomyAutocompleteTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'View taxonomy autocomplete',
      'description' => 'Tests the view taxonomy autocomplete AJAX callback.',
      'group' => 'Views'
    );
  }

  /**
   * Tests the views_ajax_autocomplete_taxonomy() AJAX callback.
   */
  public function testTaxonomyAutocomplete() {
    $this->user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($this->user);
    $base_autocomplete_path = 'admin/views/ajax/autocomplete/taxonomy/' . $this->vocabulary->vid;

    // Test that no terms returns an empty array.
    $this->assertIdentical(array(), $this->drupalGetJSON($base_autocomplete_path));

    // Test a with whole name term.
    $label = $this->term1->label();
    $expected = MapArray::copyValuesToKeys((array) $label);
    $this->assertIdentical($expected, $this->drupalGetJSON($base_autocomplete_path, array('query' => array('q' => $label))));
    // Test a term by partial name.
    $partial = substr($label, 0, 2);
    $this->assertIdentical($expected, $this->drupalGetJSON($base_autocomplete_path, array('query' => array('q' => $partial))));
  }

}
