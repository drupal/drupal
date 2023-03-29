<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Batch;

use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the BatchNegotiator.
 *
 * @group Batch
 */
class BatchNegotiatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * Test that the negotiator applies to the batch route.
   */
  public function testApplies() {
    $request = Request::create('/batch');
    // Use the router to enhance the object so that a RouteMatch can be created.
    $this->container->get('router')->matchRequest($request);
    $routeMatch = RouteMatch::createFromRequest($request);
    // The negotiator under test.
    $negotiator = $this->container->get('theme.negotiator.system.batch');

    $this->assertTrue($negotiator->applies($routeMatch));
  }

}
