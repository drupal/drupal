<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Installer\InstallerRedirectTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Exception handler to determine if an exception indicates an uninstalled site.
 */
class ExceptionDetectNeedsInstallSubscriber implements EventSubscriberInterface {
  use InstallerRedirectTrait;

  /**
   * The default database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new ExceptionDetectNeedsInstallSubscriber.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The default database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($this->shouldRedirectToInstaller($exception, $this->connection)) {
      // Only redirect if this is an HTML response (i.e., a user trying to view
      // the site in a web browser before installing it).
      $request = $event->getRequest();
      $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());
      if ($format == 'html') {
        $event->setResponse(new RedirectResponse($request->getBasePath() . '/core/install.php', 302, ['Cache-Control' => 'no-cache']));
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::EXCEPTION][] = ['onException', 100];
    return $events;
  }

}
