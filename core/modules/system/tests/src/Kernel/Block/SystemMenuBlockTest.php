<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Block;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Entity\Menu;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests \Drupal\system\Plugin\Block\SystemMenuBlock.
 *
 * @todo Expand test coverage to all SystemMenuBlock functionality, including
 *   block_menu_delete().
 *
 * @see \Drupal\system\Plugin\Derivative\SystemMenuBlock
 * @see \Drupal\system\Plugin\Block\SystemMenuBlock
 */
#[Group('Block')]
#[RunTestsInSeparateProcesses]
class SystemMenuBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'block',
    'menu_test',
    'menu_link_content',
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
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
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
  protected function setUp(): void {
    parent::setUp();
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
      1 => MenuLinkMock::createMock([
        'id' => 'test.example1',
        'route_name' => 'example1',
        'title' => 'foo',
        'parent' => '',
        'weight' => 0,
      ]),
      2 => MenuLinkMock::createMock([
        'id' => 'test.example2',
        'route_name' => 'example2',
        'title' => 'bar',
        'parent' => '',
        'route_parameters' => ['foo' => 'bar'],
        'weight' => 1,
      ]),
      3 => MenuLinkMock::createMock([
        'id' => 'test.example3',
        'route_name' => 'example3',
        'title' => 'baz',
        'parent' => 'test.example2',
        'weight' => 2,
      ]),
      4 => MenuLinkMock::createMock([
        'id' => 'test.example4',
        'route_name' => 'example4',
        'title' => 'qux',
        'parent' => 'test.example3',
        'weight' => 3,
      ]),
      5 => MenuLinkMock::createMock([
        'id' => 'test.example5',
        'route_name' => 'example5',
        'title' => 'title5',
        'parent' => '',
        'expanded' => TRUE,
        'weight' => 4,
      ]),
      6 => MenuLinkMock::createMock([
        'id' => 'test.example6',
        'route_name' => '',
        'url' => 'https://www.drupal.org/',
        'title' => 'bar_bar',
        'parent' => '',
        'weight' => 5,
      ]),
      7 => MenuLinkMock::createMock([
        'id' => 'test.example7',
        'route_name' => 'example7',
        'title' => 'title7',
        'parent' => 'test.example5',
        'weight' => 6,
      ]),
      8 => MenuLinkMock::createMock([
        'id' => 'test.example8',
        'route_name' => 'example8',
        'title' => 'title8',
        'parent' => '',
        'weight' => 7,
      ]),
    ];
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
  }

  /**
   * Tests calculation of a system menu block's configuration dependencies.
   */
  public function testSystemMenuBlockConfigDependencies(): void {

    $block = Block::create([
      'plugin' => 'system_menu_block:' . $this->menu->id(),
      'region' => 'footer',
      'id' => 'machine_name',
      'theme' => 'stark',
    ]);

    $dependencies = $block->calculateDependencies()->getDependencies();
    $expected = [
      'config' => [
        'system.menu.' . $this->menu->id(),
      ],
      'module' => [
        'system',
      ],
      'theme' => [
        'stark',
      ],
    ];
    $this->assertSame($expected, $dependencies);
  }

  /**
   * Tests the config start level and depth.
   */
  public function testConfigLevelDepth(): void {
    // Helper function to generate a configured block instance.
    $place_block = function ($level, $depth) {
      return $this->blockManager->createInstance('system_menu_block:' . $this->menu->id(), [
        'region' => 'footer',
        'id' => 'machine_name',
        'theme' => 'stark',
        'level' => $level,
        'depth' => $depth,
      ]);
    };

    // All the different block instances we're going to test.
    $blocks = [
      'all' => $place_block(1, NULL),
      'level_1_only' => $place_block(1, 1),
      'level_2_only' => $place_block(2, 1),
      'level_3_only' => $place_block(3, 1),
      'level_1_and_beyond' => $place_block(1, NULL),
      'level_2_and_beyond' => $place_block(2, NULL),
      'level_3_and_beyond' => $place_block(3, NULL),
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
    $no_active_trail_expectations['level_2_only'] = [];
    $no_active_trail_expectations['level_3_only'] = [];
    $no_active_trail_expectations['level_1_and_beyond'] = $no_active_trail_expectations['all'];
    $no_active_trail_expectations['level_2_and_beyond'] = $no_active_trail_expectations['level_2_only'];
    $no_active_trail_expectations['level_3_and_beyond'] = [];
    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = $block_build['#items'] ?? [];
      $this->assertSame($no_active_trail_expectations[$id], $this->convertBuiltMenuToIdTree($items), "Menu block $id with no active trail renders the expected tree.");
    }

    // Scenario 2: test all block instances when there's an active trail.
    $route = $this->container->get('router.route_provider')->getRouteByName('example3');
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'example3');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->setSession(new Session(new MockArraySessionStorage()));
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
        ],
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
    ];
    $active_trail_expectations['level_3_only'] = [
      'test.example4' => [],
    ];
    $active_trail_expectations['level_1_and_beyond'] = $active_trail_expectations['all'];
    $active_trail_expectations['level_2_and_beyond'] = [
      'test.example3' => [
        'test.example4' => [],
      ],
    ];
    $active_trail_expectations['level_3_and_beyond'] = $active_trail_expectations['level_3_only'];
    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = $block_build['#items'] ?? [];
      $this->assertSame($active_trail_expectations[$id], $this->convertBuiltMenuToIdTree($items), "Menu block $id with an active trail renders the expected tree.");
    }
  }

  /**
   * Tests the config expanded option.
   */
  #[DataProvider('configExpandedTestCases')]
  public function testConfigExpanded(string $active_route, int $menu_block_level, array $expected_items, array $expected_active_trail_items): void {
    // Replace the path.matcher service so it always returns FALSE when
    // checking whether a route is the front page. Otherwise, the default
    // service throws an exception when checking routes because all of these
    // are mocked.
    $service_definition = $this->container->getDefinition('path.matcher');
    $service_definition->setClass(StubPathMatcher::class);

    $block = $this->blockManager->createInstance('system_menu_block:' . $this->menu->id(), [
      'region' => 'footer',
      'id' => 'machine_name',
      'theme' => 'stark',
      'level' => $menu_block_level,
      'depth' => NULL,
      'expand_all_items' => TRUE,
    ]);

    $route = $this->container->get('router.route_provider')->getRouteByName($active_route);
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $active_route);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);

    \Drupal::service('menu.active_trail')->clear();
    $block_build = $block->build();
    $items = $block_build['#items'] ?? [];
    $this->assertEquals($expected_items, $this->convertBuiltMenuToIdTree($items));
    $active_trail_items = $this->getActiveTrailItems($items);
    $this->assertEquals($expected_active_trail_items, $active_trail_items);
    $this->assertContains("route.menu_active_trails:{$this->menu->id()}", $block->getCacheContexts());

    $block->setConfigurationValue('ignore_active_trail', TRUE);
    $block_build = $block->build();
    $items = $block_build['#items'] ?? [];
    $this->assertEquals($expected_items, $this->convertBuiltMenuToIdTree($items));
    $active_trail_items = $this->getActiveTrailItems($items);
    // Setting "ignore_active_trail" to TRUE when the menu block level is not 1
    // technically fails configuration validation, but test that logic for
    // adding the active trail cache context is correct regardless.
    if ($menu_block_level === 1) {
      $this->assertEmpty($active_trail_items);
      $this->assertNotContains("route.menu_active_trails:{$this->menu->id()}", $block->getCacheContexts());
    }
    else {
      $this->assertEquals($expected_active_trail_items, $active_trail_items);
      $this->assertContains("route.menu_active_trails:{$this->menu->id()}", $block->getCacheContexts());
    }
  }

  /**
   * @return array
   *   An array of test cases for the config expanded option.
   */
  public static function configExpandedTestCases() {
    return [
      'All levels' => [
        'example5',
        1,
        [
          'test.example1' => [],
          'test.example2' => [
            'test.example3' => [
              'test.example4' => [],
            ],
          ],
          'test.example5' => [
            'test.example7' => [],
          ],
          'test.example6' => [],
          'test.example8' => [],
        ],
        ['test.example5'],
      ],
      'All levels viewed from second level in "example 5" branch' => [
        'example7',
        1,
        [
          'test.example1' => [],
          'test.example2' => [
            'test.example3' => [
              'test.example4' => [],
            ],
          ],
          'test.example5' => [
            'test.example7' => [],
          ],
          'test.example6' => [],
          'test.example8' => [],
        ],
        ['test.example5', 'test.example7'],
      ],
      'Level two in "example 5" branch' => [
        'example5',
        2,
        [
          'test.example7' => [],
        ],
        [],
      ],
      'Level three in "example 5" branch' => [
        'example5',
        3,
        [],
        [],
      ],
      'Level three in "example 4" branch' => [
        'example4',
        3,
        [
          'test.example4' => [],
        ],
        ['test.example4'],
      ],
    ];
  }

  /**
   * Tests configuration schema validation for IgnoreActiveTrail constraint.
   */
  #[DataProvider('providerIgnoreActiveTrailConstraint')]
  public function testIgnoreActiveTrailConstraint(int $level, int $depth, bool $expand_all_items, ?bool $ignore_active_trail, bool $expect_exception): void {
    if ($expect_exception) {
      $this->expectException(SchemaIncompleteException::class);
      $this->expectExceptionMessage('Schema errors for block.block.machine_name with the following errors: 0 [settings] The &quot;ignore_active_trail&quot; setting on a system menu block cannot be enabled if &quot;level&quot; is greater than 1 or &quot;expand_all_items&quot; is not enabled and &quot;depth&quot; is greater than 1.');
    }
    \Drupal::service('theme_installer')->install(['stark']);
    $settings = [
      'label' => 'Menu block',
      'level' => $level,
      'depth' => $depth,
      'expand_all_items' => $expand_all_items,
    ];
    if ($ignore_active_trail) {
      $settings['ignore_active_trail'] = TRUE;
    }
    /** @var \Drupal\block\BlockInterface $block */
    $block = Block::create([
      'id' => 'machine_name',
      'theme' => 'stark',
      'plugin' => 'system_menu_block:' . $this->menu->id(),
      'region' => 'footer',
      'settings' => $settings,
    ]);
    $block->save();
  }

  /**
   * Provider for testIgnoreActiveTrailConstraint().
   *
   * @return array[]
   *   Array of test cases for the IgnoreActiveTrail constraint.
   */
  public static function providerIgnoreActiveTrailConstraint(): array {
    return [
      'Valid ignoring active trail' => [
        1,
        1,
        TRUE,
        TRUE,
        FALSE,
      ],
      'Valid not ignoring active trail' => [
        1,
        1,
        TRUE,
        NULL,
        FALSE,
      ],
      'Invalid level ignoring active trail' => [
        2,
        1,
        TRUE,
        TRUE,
        TRUE,
      ],
      'Invalid expand_all_items ignoring active trail' => [
        2,
        1,
        FALSE,
        TRUE,
        TRUE,
      ],
      'Invalid depth > 1' => [
        1,
        2,
        FALSE,
        TRUE,
        TRUE,
      ],
    ];
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

  /**
   * Helper method to get the IDs of the menu items in the active trail.
   *
   * @param array $items
   *   The #items from the return value of MenuLinkTree::build().
   *
   * @return list<string>
   *   List of menu item IDs in the active trail.
   */
  protected function getActiveTrailItems(array $items): array {
    $active_trail_items = [];
    foreach ($items as $key => $item) {
      if ($item['in_active_trail']) {
        $active_trail_items[] = $key;
        if ($item['below']) {
          $active_trail_items = array_merge($active_trail_items, $this->getActiveTrailItems($item['below']));
        }
      }
    }
    return $active_trail_items;
  }

}
