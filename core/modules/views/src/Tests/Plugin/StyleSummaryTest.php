<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\StyleSummaryTest.
 */

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
  public static $modules = ['entity_test'];

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
  }

}
