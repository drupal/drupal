<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class TaxonomyTestBase extends BrowserTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create Basic page and Article node types if node module is installed.
    if ($this->profile != 'standard') {
      $class = get_class($this);
      $modules = [];
      while ($class) {
        if (property_exists($class, 'modules')) {
          $modules = array_merge($modules, $class::$modules);
        }
        $class = get_parent_class($class);
      }
      if ($modules && in_array('node', $modules)) {
        $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
      }
    }
  }

}
