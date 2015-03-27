<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TaxonomyTestBase.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\entity_reference\Tests\EntityReferenceTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class TaxonomyTestBase extends WebTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
