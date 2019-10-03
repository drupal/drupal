<?php

namespace Drupal\Tests\tracker\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group tracker
 * @group legacy
 */
class TrackerLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'tracker',
    'history',
    'node',
    'node_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installSchema('tracker', 'tracker_node');
    $this->installSchema('tracker', 'tracker_user');
  }

  /**
   * @expectedDeprecation tracker_page is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\tracker\Controller\TrackerController::buildContent() instead. See https://www.drupal.org/node/3030645
   */
  public function testDeprecatedTrackerPage() {
    module_load_include('inc', 'tracker', 'tracker.pages');
    $this->assertNotEmpty(tracker_page());
  }

}
