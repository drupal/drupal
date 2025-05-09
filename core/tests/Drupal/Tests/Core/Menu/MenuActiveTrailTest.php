<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests the active menu trail service.
 *
 * @group Menu
 *
 * @coversDefaultClass \Drupal\Core\Menu\MenuActiveTrail
 */
class MenuActiveTrailTest extends UnitTestCase {

  /**
   * The tested active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrail
   */
  protected $menuActiveTrail;

  /**
   * The test request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The mocked menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $menuLinkManager;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;


  /**
   * The mocked path matcher.
   */
  protected PathMatcherInterface $pathMatcher;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheTagsInvalidatorInterface|MockObject $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = new RequestStack();
    $this->currentRouteMatch = new CurrentRouteMatch($this->requestStack);
    $this->menuLinkManager = $this->createMock('Drupal\Core\Menu\MenuLinkManagerInterface');
    $this->cache = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->createMock('\Drupal\Core\Lock\LockBackendInterface');
    $this->pathMatcher = $this->createMock('\Drupal\Core\Path\PathMatcherInterface');
    $this->cacheTagsInvalidator = $this->createMock('\Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $this->menuActiveTrail = new MenuActiveTrail($this->menuLinkManager, $this->currentRouteMatch, $this->cache, $this->lock, $this->pathMatcher);

    $container = new Container();
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * Provides test data for all test methods.
   *
   * @return array
   *   Returns a list of test data of which each is an array containing the
   *   following elements:
   *     - request: A request object.
   *     - links: An array of menu links keyed by ID.
   *     - menu_name: The active menu name.
   *     - expected_link: The expected active link for the given menu.
   *     - expected_trail: The expected active trail for the given menu.
   */
  public static function provider() {
    $data = [];

    $mock_route = new Route('');

    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'baby_llama');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $mock_route);
    $request->attributes->set('_raw_variables', new InputBag([]));

    $link_1 = MenuLinkMock::create(['id' => 'baby_llama_link_1', 'route_name' => 'baby_llama', 'title' => 'Baby llama', 'parent' => 'mama_llama_link']);
    $link_2 = MenuLinkMock::create(['id' => 'baby_llama_link_2', 'route_name' => 'baby_llama', 'title' => 'Baby llama', 'parent' => 'papa_llama_link']);

    // @see \Drupal\Core\Menu\MenuLinkManagerInterface::getParentIds()
    $link_1_parent_ids = ['baby_llama_link_1', 'mama_llama_link', ''];
    $empty_active_trail = [''];

    // No active link is returned when zero links match the current route.
    $data[] = [$request, [], Random::machineName(), NULL, $empty_active_trail];

    // The first (and only) matching link is returned when one link matches the
    // current route.
    $data[] = [$request, ['baby_llama_link_1' => $link_1], Random::machineName(), $link_1, $link_1_parent_ids];

    // The first of multiple matching links is returned when multiple links
    // match the current route, where "first" is determined by sorting by key.
    $data[] = [$request, ['baby_llama_link_1' => $link_1, 'baby_llama_link_2' => $link_2], Random::machineName(), $link_1, $link_1_parent_ids];

    // No active link is returned in case of a 403.
    $request = new Request();
    $request->attributes->set('_exception_statuscode', 403);
    $data[] = [$request, FALSE, Random::machineName(), NULL, $empty_active_trail];

    // No active link is returned when the route name is missing.
    $request = new Request();
    $data[] = [$request, FALSE, Random::machineName(), NULL, $empty_active_trail];

