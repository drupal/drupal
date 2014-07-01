<?php

/**
 * @file
 * Contains \Drupal\menu_link\Tests\MenuActiveTrailTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the active menu trail service.
 *
 * @group Drupal
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
   * The mocked menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Menu\MenuActiveTrail',
      'description' => '',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->requestStack = new RequestStack();
    $this->menuLinkManager = $this->getMock('Drupal\Core\Menu\MenuLinkManagerInterface');

    $this->menuActiveTrail = new MenuActiveTrail($this->menuLinkManager, $this->requestStack);
  }

  /**
   * Provides test data for all test methods.
   */
  public function provider() {
    $data = array();

    $request = (new Request());
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'baby_llama');
    $request->attributes->set('_raw_variables', new ParameterBag(array()));

    $link_1 = MenuLinkMock::create(array('id' => 'baby_llama_link_1', 'route_name' => 'baby_llama', 'title' => 'Baby llama', 'parent' => 'mama_llama_link'));
    $link_2 = MenuLinkMock::create(array('id' => 'baby_llama_link_2', 'route_name' => 'baby_llama', 'title' => 'Baby llama', 'parent' => 'papa_llama_link'));

    // @see \Drupal\Core\Menu\MenuLinkManagerInterface::getParentIds()
    $link_1_parent_ids = array('baby_llama_link_1', 'mama_llama_link', '');
    $empty_active_trail = array('');

    $link_1__active_trail_cache_key = 'menu_trail.baby_llama_link_1|mama_llama_link|';
    $empty_active_trail_cache_key = 'menu_trail.';

    // No active link is returned when zero links match the current route.
    $data[] = array($request, array(), $this->randomName(), NULL, $empty_active_trail, $empty_active_trail_cache_key);

    // The first (and only) matching link is returned when one link matches the
    // current route.
    $data[] = array($request, array('baby_llama_link_1' => $link_1), $this->randomName(), $link_1, $link_1_parent_ids, $link_1__active_trail_cache_key);

    // The first of multiple matching links is returned when multiple links
    // match the current route, where "first" is determined by sorting by key.
    $data[] = array($request, array('baby_llama_link_1' => $link_1, 'baby_llama_link_2' => $link_2), $this->randomName(), $link_1, $link_1_parent_ids, $link_1__active_trail_cache_key);
    $data[] = array($request, array('baby_llama_link_2' => $link_2, 'baby_llama_link_1' => $link_1), $this->randomName(), $link_1, $link_1_parent_ids, $link_1__active_trail_cache_key);

    // No active link is returned in case of a 403.
    $request = (new Request());
    $request->attributes->set('_exception_statuscode', 403);
    $data[] = array($request, FALSE, $this->randomName(), NULL, $empty_active_trail, $empty_active_trail_cache_key);

    // No active link is returned when the route name is missing.
    $request = (new Request());
    $data[] = array($request, FALSE, $this->randomName(), NULL, $empty_active_trail, $empty_active_trail_cache_key);

    return $data;
  }

  /**
   * Tests getActiveLink().
   *
   * @covers ::getActiveLink()
   * @dataProvider provider
   */
  public function testGetActiveLink(Request $request, $links, $menu_name, $expected_link) {
    $this->requestStack->push($request);
    if ($links !== FALSE) {
      $this->menuLinkManager->expects($this->exactly(2))
        ->method('loadLinksbyRoute')
        ->will($this->returnValue($links));
    }
    // Test with menu name.
    $this->assertSame($expected_link, $this->menuActiveTrail->getActiveLink($menu_name));
    // Test without menu name.
    $this->assertSame($expected_link, $this->menuActiveTrail->getActiveLink());
  }

  /**
   * Tests getActiveTrailIds().
   *
   * @covers ::getActiveTrailIds()
   * @covers ::getActiveTrailCacheKey()
   * @dataProvider provider
   */
  public function testGetActiveTrailIds(Request $request, $links, $menu_name, $expected_link, $expected_trail, $expected_cache_key) {
    $expected_trail_ids = array_combine($expected_trail, $expected_trail);

    $this->requestStack->push($request);
    if ($links !== FALSE) {
      $this->menuLinkManager->expects($this->exactly(2))
        ->method('loadLinksbyRoute')
        ->will($this->returnValue($links));
      if ($expected_link !== NULL) {
        $this->menuLinkManager->expects($this->exactly(2))
          ->method('getParentIds')
          ->will($this->returnValueMap(array(
            array($expected_link->getPluginId(), $expected_trail_ids),
          )));
      }
    }

    $this->assertSame($expected_trail_ids, $this->menuActiveTrail->getActiveTrailIds($menu_name));
    $this->assertSame($expected_cache_key, $this->menuActiveTrail->getActiveTrailCacheKey($menu_name));
  }

}
