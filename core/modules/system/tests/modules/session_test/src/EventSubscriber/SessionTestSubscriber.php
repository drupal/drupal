<?php

/**
 * @file
 * Contains \Drupal\session_test\EventSubscriber\SessionTestSubscriber.
 */

namespace Drupal\session_test\EventSubscriber;

use Drupal\Core\Session\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class SessionTestSubscriber implements EventSubscriberInterface {

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Stores whether $_SESSION is empty at the beginning of the request.
   *
   * @var bool
   */
  protected $emptySession;

  /**
   * Constructs a new session test subscriber.
   */
  public function __construct(SessionManagerInterface $session_manager) {
    $this->sessionManager = $session_manager;
  }

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSessionTest(GetResponseEvent $event) {
    $this->emptySession = (int) !$this->sessionManager->start();
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
