<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\MenuLinkContentCacheabilityBubblingTest.
 */

namespace Drupal\menu_link_content\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Ensures that rendered menu links bubble the necessary bubbleable metadata
 * for outbound path/route processing.
 *
 * @group menu_link_content
 */
class MenuLinkContentCacheabilityBubblingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'system', 'link', 'outbound_processing_test', 'url_alter_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['url_alias', 'router']);

    // Ensure that the weight of module_link_content is higher than system.
    // @see menu_link_content_install()
    module_set_weight('menu_link_content', 1);
  }

  /**
   * Tests bubbleable metadata of menu links' outbound route/path processing.
   */
  public function testOutboundPathAndRouteProcessing() {
    \Drupal::service('router.builder')->rebuild();

    $request_stack = \Drupal::requestStack();
    /** @var \Symfony\Component\Routing\RequestContext $request_context */
    $request_context = \Drupal::service('router.request_context');

    $request = Request::create('/');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));
    $request_stack->push($request);
    $request_context->fromRequest($request);

    $menu_tree = \Drupal::menuTree();
    $renderer = \Drupal::service('renderer');


    $default_menu_cacheability = (new BubbleableMetadata())
      ->setCacheMaxAge(Cache::PERMANENT)
      ->setCacheTags(['config:system.menu.tools'])
      ->setCacheContexts(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions']);

    User::create(['uid' => 1, 'name' => $this->randomString()])->save();
    User::create(['uid' => 2, 'name' => $this->randomString()])->save();

    // Five test cases, four asserting one outbound path/route processor, and
    // together covering one of each:
    // - no cacheability metadata,
    // - a cache context,
    // - a cache tag,
    // - a cache max-age.
    // Plus an additional test case to verify that multiple links adding
    // cacheability metadata of the same type is working (two links with cache
    // tags).
    $test_cases = [
      // \Drupal\Core\RouteProcessor\RouteProcessorCurrent: 'route' cache context.
      [
        'uri' => 'route:<current>',
        'cacheability' => (new BubbleableMetadata())->setCacheContexts(['route']),
      ],
      // \Drupal\Core\Access\RouteProcessorCsrf: placeholder.
      [
        'uri' => 'route:outbound_processing_test.route.csrf',
        'cacheability' => (new BubbleableMetadata())->setCacheContexts(['session'])->setAttachments(['placeholders' => []]),
      ],
      // \Drupal\Core\PathProcessor\PathProcessorFront: permanently cacheable.
      [
        'uri' => 'internal:/',
        'cacheability' => (new BubbleableMetadata()),
      ],
      // \Drupal\url_alter_test\PathProcessorTest: user entity's cache tags.
      [
        'uri' => 'internal:/user/1',
        'cacheability' => (new BubbleableMetadata())->setCacheTags(User::load(1)->getCacheTags()),
      ],
      [
        'uri' => 'internal:/user/2',
        'cacheability' => (new BubbleableMetadata())->setCacheTags(User::load(2)->getCacheTags()),
      ],
    ];

    // Test each expectation individually.
    foreach ($test_cases as $expectation) {
      $menu_link_content = MenuLinkContent::create([
        'link' => ['uri' => $expectation['uri']],
        'menu_name' => 'tools',
      ]);
      $menu_link_content->save();
      $tree = $menu_tree->load('tools', new MenuTreeParameters());
      $build = $menu_tree->build($tree);
      $renderer->renderRoot($build);

      $expected_cacheability = $default_menu_cacheability->merge($expectation['cacheability']);
      $this->assertEqual($expected_cacheability, BubbleableMetadata::createFromRenderArray($build));

      $menu_link_content->delete();
    }

    // Now test them all together in one menu: the rendered menu's cacheability
    // metadata should be the combination of the cacheability of all links, and
    // thus of all tested outbound path & route processors.
    $expected_cacheability = new BubbleableMetadata();
    foreach ($test_cases as $expectation) {
      $menu_link_content = MenuLinkContent::create([
        'link' => ['uri' => $expectation['uri']],
        'menu_name' => 'tools',
      ]);
      $menu_link_content->save();
      $expected_cacheability = $expected_cacheability->merge($expectation['cacheability']);
    }
    $tree = $menu_tree->load('tools', new MenuTreeParameters());
    $build = $menu_tree->build($tree);
    $renderer->renderRoot($build);
    $expected_cacheability = $expected_cacheability->merge($default_menu_cacheability);
    $this->assertEqual($expected_cacheability, BubbleableMetadata::createFromRenderArray($build));
  }

}
