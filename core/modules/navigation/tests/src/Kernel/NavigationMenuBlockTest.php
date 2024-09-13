<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\navigation\Plugin\Block\NavigationMenuBlock;
use Drupal\system\Entity\Menu;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests \Drupal\navigation\Plugin\Block\NavigationMenuBlock.
 *
 * @group navigation
 * @see \Drupal\navigation\Plugin\Derivative\SystemMenuNavigationBlock
 * @see \Drupal\navigation\Plugin\Block\NavigationMenuBlock
 * @todo Expand test coverage to all SystemMenuNavigationBlock functionality,
 * including block_menu_delete().
 */
class NavigationMenuBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'navigation',
    'menu_test',
    'menu_link_content',
    'field',
    'block',
    'user',
    'link',
    'layout_builder',
  ];

  /**
   * The navigation block under test.
   *
   * @var \Drupal\navigation\Plugin\Block\NavigationMenuBlock
   */
  protected $navigationBlock;

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
   * @var \Drupal\Core\Block\BlockManager
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
    $special_options = $options + ['_no_path' => TRUE];
    $routes->add('example2', new Route('/example2', [], $requirements, $options));
    $routes->add('example4', new Route('/example4', [], $requirements, $options));
    $routes->add('example9', new Route('/example9', [], $requirements, $options));
    $routes->add('example11', new Route('/example11', [], $requirements, $options));

    // Mock special routes defined in system.routing.yml.
    $routes->add('<nolink>', new Route('', [], $requirements, $special_options));
    $routes->add('<button>', new Route('', [], $requirements, $special_options));

    // Define our RouteProvider mock.
    $mock_route_provider = new MockRouteProvider($routes);
    $this->container->set('router.route_provider', $mock_route_provider);

    // Define our UrlGenerator service that use the new RouteProvider.
    $url_generator_non_bubbling = new UrlGenerator(
      $mock_route_provider,
      $this->container->get('path_processor_manager'),
      $this->container->get('route_processor_manager'),
      $this->container->get('request_stack'),
      $this->container->getParameter('filter_protocols')
    );
    $url_generator = new MetadataBubblingUrlGenerator($url_generator_non_bubbling, $this->container->get('renderer'));
    $this->container->set('url_generator', $url_generator);

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
    // - 1 (nolink)
    // - 2
    //   - 3 (nolink)
    //     - 4
    //       - 9
    // - 5 (button)
    //   - 7 (button)
    //     - 10 (nolink)
    // - 6
    // - 8 (nolink)
    //   - 11
    //     - 12 (button)
    // With link 6 being the only external link.
    // phpcs:disable
    $links = [
      1 => MenuLinkMock::create(['id' => 'test.example1', 'route_name' => '<nolink>', 'title' => 'title 1', 'parent' => '', 'weight' => 0]),
      2 => MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example2', 'title' => 'title 2', 'parent' => '', 'route_parameters' => ['foo' => 'bar'], 'weight' => 1]),
      3 => MenuLinkMock::create(['id' => 'test.example3', 'route_name' => '<nolink>', 'title' => 'title 3', 'parent' => 'test.example2', 'weight' => 2]),
      4 => MenuLinkMock::create(['id' => 'test.example4', 'route_name' => 'example4', 'title' => 'title 4', 'parent' => 'test.example3', 'weight' => 3]),
      5 => MenuLinkMock::create(['id' => 'test.example5', 'route_name' => '<button>', 'title' => 'title 5', 'parent' => '', 'expanded' => TRUE, 'weight' => 4]),
      6 => MenuLinkMock::create(['id' => 'test.example6', 'route_name' => '', 'url' => 'https://www.drupal.org/', 'title' => 'title 6', 'parent' => '', 'weight' => 5, 'options' => ['attributes' => ['target' => '_blank', 'class' => ['external-link']]]]),
      7 => MenuLinkMock::create(['id' => 'test.example7', 'route_name' => '<button>', 'title' => 'title 7', 'parent' => 'test.example5', 'weight' => 6]),
      8 => MenuLinkMock::create(['id' => 'test.example8', 'route_name' => '<nolink>', 'title' => 'title 8', 'parent' => '', 'weight' => 7]),
      9 => MenuLinkMock::create(['id' => 'test.example9', 'route_name' => 'example9', 'title' => 'title 9', 'parent' => 'test.example4', 'weight' => 7]),
      10 => MenuLinkMock::create(['id' => 'test.example10', 'route_name' => '<nolink>', 'title' => 'title 10', 'parent' => 'test.example7', 'weight' => 7]),
      11 => MenuLinkMock::create(['id' => 'test.example11', 'route_name' => 'example11', 'title' => 'title 11', 'parent' => 'test.example8', 'weight' => 7]),
      12 => MenuLinkMock::create(['id' => 'test.example12', 'route_name' => '<button>', 'title' => 'title 12', 'parent' => 'test.example11', 'weight' => 7]),
    ];
    // phpcs:enable
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
  }

  /**
   * Tests calculation of a system navigation menu block's config dependencies.
   */
  public function testSystemMenuBlockConfigDependencies(): void {
    $block = Block::create([
      'plugin' => 'navigation_menu:' . $this->menu->id(),
      'region' => 'content',
      'id' => 'machine_name',
      'theme' => 'stark',
    ]);

    $dependencies = $block->calculateDependencies()->getDependencies();
    $expected = [
      'config' => [
        'system.menu.' . $this->menu->id(),
      ],
      'module' => [
        'navigation',
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
    // Helper function to generate a configured navigation block instance.
    $place_block = function ($level, $depth) {
      return $this->blockManager->createInstance('navigation_menu:' . $this->menu->id(), [
        'region' => 'content',
        'id' => 'machine_name',
        'level' => $level,
        'depth' => $depth,
      ]);
    };

    // All the different navigation block instances we're going to test.
    $blocks = [
      'level_1_only' => $place_block(1, 0),
      'level_2_only' => $place_block(2, 0),
      'level_3_only' => $place_block(3, 0),
      'level_1_and_beyond' => $place_block(1, NavigationMenuBlock::NAVIGATION_MAX_DEPTH - 1),
      'level_2_and_beyond' => $place_block(2, NavigationMenuBlock::NAVIGATION_MAX_DEPTH - 1),
      'level_3_and_beyond' => $place_block(3, NavigationMenuBlock::NAVIGATION_MAX_DEPTH - 1),
    ];

    // Expectations are independent of the active trail.
    $expectations = [];
    $expectations['level_1_only'] = [
      'test.example1' => [],
      'test.example2' => [],
      'test.example5' => [],
      'test.example6' => [],
      'test.example8' => [],
    ];
    $expectations['level_2_only'] = [
      'test.example3' => [],
      'test.example7' => [],
      'test.example11' => [],
    ];
    $expectations['level_3_only'] = [
      'test.example4' => [],
      'test.example10' => [],
      'test.example12' => [],
    ];
    $expectations['level_1_and_beyond'] = [
      'test.example1' => [],
      'test.example2' => [
        'test.example3' => [
          'test.example4' => [],
        ],
      ],
      'test.example5' => [
        'test.example7' => [
          'test.example10' => [],
        ],
      ],
      'test.example6' => [],
      'test.example8' => [
        'test.example11' => [
          'test.example12' => [],
        ],
      ],
    ];
    $expectations['level_2_and_beyond'] = [
      'test.example3' => [
        'test.example4' => [
          'test.example9' => [],
        ],
      ],
      'test.example7' => [
        'test.example10' => [],
      ],
      'test.example11' => [
        'test.example12' => [],
      ],
    ];
    $expectations['level_3_and_beyond'] = [
      'test.example4' => [
        'test.example9' => [],
      ],
      'test.example10' => [],
      'test.example12' => [],
    ];
    // Scenario 1: test all navigation block instances when there's no active
    // trail.
    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = $block_build['#items'] ?? [];
      $this->assertSame($expectations[$id], $this->convertBuiltMenuToIdTree($items), "Menu block $id with no active trail renders the expected tree.");
    }

    // Scenario 2: test all navigation block instances when there's an active
    // trail.
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

    foreach ($blocks as $id => $block) {
      $block_build = $block->build();
      $items = $block_build['#items'] ?? [];
      $this->assertSame($expectations[$id], $this->convertBuiltMenuToIdTree($items), "Menu navigation block $id with an active trail renders the expected tree.");
    }
  }

  /**
   * Tests the generated HTML markup.
   */
  public function testHtmlMarkup() {
    $block = $this->blockManager->createInstance('navigation_menu:' . $this->menu->id(), [
      'region' => 'content',
      'id' => 'machine_name',
      'level' => 1,
      'depth' => NavigationMenuBlock::NAVIGATION_MAX_DEPTH - 1,
    ]);

    $block_build = $block->build();
    $render = \Drupal::service('renderer')->renderRoot($block_build);

    $dom = new \DOMDocument();
    $dom->loadHTML((string) $render);
    $xpath = new \DOMXPath($dom);

    $items_query = [
      "//li[contains(@class,'toolbar-block__list-item')]/span/span[text()='title 1']",
      "//li[contains(@class,'toolbar-block__list-item')]/button/span[text()='title 2']",
      "//li[contains(@class,'toolbar-menu__item--level-1')]/button/span[text()='title 3']",
      "//li[contains(@class,'toolbar-menu__item--level-2')]/a[text()='title 4']",
      "//li[contains(@class,'toolbar-block__list-item')]/button/span[text()='title 5']",
      "//li[contains(@class,'toolbar-block__list-item')]/a/span[text()='title 6']",
      "//li[contains(@class,'toolbar-block__list-item')]/a[contains(@class, 'external-link')]",
      "//li[contains(@class,'toolbar-block__list-item')]/a[contains(@class, 'external-link')]",
      "//li[contains(@class,'toolbar-block__list-item')]/a[@target='_blank']",
      "//li[contains(@class,'toolbar-menu__item--level-1')]/button/span[text()='title 7']",
      "//li[contains(@class,'toolbar-block__list-item')]/button/span[text()='title 8']",
      "//li[contains(@class,'toolbar-menu__item--level-2')]/span[text()='title 10']",
      "//li[contains(@class,'toolbar-menu__item--level-1')]/button/span[text()='title 11']",
      "//li[contains(@class,'toolbar-menu__item--level-2')]/button[text()='title 12']",
    ];
    foreach ($items_query as $query) {
      $span = $xpath->query($query);
      $this->assertEquals(1, $span->length, $query);
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
  protected function convertBuiltMenuToIdTree(array $build): array {
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
