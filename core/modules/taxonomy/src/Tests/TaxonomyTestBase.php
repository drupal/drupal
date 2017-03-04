<?php

namespace Drupal\taxonomy\Tests;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Taxonomy module tests.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\taxonomy\Functional\TaxonomyTestBase instead.
 */
abstract class TaxonomyTestBase extends WebTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

}
