<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Error;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Log exceptions without further handling.
 */
class ExceptionLoggingSubscriber implements EventSubscriberInterface {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new ExceptionLoggingSubscriber.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Log 403 errors.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();
    $this->logger->get('access denied')->warning('@uri', ['@uri' => $request->getRequestUri()]);
  }

  /**
   * Log 404 errors.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();
    $this->logger->get('page not found')->warning('@uri', ['@uri' => $request->getRequestUri()]);
  }

  /**
   * Log not-otherwise-specified errors, including HTTP 500.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onError(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $error = Error::decodeException($exception);
    $this->logger->get('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file).', $error);

    $is_critical = !$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500;
    if ($is_critical) {
      error_log(sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    }
  }

  /**
   * Log all exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();

    $method = 'onError';

    // Treat any non-HTTP exception as if it were one, so we log it the same.
    if ($exception instanceof HttpExceptionInterface) {
      $possible_method = 'on' . $exception->getStatusCode();
      if (method_exists($this, $possible_method)) {
        $method = $possible_method;
      }
    }

    $this->$method($event);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 50];
    return $events;
  }

}
