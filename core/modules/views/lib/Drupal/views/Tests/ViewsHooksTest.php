<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsHooksTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests that views hooks are registered when defined in $module.views.inc.
 *
 * @see views_hook_info().
 * @see field_hook_info().
 */
class ViewsHooksTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_test_data');

  public static function getInfo() {
    return array(
      'name' => 'Views Hooks',
      'description' => 'Tests that views hooks are registered when defined in $module.views.inc.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the hooks.
   */
  public function testHooks() {
    $hooks = &drupal_static(__FUNCTION__);

    $views_hooks = array(
      'views_data',
      'views_data_alter',
      'views_query_substitutions',
      'views_form_substitutions',
      'field_views_data',
      'field_views_data_alter',
    );

    foreach ($views_hooks as $hook) {
      $implementations = module_implements($hook);
      $this->assertTrue(in_array('views_test_data', $implementations), format_string('The hook @hook was registered.', array('@hook' => $hook)));

      // Reset the module implements cache, so we ensure that the .views.inc
      // file is loaded actively.
      unset($hooks['implementations']);
    }
  }

}
