<?php
/**
 * @file
 * Definition of Drupal\views\Tests\PluginTypeListTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\ViewExecutable;
use Drupal\Tests\UnitTestCase;

/**
 * Class for plugin list testing.
 */
class PluginTypeListTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Plugin list',
      'description' => 'Tests that list of plugin is correct.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the plugins list is correct.
   */
  public function testPluginList() {
    $plugin_list = array(
      'access',
      'area',
      'argument',
      'argument_default',
      'argument_validator',
      'cache',
      'display_extender',
      'display',
      'exposed_form',
      'field',
      'filter',
      'join',
      'pager',
      'query',
      'relationship',
      'row',
      'sort',
      'style',
      'wizard',
    );

    $diff = array_diff($plugin_list, ViewExecutable::getPluginTypes());
    $this->assertTrue(empty($diff), 'The plugin list is correct');
  }

}
