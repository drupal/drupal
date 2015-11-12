<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\DefaultExceptionHtmlSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new DefaultExceptionHtmlSubscriber.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination) {
    $this->httpKernel = $http_kernel;
    $this->logger = $logger;
    $this->redirectDestination = $redirect_destination;
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
   * Handles a 401 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on401(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, Url::fromRoute('system.401')->toString(), Response::HTTP_UNAUTHORIZED);
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, Url::fromRoute('system.403')->toString(), Response::HTTP_FORBIDDEN);
  }

  /**
   * Handles a 404 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, Url::fromRoute('system.404')->toString(), Response::HTTP_NOT_FOUND);
  }

  /**
   * Makes a subrequest to retrieve the default error page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process
   * @param string $url
   *   The path/url to which to make a subrequest for this error message.
   * @param int $status_code
   *   The status code for the error being handled.
   */
  protected function makeSubrequest(GetResponseForExceptionEvent $event, $url, $status_code) {
    $request = $event->getRequest();
    $exception = $event->getException();

    if (!($url && $url[0] == '/')) {
      $url = $request->getBasePath() . '/' . $url;
    }

    $current_url = $request->getBasePath() . $request->getPathInfo();

    if ($url != $request->getBasePath() . '/' && $url != $current_url) {
      if ($request->getMethod() === 'POST') {
        $sub_request = Request::create($url, 'POST', $this->redirectDestination->getAsArray() + ['_exception_statuscode' => $status_code] + $request->request->all(), $request->cookies->all(), [], $request->server->all());
      }
      else {
        $sub_request = Request::create($url, 'GET', $request->query->all() + $this->redirectDestination->getAsArray() + ['_exception_statuscode' => $status_code], $request->cookies->all(), [], $request->server->all());
      }

      try {
        // Persist the 'exception' attribute to the subrequest.
        $sub_request->attributes->set('exception', $request->attributes->get('exception'));
        // Persist the access result attribute to the subrequest, so that the
        // error page inherits the access result of the master request.
        $sub_request->attributes->set(AccessAwareRouterInterface::ACCESS_RESULT, $request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT));

        // Carry over the session to the subrequest.
        if ($session = $request->getSession()) {
          $sub_request->setSession($session);
        }

        $response = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        // Only 2xx responses should have their status code overridden; any
        // other status code should be passed on: redirects (3xx), error (5xx)…
        // @see https://www.drupal.org/node/2603788#comment-10504916
        if ($response->isSuccessful()) {
          $response->setStatusCode($status_code);
        }

        // Persist any special HTTP headers that were set on the exception.
        if ($exception instanceof HttpExceptionInterface) {
          $response->headers->add($exception->getHeaders());
        }

        $event->setResponse($response);
      }
      catch (\Exception $e) {
        // If an error happened in the subrequest we can't do much else. Instead,
        // just log it. The DefaultExceptionSubscriber will catch the original
        // exception and handle it normally.
        $error = Error::decodeException($e);
        $this->logger->log($error['severity_level'], '%type: @message in %function (line %line of %file).', $error);
      }
    }
  }

}
