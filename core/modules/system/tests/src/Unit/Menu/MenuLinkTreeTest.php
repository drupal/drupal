<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Unit\Menu\MenuLinkTreeTest.
 */

namespace Drupal\Tests\system\Unit\Menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Tests\Core\Menu\MenuLinkMock;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Menu\MenuLinkTree
 * @group Menu
 */
class MenuLinkTreeTest extends UnitTestCase {

  /**
   * The tested menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->menuLinkTree = new MenuLinkTree(
      $this->getMock('\Drupal\Core\Menu\MenuTreeStorageInterface'),
      $this->getMock('\Drupal\Core\Menu\MenuLinkManagerInterface'),
      $this->getMock('\Drupal\Core\Routing\RouteProviderInterface'),
      $this->getMock('\Drupal\Core\Menu\MenuActiveTrailInterface'),
      $this->getMock('\Drupal\Core\Controller\ControllerResolverInterface')
    );

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::build
   *
   * MenuLinkTree::build() gathers both:
   * 1. the tree's access cacheability: the cacheability of the access result
   *    of checking a link in a menu tree's access. Callers can opt out of
   *    this by MenuLinkTreeElement::access to NULL (the default) value, in
   *    which case the menu link is always visible. Only when an
   *    AccessResultInterface object is specified, we gather this cacheability
   *    metadata.
   *    This means there are three cases:
   *    a. no access result (NULL): menu link is visible
   *    b. AccessResultInterface object that is allowed: menu link is visible
   *    c. AccessResultInterface object that is not allowed: menu link is
   *       invisible, but cacheability metadata is still applicable
   * 2. the tree's menu links' cacheability: the cacheability of a menu link
   *    itself, because it may be dynamic. For this reason, MenuLinkInterface
   *    extends CacheableDependencyInterface. It allows any menu link plugin to
   *    mark itself as uncacheable (max-age=0) or dynamic (by specifying cache
   *    tags and/or contexts), to indicate the extent of dynamism.
   *    This means there are two cases:
   *    a. permanently cacheable, no cache tags, no cache contexts
   *    b. anything else: non-permanently cacheable, and/or cache tags, and/or
   *       cache contexts.
   *
   * Finally, there are four important shapes of trees, all of which we want to
   * test:
   * 1. the empty tree
   * 2. a single-element tree
   * 3. a single-level tree (>1 element; just 1 element is case 2)
   * 4. a multi-level tree
   *
   * The associated data provider aims to test the handling of both of these
   * types of cacheability, and for all four tree shapes, for each of the types
   * of values for the two types of cacheability.
   *
   * There is another level of cacheability involved when actually rendering
   * built menu trees (i.e. when invoking RendererInterface::render() on the
   * return value of MenuLinkTreeInterface::build()): the cacheability of the
   * generated URLs.
   * Fortunately, that doesn't need additional test coverage here because that
   * cacheability is handled at the level of the Renderer (i.e. menu.html.twig
   * template's link() function invocation). It also has its own test coverage.
   *
   * @see \Drupal\menu_link_content\Tests\MenuLinkContentCacheabilityBubblingTest
   *
   * @dataProvider providerTestBuildCacheability
   */
  public function testBuildCacheability($description, $tree, $expected_build, $access, array $access_cache_contexts = []) {
    if ($access !== NULL) {
      $access->addCacheContexts($access_cache_contexts);
    }
    $build = $this->menuLinkTree->build($tree);
    sort($expected_build['#cache']['contexts']);
    $this->assertEquals($expected_build, $build, $description);
  }

