<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirects paths containing successive slashes to those with single slashes.
 */
class RedirectLeadingSlashesSubscriber implements EventSubscriberInterface {

  /**
   * Redirects paths containing successive slashes to those with single slashes.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The RequestEvent to process.
   */
  public function redirect(RequestEvent $event) {
    $request = $event->getRequest();
    // Get the requested path minus the base path.
    $path = $request->getPathInfo();

    // It is impossible to create a link or a route to a path starting with
    // multiple leading slashes. However if a form is added to the 404 page that
    // submits back to the same URI this presents an open redirect
    // vulnerability. Also, Drupal 7 renders the same page for
    // http://www.example.org/foo and http://www.example.org////foo.
    if (str_contains($path, '//')) {
      $path = preg_replace('/\/+/', '/', $path);
      $qs = $request->getQueryString();
      if ($qs) {
        $qs = '?' . $qs;
      }
      $event->setResponse(new CacheableRedirectResponse($request->getUriForPath($path) . $qs));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['redirect', 1000];
    return $events;
  }

}
