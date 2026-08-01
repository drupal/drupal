<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\Routing\Attribute\Route;
use Drupal\KernelTests\KernelTestBase;
use Drupal\router_test\Controller\TestAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests title closures declared on Route attributes.
 *
 * Routes are serialized into the router table and closures cannot be
 * serialized, so these tests load the routes back through the route provider
 * to cover the full round trip rather than the discovered route objects.
 *
 * @see \Drupal\Core\Routing\Attribute\Route
 */
#[CoversClass(Route::class)]
#[Group('Routing')]
#[RunTestsInSeparateProcesses]
class TitleClosureTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['router_test', 'system'];

  /**
   * Tests that a title closure survives being written to the router table.
   */
  public function testTitleClosureRoundTrip(): void {
    $route = $this->container->get('router.route_provider')
      ->getRouteByName('router_test.title_closure');

    // The closure is not stored on the route, only a reference to it.
    $this->assertNull($route->getDefault('_title'));
    $this->assertSame(
      [TestAttributes::class, 'titleClosure', 0],
      $route->getDefault('_title_closure'),
    );

    $request = new Request();
    $request->attributes->set('parameter', 'test');
    $title = $this->container->get('title_resolver')->getTitle($request, $route);
    $this->assertSame('First title for test', (string) $title);
  }

  /**
   * Tests that repeated attributes on one method keep their own closures.
   */
  public function testRepeatedAttributesHaveOwnTitles(): void {
    $route = $this->container->get('router.route_provider')
      ->getRouteByName('router_test.title_closure_other');

    // The second attribute on the method is recorded at index 1.
    $this->assertSame(
      [TestAttributes::class, 'titleClosure', 1],
      $route->getDefault('_title_closure'),
    );

    $request = new Request();
    $request->attributes->set('parameter', 'test');
    $title = $this->container->get('title_resolver')->getTitle($request, $route);
    $this->assertSame('Second title for test', (string) $title);
  }

  /**
   * Tests that a stale reference to a missing attribute is reported.
   */
  public function testStaleReference(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageIs('There is no Route attribute at index 2 on ' . TestAttributes::class . '::titleClosure().');
    Route::getTitleClosure(TestAttributes::class, 'titleClosure', 2);
  }

  /**
   * Tests that a reference to an attribute without a closure is reported.
   */
  public function testReferenceToNonClosureTitle(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageIs('The Route attribute at index 0 on ' . TestAttributes::class . '::allProperties() does not have a closure title.');
    Route::getTitleClosure(TestAttributes::class, 'allProperties', 0);
  }

}
