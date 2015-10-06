<?php

/**
 * @file
 * Contains \Drupal\statistics\Tests\StatisticsAttachedTest.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests if statistics.js is loaded when content is not printed.
 *
 * @group statistics
 */
class StatisticsAttachedTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'statistics');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    // Install "statistics_test_attached" and set it as the default theme.
    $theme = 'statistics_test_attached';
    \Drupal::service('theme_handler')->install(array($theme));
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    // Installing a theme will cause the kernel terminate event to rebuild the
    // router. Simulate that here.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Tests if statistics.js is loaded when content is not printed.
   */
  public function testAttached() {

    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'body' => 'body text'
    ]);
    $node->save();
    $this->drupalGet('node/' . $node->id());

    $this->assertRaw('core/modules/statistics/statistics.js', 'Statistics library is available');
  }

}
