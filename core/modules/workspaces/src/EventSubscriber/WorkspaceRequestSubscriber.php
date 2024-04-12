<?php

namespace Drupal\workspaces\EventSubscriber;

use Drupal\Core\Routing\CacheableRouteProviderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a event subscriber for setting workspace-specific cache keys.
 *
 * @internal
 */
class WorkspaceRequestSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly RouteProviderInterface $routeProvider,
    protected readonly WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * Adds the active workspace as a cache key part to the route provider.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   An event object.
   */
  public function onKernelRequest(RequestEvent $event) {
    if ($this->workspaceManager->hasActiveWorkspace() && $this->routeProvider instanceof CacheableRouteProviderInterface) {
      $this->routeProvider->addExtraCacheKeyPart('workspace', $this->workspaceManager->getActiveWorkspace()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Use a priority of 33 in order to run before Symfony's router listener.
    // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 33];

    return $events;
  }

}
