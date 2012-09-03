<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\DisplayTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the basic display plugin.
 */
class DisplayTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Display',
      'description' => 'Tests the basic display plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Tests the overriding of filter_groups.
   */
  function testFilterGroupsOverriding() {
    $view = $this->createViewFromConfig('test_filter_groups');
    $view->initDisplay();

    // mark is as overridden, yes FALSE, means overridden.
    $view->display['page']->handler->setOverride('filter_groups', FALSE);
    $this->assertFalse($view->display['page']->handler->isDefaulted('filter_groups'), "Take sure that 'filter_groups' is marked as overridden.");
    $this->assertFalse($view->display['page']->handler->isDefaulted('filters'), "Take sure that 'filters'' is marked as overridden.");
  }

}
