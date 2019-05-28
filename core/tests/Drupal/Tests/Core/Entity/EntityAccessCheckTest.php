<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test of entity access checking system.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityAccessCheck
 *
 * @group Access
 * @group Entity
 */
class EntityAccessCheckTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the method for checking access to routes.
   */
  public function testAccess() {
    $route = new Route('/foo/{var_name}', [], ['_entity_access' => 'var_name.update'], ['parameters' => ['var_name' => ['type' => 'entity:node']]]);
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->prophesize(AccountInterface::class)->reveal();

    /** @var \Drupal\node\NodeInterface|\Prophecy\Prophecy\ObjectProphecy $route_match */
    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account, TRUE)->willReturn(AccessResult::allowed());
    $node = $node->reveal();

    /** @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy $route_match */
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRawParameters()->willReturn(new ParameterBag(['var_name' => 1]));
    $route_match->getParameters()->willReturn(new ParameterBag(['var_name' => $node]));
    $route_match = $route_match->reveal();

    $access_check = new EntityAccessCheck();
    $this->assertEquals(AccessResult::allowed(), $access_check->access($route, $route_match, $account));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithTypePlaceholder() {
    $route = new Route('/foo/{entity_type}/{var_name}', [], ['_entity_access' => 'var_name.update'], ['parameters' => ['var_name' => ['type' => 'entity:{entity_type}']]]);
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->prophesize(AccountInterface::class)->reveal();

    /** @var \Drupal\node\NodeInterface|\Prophecy\Prophecy\ObjectProphecy $node */
    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account, TRUE)->willReturn(AccessResult::allowed());
    $node = $node->reveal();

    /** @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy $route_match */
    $route_match = $this->createRouteMatchForObject($node);

    $access_check = new EntityAccessCheck();
    $this->assertEquals(AccessResult::allowed(), $access_check->access($route, $route_match, $account));
  }

  /**
   * @covers ::access
   */
  public function testAccessWithDifferentRouteParameters() {
    $route = new Route(
      '/foo/{var_name}',
      [],
      ['_entity_access' => 'var_name.update'],
      ['parameters' => ['var_name' => ['type' => 'entity:node']]]
    );
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $access_check = new EntityAccessCheck();

    // Confirm an EntityInterface route parameter's ::access() is called.
    /** @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy $node */
    $node = $this->prophesize(EntityInterface::class);
    $node->access('update', $account, TRUE)->willReturn(AccessResult::allowed());
    $route_match = $this->createRouteMatchForObject($node->reveal());
    $this->assertEquals(AccessResult::allowed(), $access_check->access($route, $route_match, $account));

    // AccessibleInterface is not entity-like: ::access() should not be called.
    /** @var \Drupal\Core\Access\AccessibleInterface|\Prophecy\Prophecy\ObjectProphecy $node */
    $node = $this->prophesize(AccessibleInterface::class);
    $node->access('update', $account, TRUE)->willReturn(AccessResult::allowed());
    $route_match = $this->createRouteMatchForObject($node->reveal());
    $this->assertEquals(AccessResult::neutral(), $access_check->access($route, $route_match, $account));
  }

  /**
   * Wrap any object with a route match, and return that.
   *
   * @param object $object
   *   Any object, including prophesized mocks based on interfaces.
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   A prophesized RouteMatchInterface.
   */
  private function createRouteMatchForObject(\stdClass $object) {
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRawParameters()->willReturn(new ParameterBag(['entity_type' => 'node', 'var_name' => 1]));
    $route_match->getParameters()->willReturn(new ParameterBag(['entity_type' => 'node', 'var_name' => $object]));
    return $route_match->reveal();
  }

}
