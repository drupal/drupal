<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\navigation\EntityRouteHelper.
 *
 * @see \Drupal\navigation\EntityRouteHelper
 */
#[Group('navigation')]
#[RunTestsInSeparateProcesses]
class NavigationEntityRouteHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'navigation',
    'layout_builder',
    'user',
  ];

  /**
   * Tests entity route helper when the route does not exist.
   *
   * @see \Drupal\navigation\EntityRouteHelper::getContentEntityFromRoute
   * @see \Drupal\navigation\EntityRouteHelper::isContentEntityFromRoute
   */
  public function testContentEntityFromRouteWithNonExistentRoute(): void {
    $request = Request::create('/does-not-exist');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertNull($this->container->get('navigation.entity_route_helper')->getContentEntityFromRoute());
    $this->assertFalse($this->container->get('navigation.entity_route_helper')->isContentEntityRoute());
  }

}
