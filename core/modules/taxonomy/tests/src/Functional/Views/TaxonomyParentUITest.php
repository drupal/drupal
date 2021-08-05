<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\views\Tests\ViewTestData;
use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Tests views taxonomy parent plugin UI.
 *
 * @group taxonomy
 * @see Drupal\taxonomy\Plugin\views\access\Role
 */
class TaxonomyParentUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_parent'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy', 'taxonomy_test_views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);
  }

  /**
   * Tests the taxonomy parent plugin UI.
   */
  public function testTaxonomyParentUI() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_parent/default/relationship/parent');
    $this->assertSession()->pageTextNotContains('The handler for this item is broken or missing.');
  }

}
