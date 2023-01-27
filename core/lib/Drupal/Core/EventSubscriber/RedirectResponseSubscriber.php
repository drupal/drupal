<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Allows manipulation of the response object when performing a redirect.
 */
class RedirectResponseSubscriber implements EventSubscriberInterface {

  /**
   * The unrouted URL assembler service.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $unroutedUrlAssembler;

  /**
   * The request context.
   */
  protected RequestContext $requestContext;

  /**
   * Constructs a RedirectResponseSubscriber object.
   *
   * @param \Drupal\Core\Utility\UnroutedUrlAssemblerInterface $url_assembler
   *   The unrouted URL assembler service.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(UnroutedUrlAssemblerInterface $url_assembler, RequestContext $request_context) {
    $this->unroutedUrlAssembler = $url_assembler;
    $this->requestContext = $request_context;
  }

  /**
   * Allows manipulation of the response object when performing a redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Event to process.
   */
  public function checkRedirectUrl(ResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $request = $event->getRequest();

      // Let the 'destination' query parameter override the redirect target.
      // If $response is already a SecuredRedirectResponse, it might reject the
      // new target as invalid, in which case proceed with the old target.
      $destination = $request->query->get('destination');
      if ($destination) {
        // The 'Location' HTTP header must always be absolute.
        $destination = $this->getDestinationAsAbsoluteUrl($destination, $request->getSchemeAndHttpHost());
        try {
          $response->setTargetUrl($destination);
        }
        catch (\InvalidArgumentException $e) {
        }
      }

      // Regardless of whether the target is the original one or the overridden
      // destination, ensure that all redirects are safe.
      if (!($response instanceof SecuredRedirectResponse)) {
        try {
          // SecuredRedirectResponse is an abstract class that requires a
          // concrete implementation. Default to LocalRedirectResponse, which
          // considers only redirects to within the same site as safe.
          $safe_response = LocalRedirectResponse::createFromRedirectResponse($response);
          $safe_response->setRequestContext($this->requestContext);
        }
        catch (\InvalidArgumentException $e) {
          // If the above failed, it's because the redirect target wasn't
          // local. Do not follow that redirect. Display an error message
          // instead. We're already catching one exception, so trigger_error()
          // rather than throw another one.
          // We don't throw an exception, because this is a client error rather than a
          // server error.
          $message = 'Redirects to external URLs are not allowed by default, use \Drupal\Core\Routing\TrustedRedirectResponse for it.';
          trigger_error($message, E_USER_ERROR);
          $safe_response = new Response($message, 400);
        }
        $event->setResponse($safe_response);
      }
    }
  }

  /**
   * Converts the passed in destination into an absolute URL.
   *
   * @param string $destination
   *   The path for the destination. In case it starts with a slash it should
   *   have the base path included already.
   * @param string $scheme_and_host
   *   The scheme and host string of the current request.
   *
   * @return string
   *   The destination as absolute URL.
   */
  protected function getDestinationAsAbsoluteUrl($destination, $scheme_and_host) {
    if (!UrlHelper::isExternal($destination)) {
      // The destination query parameter can be a relative URL in the sense of
      // not including the scheme and host, but its path is expected to be
      // absolute (start with a '/'). For such a case, prepend the scheme and
      // host, because the 'Location' header must be absolute.
      if (str_starts_with($destination, '/')) {
        $destination = $scheme_and_host . $destination;
      }
      else {
        // Legacy destination query parameters can be internal paths that have
        // not yet been converted to URLs.
        $destination = UrlHelper::parse($destination);
        $uri = 'base:' . $destination['path'];
        $options = [
          'query' => $destination['query'],
          'fragment' => $destination['fragment'],
          'absolute' => TRUE,
        ];
        // Treat this as if it's user input of a path relative to the site's
        // base URL.
        $destination = $this->unroutedUrlAssembler->assemble($uri, $options);
      }
    }
    return $destination;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['checkRedirectUrl'];
    return $events;
  }

}
