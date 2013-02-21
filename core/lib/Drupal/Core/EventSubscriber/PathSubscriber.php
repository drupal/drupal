<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\PathSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\CacheDecorator\AliasManagerCacheDecorator;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Access subscriber for controller requests.
 */
class PathSubscriber extends PathListenerBase implements EventSubscriberInterface {

  protected $aliasManager;
  protected $pathProcessor;

  public function __construct(AliasManagerCacheDecorator $alias_manager, InboundPathProcessorInterface $path_processor) {
    $this->aliasManager = $alias_manager;
    $this->pathProcessor = $path_processor;
  }

  /**
   * Converts the request path to a system path.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestConvertPath(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = trim($request->getPathInfo(), '/');
    $path = $this->pathProcessor->processInbound($path, $request);
    $request->attributes->set('system_path', $path);
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $this->aliasManager->setCacheKey($path);
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
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestConvertPath', 200);
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminate', 200);
    return $events;
  }
}
