<?php

namespace Drupal\path_alias\EventSubscriber;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a path subscriber that converts path aliases.
 */
class PathAliasSubscriber implements EventSubscriberInterface {

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
   * Constructs a new PathSubscriber instance.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(AliasManagerInterface $alias_manager, CurrentPathStack $current_path) {
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
  }

  /**
   * Sets the cache key on the alias manager cache decorator.
   *
   * KernelEvents::CONTROLLER is used in order to be executed after routing.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The Event to process.
   */
  public function onKernelController(FilterControllerEvent $event) {
    // Set the cache key on the alias manager cache decorator.
    if ($event->isMasterRequest()) {
      $this->aliasManager->setCacheKey(rtrim($this->currentPath->getPath($event->getRequest()), '/'));
    }
  }

  /**
   * Ensures system paths for the request get cached.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    $this->aliasManager->writeCache();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER][] = ['onKernelController', 200];
    $events[KernelEvents::TERMINATE][] = ['onKernelTerminate', 200];
    return $events;
  }

}
