<?php

/**
 * @file
 * Contains Drupal\url_alter_test\PathSubscriber.
 */

namespace Drupal\url_alter_test;

use Drupal\Core\EventSubscriber\PathListenerBase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Path subscriber for url_alter_test.
 */
class PathSubscriber extends PathListenerBase implements EventSubscriberInterface {

  /**
   * Resolve the system path based on some arbitrary rules.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestPathResolve(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);
    // Rewrite user/username to user/uid.
    if (preg_match('!^user/([^/]+)(/.*)?!', $path, $matches)) {
      if ($account = user_load_by_name($matches[1])) {
        $matches += array(2 => '');
        $path = 'user/' . $account->uid . $matches[2];
      }
    }

    // Rewrite community/ to forum/.
    if ($path == 'community' || strpos($path, 'community/') === 0) {
      $path = 'forum' . substr($path, 9);
    }

    if ($path == 'url-alter-test/bar') {
      $path = 'url-alter-test/foo';
    }

    $this->setPath($request, $path);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestPathResolve', 100);
    return $events;
  }
}
