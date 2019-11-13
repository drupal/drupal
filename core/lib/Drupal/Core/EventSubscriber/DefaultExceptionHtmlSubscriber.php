<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

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
   * A router implementation which does not check access.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $accessUnawareRouter;

  /**
   * Constructs a new DefaultExceptionHtmlSubscriber.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   */
  public function __construct(HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router) {
    $this->httpKernel = $http_kernel;
    $this->logger = $logger;
    $this->redirectDestination = $redirect_destination;
    $this->accessUnawareRouter = $access_unaware_router;
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
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 4xx error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on4xx(GetResponseForExceptionEvent $event) {
    if (($exception = $event->getThrowable()) && $exception instanceof HttpExceptionInterface) {
      $this->makeSubrequest($event, '/system/4xx', $exception->getStatusCode());
    }
  }

  /**
   * Handles a 401 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on401(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, '/system/401', Response::HTTP_UNAUTHORIZED);
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, '/system/403', Response::HTTP_FORBIDDEN);
  }

  /**
   * Handles a 404 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $this->makeSubrequest($event, '/system/404', Response::HTTP_NOT_FOUND);
  }

  /**
   * Makes a subrequest to retrieve the default error page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   * @param string $url
   *   The path/url to which to make a subrequest for this error message.
   * @param int $status_code
   *   The status code for the error being handled.
   */
  protected function makeSubrequest(GetResponseForExceptionEvent $event, $url, $status_code) {
    $request = $event->getRequest();
    $exception = $event->getThrowable();

    try {
      // Reuse the exact same request (so keep the same URL, keep the access
      // result, the exception, et cetera) but override the routing information.
      // This means that aside from routing, this is identical to the master
      // request. This allows us to generate a response that is executed on
      // behalf of the master request, i.e. for the original URL. This is what
      // allows us to e.g. generate a 404 response for the original URL; if we
      // would execute a subrequest with the 404 route's URL, then it'd be
      // generated for *that* URL, not the *original* URL.
      $sub_request = clone $request;

      // The routing to the 404 page should be done as GET request because it is
      // restricted to GET and POST requests only. Otherwise a DELETE request
      // would for example trigger a method not allowed exception.
      $request_context = clone ($this->accessUnawareRouter->getContext());
      $request_context->setMethod('GET');
      $this->accessUnawareRouter->setContext($request_context);

      $sub_request->attributes->add($this->accessUnawareRouter->match($url));

      // Add to query (GET) or request (POST) parameters:
      // - 'destination' (to ensure e.g. the login form in a 403 response
      //   redirects to the original URL)
      // - '_exception_statuscode'
      $parameters = $sub_request->isMethod('GET') ? $sub_request->query : $sub_request->request;
      $parameters->add($this->redirectDestination->getAsArray() + ['_exception_statuscode' => $status_code]);

      $response = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
      // Only 2xx responses should have their status code overridden; any
      // other status code should be passed on: redirects (3xx), error (5xx)…
      // @see https://www.drupal.org/node/2603788#comment-10504916
      if ($response->isSuccessful()) {
        $response->setStatusCode($status_code);
      }

      // Persist the exception's cacheability metadata, if any. If the exception
      // itself isn't cacheable, then this will make the response uncacheable:
      // max-age=0 will be set.
      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($exception);
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