    return $data;
  }

  /**
   * Tests getActiveLink().
   *
   * @covers ::getActiveLink
   * @dataProvider provider
   */
  public function testGetActiveLink(Request $request, $links, $menu_name, $expected_link): void {
    $this->requestStack->push($request);
    if ($links !== FALSE) {
      $this->menuLinkManager->expects($this->exactly(2))
        ->method('loadLinksByRoute')
        ->with('baby_llama')
        ->willReturn($links);
    }
    // Test with menu name.
    $this->assertSame($expected_link, $this->menuActiveTrail->getActiveLink($menu_name));
    // Test without menu name.
    $this->assertSame($expected_link, $this->menuActiveTrail->getActiveLink());
  }

  /**
   * Tests that getActiveLink() returns a <front> route link for a route that is the front page and has no other links.
   *
   * @covers ::getActiveLink
   */
  public function testGetActiveLinkReturnsFrontPageLinkAtTheFrontPage(): void {

    // Mock the request.
    $mock_route = new Route('');
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'link_1');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $mock_route);
    $request->attributes->set('_raw_variables', new InputBag([]));
    $this->requestStack->push($request);

    // Pretend that the current path is the front page.
    $this->pathMatcher
      ->method('isFrontPage')
      ->willReturn(TRUE);

    // Make 'link_1' route to have no links and the '<front>' route to have a link.
    $home_link = MenuLinkMock::create(['id' => 'home_link', 'route_name' => 'home_link', 'title' => 'Home', 'parent' => NULL]);
    $this->menuLinkManager
      ->method('loadLinksByRoute')
      ->willReturnCallback(function ($route_name) use ($home_link) {
        return match ($route_name) {
          'link_1' => [],
          '<front>' => [$home_link],
        };
      });

    // Test.
    $this->assertSame($home_link, $this->menuActiveTrail->getActiveLink());

  }

  /**
   * Tests getActiveTrailIds().
   *
   * @covers ::getActiveTrailIds
   * @dataProvider provider
   */
  public function testGetActiveTrailIds(Request $request, $links, $menu_name, $expected_link, $expected_trail): void {
    $expected_trail_ids = array_combine($expected_trail, $expected_trail);

    $this->requestStack->push($request);
    if ($links !== FALSE) {
      // We expect exactly two calls, one for the first call, and one after the
      // cache clearing below.
      $this->menuLinkManager->expects($this->exactly(3))
        ->method('loadLinksByRoute')
        ->with('baby_llama')
        ->willReturn($links);
      if ($expected_link !== NULL) {
        $this->menuLinkManager->expects($this->exactly(3))
          ->method('getParentIds')
          ->willReturnMap([
            [$expected_link->getPluginId(), $expected_trail_ids],
          ]);
      }
    }

    // Call out the same twice in order to ensure that static caching works.
    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds($menu_name));
    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds($menu_name));

    $this->cacheTagsInvalidator->expects($this->exactly(2))
      ->method('invalidateTags')
      ->willReturnCallback(fn($tags) =>
        match($tags) {
          ['config:system.menu.' . $menu_name] => NULL,
          ['config:system.menu.' . $menu_name, 'config:system.menu.' . $menu_name, 'config:menu_list', 'menu_link_content_list'] => NULL,
        }
      );
    $this->menuActiveTrail->clear();
    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds($menu_name));

    // Test without menu name.
    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds(NULL));
    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds(NULL));
    $this->menuActiveTrail->clear();
  }

  /**
   * Tests getCid()
   *
   * @covers ::getCid
   */
  public function testGetCid(): void {
    $data = $this->provider()[1];
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $data[0];
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $route->setPath('/test/{b}/{a}');
    $request->attributes->get('_raw_variables')->add(['b' => 1, 'a' => 0]);
    $this->requestStack->push($request);

    $this->menuLinkManager->expects($this->any())
      ->method('loadLinksByRoute')
      ->with('baby_llama')
      ->willReturn($data[1]);

    $expected_link = $data[3];
    $expected_trail = $data[4];
    $expected_trail_ids = array_combine($expected_trail, $expected_trail);

    $this->menuLinkManager->expects($this->any())
      ->method('getParentIds')
      ->willReturnMap([
        [$expected_link->getPluginId(), $expected_trail_ids],
      ]);

    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds($data[2]));

    $this->cache->expects($this->once())
      ->method('set')
      // Ensure we normalize the serialized data by sorting them.
      ->with('active-trail:route:baby_llama:route_parameters:' . serialize(['a' => 0, 'b' => 1]));
    $this->lock->expects($this->any())
      ->method('acquire')
      ->willReturn(TRUE);
    $this->menuActiveTrail->destruct();
  }

}
