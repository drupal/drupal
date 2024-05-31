<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel;

use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\navigation\Plugin\Block\NavigationMenuBlock;
use Drupal\system\Entity\Menu;
use Drupal\system\Tests\Routing\MockRouteProvider;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests \Drupal\navigation\Plugin\Block\NavigationMenuBlock.
 *
 * @group navigation
 * @see \Drupal\navigation\Plugin\Derivative\SystemMenuNavigationBlock
 * @see \Drupal\navigation\Plugin\Block\NavigationMenuBlock
 */
class NavigationMenuMarkupTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
    $this->installEntitySchema('menu_link_content');

    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
    $this->linkTree = $this->container->get('menu.link_tree');
    $this->blockManager = $this->container->get('plugin.manager.block');

    $routes = new RouteCollection();
    $requirements = ['_access' => 'TRUE'];
    $options = ['_access_checks' => ['access_check.default']];
    $routes->add('example1', new Route('/example1', [], $requirements, $options));
    $routes->add('example2', new Route('/example2', [], $requirements, $options));
    $routes->add('example3', new Route('/example3', [], $requirements, $options));

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
    // - 1
    // - 2
    //   - 3
    // phpcs:disable
    $links = [
      1 => MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'title 1', 'parent' => '', 'weight' => 0]),
      2 => MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example2', 'title' => 'Another title', 'parent' => '', 'route_parameters' => ['foo' => 'bar'], 'weight' => 1]),
      3 => MenuLinkMock::create(['id' => 'test.example3', 'route_name' => 'example3', 'title' => 'A menu link', 'parent' => 'test.example2', 'weight' => 2]),
    ];
    // phpcs:enable
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
  }

  /**
   * Tests the generated HTML markup.
   */
  public function testToolbarButtonAttributes(): void {
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
      "//li[contains(@class,'toolbar-block__list-item')]/a[@data-index-text='t']",
      "//li[contains(@class,'toolbar-block__list-item')]/a[@data-icon-text='ti']",
      "//li[contains(@class,'toolbar-block__list-item')]/button[@data-index-text='a']",
      "//li[contains(@class,'toolbar-block__list-item')]/button[@data-icon-text='An']",
      "//li[contains(@class,'toolbar-menu__item--level-1')]/a[@data-index-text='a']",
      "//li[contains(@class,'toolbar-menu__item--level-1')]/a[not(@data-icon-text)]",
    ];
    foreach ($items_query as $query) {
      $span = $xpath->query($query);
      $this->assertEquals(1, $span->length, $query);
    }
  }

}
