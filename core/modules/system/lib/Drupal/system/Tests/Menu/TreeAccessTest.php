<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\TreeAccessTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\menu_link\Entity\MenuLink;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the access check for menu tree using both menu links and route items.
 */

class TreeAccessTest extends DrupalUnitTestBase {

  /**
   * A list of menu links used for this test.
   *
   * @var array
   */
  protected $links;

  /**
   * The route collection used for this test.
   *
   * @var\ \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_link');

  public static function getInfo() {
    return array(
      'name' => 'Menu tree access',
      'description' => 'Tests the access check for menu tree using both menu links and route items.',
      'group' => 'Menu',
    );
  }

  /**
   * Overrides \Drupal\simpletest\DrupalUnitTestBase::containerBuild().
   */
  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);

    $route_collection = $this->getTestRouteCollection();

    $container->register('router.route_provider', 'Drupal\system\Tests\Routing\MockRouteProvider')
      ->addArgument($route_collection);
  }

  /**
   * Generates the test route collection.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns the test route collection.
   */
  protected function getTestRouteCollection() {
    if (!isset($this->routeCollection)) {
      $route_collection = new RouteCollection();
      $route_collection->add('menu_test_1', new Route('/menu_test/test_1',
        array(
          '_controller' => '\Drupal\menu_test\TestController::test'
        ),
        array(
          '_access' => 'TRUE'
        )
      ));
      $route_collection->add('menu_test_2', new Route('/menu_test/test_2',
        array(
          '_controller' => '\Drupal\menu_test\TestController::test'
        ),
        array(
          '_access' => 'FALSE'
        )
      ));
      $this->routeCollection = $route_collection;
    }

    return $this->routeCollection;
  }

  /**
   * Tests access check for menu links with a route item.
   */
  public function testRouteItemMenuLinksAccess() {
    // Add the access checkers to the route items.
    $this->container->get('access_manager')->setChecks($this->getTestRouteCollection());

    // Setup the links with the route items.
    $this->links = array(
      new MenuLink(array('mlid' => 1, 'route_name' => 'menu_test_1', 'depth' => 1, 'link_path' => 'menu_test/test_1'), 'menu_link'),
      new MenuLink(array('mlid' => 2, 'route_name' => 'menu_test_2', 'depth' => 1, 'link_path' => 'menu_test/test_2'), 'menu_link'),
    );

    // Build the menu tree and check access for all of the items.
    $tree = menu_tree_data($this->links);
    menu_tree_check_access($tree);

    $this->assertEqual(count($tree), 1, 'Ensure that just one menu link got access.');
    $item = reset($tree);
    $this->assertEqual($this->links[0], $item['link'], 'Ensure that the right link got access');
  }

}
