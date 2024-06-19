<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional\Views;

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
  protected function setUp($import_test_views = TRUE, $modules = ['taxonomy_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests the taxonomy parent plugin UI.
   */
  public function testTaxonomyParentUI(): void {
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_parent/default/relationship/parent');
    $this->assertSession()->pageTextNotContains('The handler for this item is broken or missing.');
  }

}