  /**
   * Provides the test cases to test for ::testBuildCacheability().
   *
   * As explained in the documentation for ::testBuildCacheability(), this
   * generates 1 + (3 * 2 * 3) = 19 test cases.
   *
   * @see testBuildCacheability
   */
  public function providerTestBuildCacheability() {
    $base_expected_build_empty = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    $base_expected_build = [
      '#cache' => [
        'contexts' => [],
        'tags' => [
          'config:system.menu.mock',
        ],
        'max-age' => Cache::PERMANENT,
      ],
      '#sorted' => TRUE,
      '#theme' => 'menu__mock',
      '#items' => [
        // To be filled when generating test cases, using $get_built_element().
      ]
    ];

    $get_built_element = function(MenuLinkTreeElement $element) {
      $return = [
        'attributes' => new Attribute(),
        'title' => $element->link->getTitle(),
        'url' => new Url($element->link->getRouteName(), $element->link->getRouteParameters(), ['set_active_class' => TRUE]),
        'below' => [],
        'original_link' => $element->link,
        'is_expanded' => FALSE,
        'is_collapsed' => FALSE,
        'in_active_trail' => FALSE,
      ];

      if ($element->hasChildren && !empty($element->subtree)) {
        $return['is_expanded'] = TRUE;
      }
      elseif ($element->hasChildren) {
        $return['is_collapsed'] = TRUE;
      }
      if ($element->inActiveTrail) {
        $return['in_active_trail'] = TRUE;
      }

      return $return;
    };

    // The three access scenarios described in this method's documentation.
    $access_scenarios = [
      [NULL, []],
      [AccessResult::allowed(), ['access:allowed']],
      [AccessResult::neutral(), ['access:neutral']],
    ];

    // The two links scenarios described in this method's documentation.
    $cache_defaults = ['cache_max_age' => Cache::PERMANENT, 'cache_tags' => []];
    $links_scenarios = [
      [
        MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'Example 1']),
        MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example1', 'title' => 'Example 2', 'metadata' => ['cache_contexts' => ['llama']] + $cache_defaults]),
      ],
      [
        MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'Example 1', 'metadata' => ['cache_contexts' => ['foo']] + $cache_defaults]),
        MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example1', 'title' => 'Example 2', 'metadata' => ['cache_contexts' => ['bar']] + $cache_defaults]),
      ],
    ];


    $data = [];

    // Empty tree.
    $data[] = [
      'description' => 'Empty tree.',
      'tree' => [],
      'expected_build' => $base_expected_build_empty,
      'access' => NULL,
      'access_cache_contexts' => [],
    ];

    for ($i = 0; $i < count($access_scenarios); $i++) {
      list($access, $access_cache_contexts) = $access_scenarios[$i];

      for ($j = 0; $j < count($links_scenarios); $j++) {
        $links = $links_scenarios[$j];

        // Single-element tree.
        $tree = [
          new MenuLinkTreeElement($links[0], FALSE, 0, FALSE, []),
        ];
        $tree[0]->access = $access;
        if ($access === NULL || $access->isAllowed()) {
          $expected_build = $base_expected_build;
          $expected_build['#items']['test.example1'] = $get_built_element($tree[0]);
        }
        else {
          $expected_build = $base_expected_build_empty;
        }
        $expected_build['#cache']['contexts'] = array_merge($expected_build['#cache']['contexts'], $access_cache_contexts, $links[0]->getCacheContexts());
        $data[] = [
          'description' => "Single-item tree; access=$i; link=$j.",
          'tree' => $tree,
          'expected_build' => $expected_build,
          'access' => $access,
          'access_cache_contexts' => $access_cache_contexts,
        ];

        // Single-level tree.
        $tree = [
          new MenuLinkTreeElement($links[0], FALSE, 0, FALSE, []),
          new MenuLinkTreeElement($links[1], FALSE, 0, FALSE, []),
        ];
        $tree[0]->access = $access;
        $expected_build = $base_expected_build;
        if ($access === NULL || $access->isAllowed()) {
          $expected_build['#items']['test.example1'] = $get_built_element($tree[0]);
        }
        $expected_build['#items']['test.example2'] = $get_built_element($tree[1]);
        $expected_build['#cache']['contexts'] = array_merge($expected_build['#cache']['contexts'], $access_cache_contexts, $links[0]->getCacheContexts(), $links[1]->getCacheContexts());
        $data[] = [
          'description' => "Single-level tree; access=$i; link=$j.",
          'tree' => $tree,
          'expected_build' => $expected_build,
          'access' => $access,
          'access_cache_contexts' => $access_cache_contexts,
        ];

        // Multi-level tree.
        $multi_level_root_a = MenuLinkMock::create(['id' => 'test.roota', 'route_name' => 'roota', 'title' => 'Root A']);
        $multi_level_root_b = MenuLinkMock::create(['id' => 'test.rootb', 'route_name' => 'rootb', 'title' => 'Root B']);
        $multi_level_parent_c = MenuLinkMock::create(['id' => 'test.parentc', 'route_name' => 'parentc', 'title' => 'Parent C']);
        $tree = [
          new MenuLinkTreeElement($multi_level_root_a, TRUE, 0, FALSE, [
            new MenuLinkTreeElement($multi_level_parent_c, TRUE, 0, FALSE, [
              new MenuLinkTreeElement($links[0], FALSE, 0, FALSE, []),
            ])
          ]),
          new MenuLinkTreeElement($multi_level_root_b, TRUE, 0, FALSE, [
            new MenuLinkTreeElement($links[1], FALSE, 1, FALSE, [])
          ]),
        ];
        $tree[0]->subtree[0]->subtree[0]->access = $access;
        $expected_build = $base_expected_build;
        $expected_build['#items']['test.roota'] = $get_built_element($tree[0]);
        $expected_build['#items']['test.roota']['below']['test.parentc'] = $get_built_element($tree[0]->subtree[0]);
        if ($access === NULL || $access->isAllowed()) {
          $expected_build['#items']['test.roota']['below']['test.parentc']['below']['test.example1'] = $get_built_element($tree[0]->subtree[0]->subtree[0]);
        }
        $expected_build['#items']['test.rootb'] = $get_built_element($tree[1]);
        $expected_build['#items']['test.rootb']['below']['test.example2'] = $get_built_element($tree[1]->subtree[0]);
        $expected_build['#cache']['contexts'] = array_merge($expected_build['#cache']['contexts'], $access_cache_contexts, $links[0]->getCacheContexts(), $links[1]->getCacheContexts());
        $data[] = [
          'description' => "Multi-level tree; access=$i; link=$j.",
          'tree' => $tree,
          'expected_build' => $expected_build,
          'access' => $access,
          'access_cache_contexts' => $access_cache_contexts,
        ];
      }
    }

    return $data;
  }

}
