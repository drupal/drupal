<?php

/**
 * @file
 * Contains \Drupal\session_test\EventSubscriber\SessionTestSubscriber.
 */

namespace Drupal\session_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class SessionTestSubscriber implements EventSubscriberInterface {

  /*
   * Stores whether $_SESSION is empty at the beginning of the request.
   */
  protected $emptySession;

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSessionTest(GetResponseEvent $event) {
    // Trigger the authentication in the test to ensure that $_SESSION has the
    // needed data.
    // @todo: On the longrun the session will be lazy initialized, so we no
    // longer have to force it here.
    \Drupal::currentUser()->getAccount();
    $this->emptySession = intval(empty($_SESSION));
  }

  /**
   * Performs tasks for session_test module on kernel.response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function onKernelResponseSessionTest(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceOf RedirectResponse) {
      // Force the redirection to go to a non-secure page after being on a
      // secure page through https.php.
      global $base_insecure_url, $is_https_mock;
      // Alter the redirect to use HTTP when using a mock HTTPS request through
      // https.php because form submissions would otherwise redirect to a
      // non-existent HTTPS site.
      if (!empty($is_https_mock)) {
        $path = $base_insecure_url . '/' . $response->getTargetUrl();
        $response->setTargetUrl($path);
      }
    }
    // Set header for session testing.
    $response->headers->set('X-Session-Empty', $this->emptySession);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onKernelResponseSessionTest', 300);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSessionTest', 100);
    return $events;
  }

}
