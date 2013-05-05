<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\RequestCloseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\CachedModuleHandlerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for all responses.
 */
class RequestCloseSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   */
  function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Performs end of request tasks.
   *
   * @todo The body of this function has just been copied almost verbatim from
   *   drupal_page_footer(). There's probably a lot in here that needs to get
   *   removed/changed. Also, if possible, do more light-weight shutdowns on
   *   AJAX requests.
   *
   * @param Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The Event to process.
   */
  public function onTerminate(PostResponseEvent $event) {
    $request_method = $event->getRequest()->getMethod();
    // Check whether we need to write the module implementations cache. We do
    // not want to cache hooks which are only invoked on HTTP POST requests
    // since these do not need to be optimized as tightly, and not doing so
    // keeps the cache entry smaller.
    if (($request_method == 'GET' || $request_method == 'HEAD') && $this->moduleHandler instanceof CachedModuleHandlerInterface) {
      $this->moduleHandler->writeCache();
    }
    system_run_automated_cron();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onTerminate', 100);

    return $events;
  }
}
