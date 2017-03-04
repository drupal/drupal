<?php

namespace Drupal\Tests\system\Kernel\Block;

use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Menu;
use Drupal\block\Entity\Block;
use Drupal\Core\Render\Element;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\user\Entity\User;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests \Drupal\system\Plugin\Block\SystemMenuBlock.
 *
 * @group Block
 * @todo Expand test coverage to all SystemMenuBlock functionality, including
 *   block_menu_delete().
 *
 * @see \Drupal\system\Plugin\Derivative\SystemMenuBlock
 * @see \Drupal\system\Plugin\Block\SystemMenuBlock
 */
class SystemMenuBlockTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'block',
    'menu_test',
    'menu_link_content',
    'field',
    'user',
    'link',
  ];

  /**
   * The block under test.
   *
   * @var \Drupal\system\Plugin\Block\SystemMenuBlock
   */
  protected $block;

  /**
   * The menu for testing.
   *
   * @var \Drupal\system\MenuInterface
   */
  protected $menu;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $linkTree;

  /**
   * The menu link plugin manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');

    $account = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);

    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
    $this->linkTree = $this->container->get('menu.link_tree');
    $this->blockManager = $this->container->get('plugin.manager.block');

    $routes = new RouteCollection();
    $requirements = ['_access' => 'TRUE'];
    $options = ['_access_checks' => ['access_check.default']];
    $routes->add('example1', new Route('/example1', [], $requirements, $options));
    $routes->add('example2', new Route('/example2', [], $requirements, $options));
    $routes->add('example3', new Route('/example3', [], $requirements, $options));
    $routes->add('example4', new Route('/example4', [], $requirements, $options));
    $routes->add('example5', new Route('/example5', [], $requirements, $options));
    $routes->add('example6', new Route('/example6', [], $requirements, $options));
    $routes->add('example7', new Route('/example7', [], $requirements, $options));
    $routes->add('example8', new Route('/example8', [], $requirements, $options));

    $mock_route_provider = new MockRouteProvider($routes);
    $this->container->set('router.route_provider', $mock_route_provider);

    // Add a new custom menu.
    $menu_name = 'mock';
    $label = $this->randomMachineName(16);

    $this->menu = Menu::create([
      'id' => $menu_name,
      'label' => $label,
      'description' => 'Description text',
    ]);
    $this->menu->save();

    // This creates a tree with the following structure:
    // - 1
    // - 2
    //   - 3
    //     - 4
    // - 5
    //   - 7
    // - 6
    // - 8
    // With link 6 being the only external link.
    $links = [
      1 => MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '', 'weight' => 0]),
      2 => MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example2', 'title' => 'bar', 'parent' => '', 'route_parameters' => ['foo' => 'bar'], 'weight' => 1]),
      3 => MenuLinkMock::create(['id' => 'test.example3', 'route_name' => 'example3', 'title' => 'baz', 'parent' => 'test.example2', 'weight' => 2]),
      4 => MenuLinkMock::create(['id' => 'test.example4', 'route_name' => 'example4', 'title' => 'qux', 'parent' => 'test.example3', 'weight' => 3]),
      5 => MenuLinkMock::create(['id' => 'test.example5', 'route_name' => 'example5', 'title' => 'foofoo', 'parent' => '', 'expanded' => TRUE, 'weight' => 4]),
      6 => MenuLinkMock::create(['id' => 'test.example6', 'route_name' => '', 'url' => 'https://www.drupal.org/', 'title' => 'barbar', 'parent' => '', 'weight' => 5]),
      7 => MenuLinkMock::create(['id' => 'test.example7', 'route_name' => 'example7', 'title' => 'bazbaz', 'parent' => 'test.example5', 'weight' => 6]),
      8 => MenuLinkMock::create(['id' => 'test.example8', 'route_name' => 'example8', 'title' => 'quxqux', 'parent' => '', 'weight' => 7]),
    ];
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
  }

  /**
   * Tests calculation of a system menu block's configuration dependencies.
   */
  public function testSystemMenuBlockConfigDependencies() {

    $block = Block::create([
      'plugin' => 'system_menu_block:' . $this->menu->id(),
      'region' => 'footer',
      'id' => 'machinename',
      'theme' => 'stark',
    ]);

    $dependencies = $block->calculateDependencies()->getDependencies();
    $expected = [
      'config' => [
        'system.menu.' . $this->menu->id()
      ],
      'module' => [
        'system'
      ],
      'theme' => [
        'stark'
      ],
    ];
    $this->assertIdentical($expected, $dependencies);
  }

  /**
   * Tests the config start level and depth.
   */
  public function testConfigLevelDepth() {
    // Helper function to generate a configured block instance.
    $place_block = function ($level, $depth) {
      return $this->blockManager->createInstance('system_menu_block:' . $this->menu->id(), [
        'region' => 'footer',
        'id' => 'machinename',
        'theme' => 'stark',
        'level' => $level,
        'depth' => $depth,
      ]);
    };

    // All the different block instances we're going to test.
    $blocks = [
      'all' => $place_block(1, 0),
      'level_1_only' => $place_block(1, 1),
      'level_2_only' => $place_block(2, 1),
      'level_3_only' => $place_block(3, 1),
      'level_1_and_beyond' => $place_block(1, 0),
      'level_2_and_beyond' => $place_block(2, 0),
      'level_3_and_beyond' => $place_block(3, 0),
    ];

    // Scenario 1: test all block instances when there's no active trail.
    $no_active_trail_expectations = [];
    $no_active_trail_expectations['all'] = [
      'test.example1' => [],
      'test.example2' => [],
      'test.example5' => [
        'test.example7' => [],
       ],
      'test.example6' => [],
      'test.example8' => [],
    ];
    $no_active_trail_expectations['level_1_only'] = [
      'test.example1' => [],
      'test.example2' => [],
      'test.example5' => [],
      'test.example6' => [],
      'test.example8' => [],
    ];
    $no_active_trail_expectations['level_2_only'] = [
      'test.example7' => [],
    ];
    $no_active_trail_expectations['level_3_only'] = [];
    $no_active_trail_expectations['level_1_and_beyond'] = $no_active_trail_expectations['all'];
    $no_active_trail_expectations['level_2_and_beyond'] = $no_active_trail_expectations['level_2_only'];
    $no_active_trail_expectations['level_3_and_beyond'] = [];
    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = isset($block_build['#items']) ? $block_build['#items'] : [];
      $this->assertIdentical($no_active_trail_expectations[$id], $this->convertBuiltMenuToIdTree($items), format_string('Menu block %id with no active trail renders the expected tree.', ['%id' => $id]));
    }

    // Scenario 2: test all block instances when there's an active trail.
    $route = $this->container->get('router.route_provider')->getRouteByName('example3');
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'example3');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $this->container->get('request_stack')->push($request);
    // \Drupal\Core\Menu\MenuActiveTrail uses the cache collector pattern, which
    // includes static caching. Since this second scenario simulates a second
    // request, we must also simulate it for the MenuActiveTrail service, by
    // clearing the cache collector's static cache.
    \Drupal::service('menu.active_trail')->clear();

    $active_trail_expectations = [];
    $active_trail_expectations['all'] = [
      'test.example1' => [],
      'test.example2' => [
        'test.example3' => [
          'test.example4' => [],
        ]
      ],
      'test.example5' => [
        'test.example7' => [],
      ],
      'test.example6' => [],
      'test.example8' => [],
    ];
    $active_trail_expectations['level_1_only'] = [
      'test.example1' => [],
      'test.example2' => [],
      'test.example5' => [],
      'test.example6' => [],
      'test.example8' => [],
    ];
    $active_trail_expectations['level_2_only'] = [
      'test.example3' => [],
      'test.example7' => [],
    ];
    $active_trail_expectations['level_3_only'] = [
      'test.example4' => [],
    ];
    $active_trail_expectations['level_1_and_beyond'] = $active_trail_expectations['all'];
    $active_trail_expectations['level_2_and_beyond'] = [
      'test.example3' => [
        'test.example4' => [],
      ],
      'test.example7' => [],
    ];
    $active_trail_expectations['level_3_and_beyond'] = $active_trail_expectations['level_3_only'];
    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = isset($block_build['#items']) ? $block_build['#items'] : [];
      $this->assertIdentical($active_trail_expectations[$id], $this->convertBuiltMenuToIdTree($items), format_string('Menu block %id with an active trail renders the expected tree.', ['%id' => $id]));
    }
  }

  /**
   * Helper method to allow for easy menu link tree structure assertions.
   *
   * Converts the result of MenuLinkTree::build() in a "menu link ID tree".
   *
   * @param array $build
   *   The return value of MenuLinkTree::build()
   *
   * @return array
   *   The "menu link ID tree" representation of the given render array.
   */
  protected function convertBuiltMenuToIdTree(array $build) {
    $level = [];
    foreach (Element::children($build) as $id) {
      $level[$id] = [];
      if (isset($build[$id]['below'])) {
        $level[$id] = $this->convertBuiltMenuToIdTree($build[$id]['below']);
      }
    }
    return $level;
  }

}
