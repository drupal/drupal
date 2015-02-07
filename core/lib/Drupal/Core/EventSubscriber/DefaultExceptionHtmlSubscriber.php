<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Exception subscriber for handling core default HTML error pages.
 */
class DefaultExceptionHtmlSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new DefaultExceptionHtmlSubscriber.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(HttpKernelInterface $http_kernel, LoggerInterface $logger) {
    $this->httpKernel = $http_kernel;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // A very low priority so that custom handlers are almost certain to fire
    // before it, even if someone forgets to set a priority.
    return -128;
  }

  /**
   * {@inheritDoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, 'system/403', Response::HTTP_FORBIDDEN);
  }

  /**
   * Handles a 404 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, 'system/404', Response::HTTP_NOT_FOUND);
  }

  /**
   * Makes a subrequest to retrieve the default error page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process
   * @param string $path
   *   The path to which to make a subrequest for this error message.
   * @param int $status_code
   *   The status code for the error being handled.
   */
  protected function makeSubrequest(GetResponseForExceptionEvent $event, $path, $status_code) {
    $request = $event->getRequest();

    // @todo Remove dependency on the internal _system_path attribute:
    //   https://www.drupal.org/node/2293523.
    $system_path = $request->attributes->get('_system_path');

    if ($path && $path != $system_path) {
      if ($request->getMethod() === 'POST') {
        $sub_request = Request::create($request->getBaseUrl() . '/' . $path, 'POST', ['destination' => $system_path, '_exception_statuscode' => $status_code] + $request->request->all(), $request->cookies->all(), [], $request->server->all());
      }
      else {
        $sub_request = Request::create($request->getBaseUrl() . '/' . $path, 'GET', $request->query->all() + ['destination' => $system_path, '_exception_statuscode' => $status_code], $request->cookies->all(), [], $request->server->all());
      }

      try {
        // Persist the 'exception' attribute to the subrequest.
        $sub_request->attributes->set('exception', $request->attributes->get('exception'));

        $response = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        $response->setStatusCode($status_code);
        $event->setResponse($response);
      }
      catch (\Exception $e) {
        // If an error happened in the subrequest we can't do much else. Instead,
        // just log it. The DefaultExceptionSubscriber will catch the original
        // exception and handle it normally.
        $error = Error::decodeException($e);
        $this->logger->log($error['severity_level'], '%type: !message in %function (line %line of %file).', $error);
      }
    }
  }

}
