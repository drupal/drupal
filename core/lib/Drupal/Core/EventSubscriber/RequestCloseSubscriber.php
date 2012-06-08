<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\RequestCloseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for all responses.
 */
class RequestCloseSubscriber implements EventSubscriberInterface {

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
    global $user;

    module_invoke_all('exit');

    // Commit the user session, if needed.
    drupal_session_commit();
    $response = $event->getResponse();
    $config = config('system.performance');

    if ($config->get('cache') && ($cache = drupal_page_set_cache())) {
      drupal_serve_page_from_cache($cache);
    }
    else {
      ob_flush();
    }

    _registry_check_code(REGISTRY_WRITE_LOOKUP_CACHE);
    drupal_cache_system_paths();
    module_implements_write_cache();
    system_run_automated_cron();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onTerminate');

    return $events;
  }
}
