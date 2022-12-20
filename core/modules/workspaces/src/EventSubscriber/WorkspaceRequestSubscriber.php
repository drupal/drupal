<?php

namespace Drupal\workspaces\EventSubscriber;

use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CacheableRouteProviderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a event subscriber for setting workspace-specific cache keys.
 */
class WorkspaceRequestSubscriber implements EventSubscriberInterface {

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new WorkspaceRequestSubscriber instance.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(AliasManagerInterface $alias_manager, CurrentPathStack $current_path, RouteProviderInterface $route_provider, WorkspaceManagerInterface $workspace_manager) {
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
    $this->routeProvider = $route_provider;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * Sets the cache key on the alias manager cache decorator.
   *
   * KernelEvents::CONTROLLER is used in order to be executed after routing.
   *
   * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
   *   The Event to process.
   */
  public function onKernelController(ControllerEvent $event) {
    // Set the cache key on the alias manager cache decorator.
    if ($event->isMainRequest() && $this->workspaceManager->hasActiveWorkspace()) {
      $cache_key = $this->workspaceManager->getActiveWorkspace()->id() . ':' . rtrim($this->currentPath->getPath($event->getRequest()), '/');
      $this->aliasManager->setCacheKey($cache_key);
    }
  }

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
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    // Use a priority of 190 in order to run after the generic core subscriber.
    // @see \Drupal\Core\EventSubscriber\PathSubscriber::getSubscribedEvents()
    $events[KernelEvents::CONTROLLER][] = ['onKernelController', 190];

    // Use a priority of 33 in order to run before Symfony's router listener.
    // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 33];

    return $events;
  }

}
