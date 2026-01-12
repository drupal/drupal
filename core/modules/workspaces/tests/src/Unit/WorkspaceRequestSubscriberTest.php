<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Unit;

use Drupal\Core\Routing\CacheableRouteProviderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\workspaces\EventSubscriber\WorkspaceRequestSubscriber;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Tests Drupal\workspaces\EventSubscriber\WorkspaceRequestSubscriber.
 */
#[CoversClass(WorkspaceRequestSubscriber::class)]
#[Group('workspaces')]
class WorkspaceRequestSubscriberTest extends UnitTestCase {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->workspaceManager = $this->prophesize(WorkspaceManagerInterface::class);

    $active_workspace = $this->prophesize(WorkspaceInterface::class);
    $active_workspace->id()->willReturn('test');
    $this->workspaceManager->getActiveWorkspace()->willReturn($active_workspace->reveal());
    $this->workspaceManager->hasActiveWorkspace()->willReturn(TRUE);
  }

  /**
   * Tests on kernel request with cacheable route provider.
   */
  public function testOnKernelRequestWithCacheableRouteProvider(): void {
    $route_provider = $this->prophesize(CacheableRouteProviderInterface::class);
    $route_provider->addExtraCacheKeyPart('workspace', 'test')->shouldBeCalled();

    // Check that WorkspaceRequestSubscriber::onKernelRequest() calls
    // addExtraCacheKeyPart() on a route provider that implements
    // CacheableRouteProviderInterface.
    $workspace_request_subscriber = new WorkspaceRequestSubscriber($route_provider->reveal(), $this->workspaceManager->reveal());
    $event = $this->prophesize(RequestEvent::class)->reveal();
    $this->assertNull($workspace_request_subscriber->onKernelRequest($event));
  }

  /**
   * Tests on kernel request without cacheable route provider.
   */
  public function testOnKernelRequestWithoutCacheableRouteProvider(): void {
    $route_provider = $this->prophesize(RouteProviderInterface::class);

    // Check that WorkspaceRequestSubscriber::onKernelRequest() doesn't call
    // addExtraCacheKeyPart() on a route provider that does not implement
    // CacheableRouteProviderInterface.
    $workspace_request_subscriber = new WorkspaceRequestSubscriber($route_provider->reveal(), $this->workspaceManager->reveal());
    $event = $this->prophesize(RequestEvent::class)->reveal();
    $this->assertNull($workspace_request_subscriber->onKernelRequest($event));
  }

}
