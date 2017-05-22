<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the summary style plugin.
 *
 * @group views
 */
class StyleSummaryTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'views_ui'];

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
  protected function setUp($import_test_views = TRUE) {
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

    $summary_list = $this->cssSelect('ul.views-summary li');
    $this->assertEqual(4, count($summary_list));

    foreach ($summary_list as $summary_list_item) {
      $this->assertEqual('(5)', trim((string) $summary_list_item));
    }

    $summary_links = $this->cssSelect('ul.views-summary a');
    $this->assertEqual(4, count($summary_links));
    foreach ($summary_links as $index => $summary_link) {
      $this->assertEqual('type' . $index, trim((string) $summary_link));
    }

    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertEqual(2, count($entries));

    // Add a base path to the summary settings.
    $edit = [
      'options[summary][options][default_summary][base_path]' => 'test-summary',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_summary/page_1/argument/type', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Test that the links still work.
    $this->drupalGet('test-summary');
    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertEqual(2, count($entries));

    // Change the summary display to an unformatted list displaying 3 items.
    $edit = [
      'options[summary][format]' => 'unformatted_summary',
      'options[summary][options][unformatted_summary][override]' => '1',
      'options[summary][options][unformatted_summary][items_per_page]' => '3',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_summary/page_1/argument/type', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_summary/page_1/argument/type');
    $this->drupalGet('test-summary');

    $summary_list = $this->cssSelect('.views-summary-unformatted');
    $this->assertEqual(3, count($summary_list));

    foreach ($summary_list as $summary_list_item) {
      $this->assertEqual('(5)', trim((string) $summary_list_item));
    }

    $summary_links = $this->cssSelect('.views-summary-unformatted a');
    $this->assertEqual(3, count($summary_links));
    foreach ($summary_links as $index => $summary_link) {
      $this->assertEqual('type' . $index, trim((string) $summary_link));
    }

    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertEqual(2, count($entries));

    // Add a base path to the summary settings.
    $edit = [
      'options[summary][options][unformatted_summary][base_path]' => 'test-summary',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_summary/page_1/argument/type', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Test that the links still work.
    $this->drupalGet('test-summary');
    $this->clickLink('type1');
    $entries = $this->cssSelect('div.view-content div.views-row');
    $this->assertEqual(2, count($entries));

    // Set base_path to an unknown path and test that the links lead to the
    // front page.
    $edit = [
      'options[summary][options][unformatted_summary][base_path]' => 'unknown-path',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_summary/page_1/argument/type', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('test-summary');
    $this->assertLinkByHref('/');
  }

}
