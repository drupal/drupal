<?php

namespace Drupal\Tests\help\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatch;
use Drupal\help_test\SupernovaGenerator;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the empty HTML page.
 *
 * @group help
 */
class HelpEmptyPageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'help_test', 'user'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->set('url_generator', new SupernovaGenerator());
  }

  /**
   * Ensures that no URL generator is called on a page without hook_help().
   */
  public function testEmptyHookHelp() {
    $all_modules = system_rebuild_module_data();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, already enabled modules and modules in the
      // Testing package.
      if ($module->origin !== 'core' || !empty($module->info['hidden']) || $module->status == TRUE || $module->info['package'] == 'Testing') {
        return FALSE;
      }
      return TRUE;
    });

    $this->enableModules(array_keys($all_modules));
    $this->installEntitySchema('menu_link_content');

    $route = \Drupal::service('router.route_provider')->getRouteByName('<front>');
    \Drupal::service('module_handler')->invokeAll('help', ['<front>', new RouteMatch('<front>', $route)]);
  }

}
