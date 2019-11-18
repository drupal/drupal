<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a path subscriber that converts path aliases.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\path_alias\EventSubscriber\PathAliasSubscriber instead.
 *
 * @see https://www.drupal.org/node/3092086
 */
class PathSubscriber implements EventSubscriberInterface {

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
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
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(AliasManagerInterface $alias_manager, CurrentPathStack $current_path) {
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;

    // This is used as base class by the new class, so we do not trigger
    // deprecation notices when that or any child class is instantiated.
    $new_class = 'Drupal\path_alias\EventSubscriber\PathAliasSubscriber';
    if (!is_a($this, $new_class) && class_exists($new_class)) {
      @trigger_error('The \\' . __CLASS__ . ' class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \\' . $new_class . '. See https://drupal.org/node/3092086', E_USER_DEPRECATED);
    }
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
