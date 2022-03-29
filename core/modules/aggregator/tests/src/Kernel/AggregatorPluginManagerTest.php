<?php

namespace Drupal\Tests\aggregator\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aggregator plugin manager.
 *
 * @group aggregator
 * @group legacy
 */
class AggregatorPluginManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['aggregator', 'aggregator_test'];

  /**
   * Tests that the fetcher info alter hook works.
   */
  public function testFetcherInfoAlter() {
    $widget_definition = \Drupal::service('plugin.manager.aggregator.fetcher')->getDefinition('aggregator_test_fetcher');

    // Test if hook_aggregator_fetcher_info_alter is being called.
    $this->assertTrue($widget_definition['definition_altered'], "The 'aggregator_test_fetcher' plugin definition was updated in `hook_aggregator_fetcher_info_alter()`");
  }

  /**
   * Tests that the fetcher info alter hook works.
   */
  public function testParserInfoAlter() {
    $widget_definition = \Drupal::service('plugin.manager.aggregator.parser')->getDefinition('aggregator_test_parser');

    // Test if hook_aggregator_parser_info_alter is being called.
    $this->assertTrue($widget_definition['definition_altered'], "The 'aggregator_test_parser' plugin definition was updated in `hook_aggregator_parser_info_alter()`");
  }

  /**
   * Tests that the fetcher info alter hook works.
   */
  public function testProcessorInfoAlter() {
    $widget_definition = \Drupal::service('plugin.manager.aggregator.processor')->getDefinition('aggregator_test_processor');

    // Test if hook_aggregator_processor_info_alter is being called.
    $this->assertTrue($widget_definition['definition_altered'], "The 'aggregator_test_processor' plugin definition was updated in `hook_aggregator_processor_info_alter()`");
  }

}
