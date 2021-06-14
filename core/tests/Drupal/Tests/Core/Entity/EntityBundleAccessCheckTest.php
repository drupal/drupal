<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityBundleAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test of entity access checking system.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityBundleAccessCheck
 *
 * @group Access
 * @group Entity
 * @group legacy
 */
class EntityBundleAccessCheckTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Data provider.
   */
  public function getBundleAndAccessResult() {
    return [
      [
        'article',
        'node:article',
        AccessResult::allowed(),
      ],
      [
        'page',
        'node:article',
        AccessResult::neutral('The entity bundle does not match the route _entity_bundles requirement.'),
      ],
      [
        'page',
        'node:article|page',
        AccessResult::allowed(),
      ],
      [
        'article',
        'node:article|page',
        AccessResult::allowed(),
      ],
      [
        'book_page',
        'node:article|page',
        AccessResult::neutral('The entity bundle does not match the route _entity_bundles requirement.'),
      ],
    ];
  }

  /**
   * @covers ::access
   *
   * @dataProvider getBundleAndAccessResult
   */
  public function testRouteAccess($bundle, $access_requirement, $access_result) {
    $route = new Route('/foo/{node}', [], ['_entity_bundles' => $access_requirement], ['parameters' => ['node' => ['type' => 'entity:node']]]);
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->prophesize(AccountInterface::class)->reveal();

    /** @var \Drupal\node\NodeInterface|\Prophecy\Prophecy\ObjectProphecy $node */
    $node = $this->prophesize(NodeInterface::class);
    $node->bundle()->willReturn($bundle);
    $node->getCacheContexts()->willReturn([]);
    $node->getCacheTags()->willReturn([]);
    $node->getCacheMaxAge()->willReturn(-1);
    $node = $node->reveal();

    /** @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy $route_match */
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getRawParameters()->willReturn(new ParameterBag(['node' => 1]));
    $route_match->getParameters()->willReturn(new ParameterBag(['node' => $node]));
    $route_match = $route_match->reveal();

    $access_check = new EntityBundleAccessCheck();
    $this->expectDeprecation('The Drupal\Core\Entity\EntityBundleAccessCheck is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Specify the list of bundles in the entity parameter, under "bundle" key, as a sequence, instead. See https://www.drupal.org/node/3155569');
    $this->assertEquals($access_result, $access_check->access($route, $route_match, $account));
  }

}
