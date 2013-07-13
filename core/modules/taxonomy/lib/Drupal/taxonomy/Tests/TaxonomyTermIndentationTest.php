<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyTermIndentationTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Testing term indentation functionality in term list page.
 */
class TaxonomyTermIndentationTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term indentation',
      'description' => 'Ensure that the term indentation works properly.',
      'group' => 'Taxonomy',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Tests term indentation.
   */
  function testTermIndentation() {
    // Create three taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $term3 = $this->createTerm($this->vocabulary);

    // Indent the second term under the first one.
    $edit = array(
      'terms[tid:' . $term2->id() . ':0][term][tid]' => 2,
      'terms[tid:' . $term2->id() . ':0][term][parent]' => 1,
      'terms[tid:' . $term2->id() . ':0][term][depth]' => 1,
      'terms[tid:' . $term2->id() . ':0][weight]' => 1,
    );

    // Submit the edited form and check for HTML indentation element presence.
    $this->drupalPost('admin/structure/taxonomy/manage/' . $this->vocabulary->get('vid'), $edit, t('Save'));
    $this->assertPattern('|<div class="indentation">&nbsp;</div>|');
  }

}

