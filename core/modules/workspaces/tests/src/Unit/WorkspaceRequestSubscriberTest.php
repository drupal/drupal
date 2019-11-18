<?php

namespace Drupal\Tests\workspaces\Unit;

use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CacheableRouteProviderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\workspaces\EventSubscriber\WorkspaceRequestSubscriber;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @coversDefaultClass \Drupal\workspaces\EventSubscriber\WorkspaceRequestSubscriber
 *
 * @group workspace
 */
class WorkspaceRequestSubscriberTest extends UnitTestCase {

  /**
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->aliasManager = $this->prophesize(AliasManagerInterface::class)->reveal();
    $this->currentPath = $this->prophesize(CurrentPathStack::class)->reveal();
    $this->workspaceManager = $this->prophesize(WorkspaceManagerInterface::class);

    $active_workspace = $this->prophesize(WorkspaceInterface::class);
    $active_workspace->id()->willReturn('test');
    $this->workspaceManager->getActiveWorkspace()->willReturn($active_workspace->reveal());
    $this->workspaceManager->hasActiveWorkspace()->willReturn(TRUE);
  }

  /**
   * @covers ::onKernelRequest
   */
  public function testOnKernelRequestWithCacheableRouteProvider() {
    $route_provider = $this->prophesize(CacheableRouteProviderInterface::class);
    $route_provider->addExtraCacheKeyPart('workspace', 'test')->shouldBeCalled();

    // Check that WorkspaceRequestSubscriber::onKernelRequest() calls
    // addExtraCacheKeyPart() on a route provider that implements
    // CacheableRouteProviderInterface.
    $workspace_request_subscriber = new WorkspaceRequestSubscriber($this->aliasManager, $this->currentPath, $route_provider->reveal(), $this->workspaceManager->reveal());
    $event = $this->prophesize(GetResponseEvent::class)->reveal();
    $this->assertNull($workspace_request_subscriber->onKernelRequest($event));
  }

  /**
   * @covers ::onKernelRequest
   */
  public function testOnKernelRequestWithoutCacheableRouteProvider() {
    $route_provider = $this->prophesize(RouteProviderInterface::class);

    // Check that WorkspaceRequestSubscriber::onKernelRequest() doesn't call
    // addExtraCacheKeyPart() on a route provider that does not implement
    // CacheableRouteProviderInterface.
    $workspace_request_subscriber = new WorkspaceRequestSubscriber($this->aliasManager, $this->currentPath, $route_provider->reveal(), $this->workspaceManager->reveal());
    $event = $this->prophesize(GetResponseEvent::class)->reveal();
    $this->assertNull($workspace_request_subscriber->onKernelRequest($event));
  }

}
