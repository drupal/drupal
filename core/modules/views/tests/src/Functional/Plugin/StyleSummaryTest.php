<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the summary style plugin.
 *
 * @group views
 */
class StyleSummaryTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_summary'];

  /**
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Create 5 entities per bundle, to allow a summary overview per bundle.
    for ($i = 0; $i < 5; $i++) {
      for ($j = 0; $j < 5; $j++) {
        $this->entities[] = $entity = EntityTest::create([
          'name' => 'Entity ' . ($i * 5 + $j),
          'type' => 'type' . $i,
        ]);
        $entity->save();
      }
    }

    $views_user = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($views_user);
  }

  /**
   * Tests a summary view.
   */
  public function testSummaryView() {
    $this->drupalGet('test-summary');

    // Ensure styles are properly added for summary views.
    $this->assertRaw('stable/css/views/views.module.css');

    $summary_list = $this->cssSelect('ul.views-summary li');
    $this->assertCount(4, $summary_list);

    foreach ($summary_list as $summary_list_item) {
      $this->assertEquals('(5)', trim(explode(' ', $summary_list_item->getText())[1]));
    }

    $summary_links = $this->cssSelect('ul.views-summary a');
    $this->assertCount(4, $summary_links);
    foreach ($summary_links as $index => $summary_link) {
      $this->assertEquals('type' . $index, trim($summary_link->getText()));
    }

    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertCount(2, $entries);

    // Add a base path to the summary settings.
    $edit = [
      'options[summary][options][default_summary][base_path]' => 'test-summary',
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Test that the links still work.
    $this->drupalGet('test-summary');
    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertCount(2, $entries);

    // Change the summary display to an unformatted list displaying 3 items.
    $edit = [
      'options[summary][format]' => 'unformatted_summary',
      'options[summary][options][unformatted_summary][override]' => '1',
      'options[summary][options][unformatted_summary][items_per_page]' => '3',
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->drupalGet('test-summary');

    $summary_list = $this->cssSelect('.views-summary-unformatted');
    $this->assertCount(3, $summary_list);

    foreach ($summary_list as $summary_list_item) {
      $this->assertEquals('(5)', trim(explode(' ', $summary_list_item->getText())[1]));
    }

    $summary_links = $this->cssSelect('.views-summary-unformatted a');
    $this->assertCount(3, $summary_links);
    foreach ($summary_links as $index => $summary_link) {
      $this->assertEquals('type' . $index, trim($summary_link->getText()));
    }

    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertCount(2, $entries);

    // Add a base path to the summary settings.
    $edit = [
      'options[summary][options][unformatted_summary][base_path]' => 'test-summary',
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Test that the links still work.
    $this->drupalGet('test-summary');
    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertCount(2, $entries);

    // Set base_path to an unknown path and test that the links lead to the
    // front page.
    $edit = [
      'options[summary][options][unformatted_summary][base_path]' => 'unknown-path',
    ];
    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('test-summary');
    $this->assertSession()->linkByHrefExists('/');
  }

}
