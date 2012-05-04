<?php

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\RouterListener;
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\EventListener\RouterListener as SymfonyRouterListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotFoundException;


/**
 * Drupal-specific Router listener.
 *
 * This is the bridge from the kernel to the UrlMatcher.
 */
class RouterListener extends SymfonyRouterListener {

  protected $urlMatcher;
  protected $logger;

  public function __construct(UrlMatcherInterface $urlMatcher, LoggerInterface $logger = null) {
    parent::__construct($urlMatcher, $logger);
    $this->urlMatcher = $urlMatcher;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * This method is nearly identical to the parent, except it passes the
   * $request->attributes->get('system_path') variable to the matcher.
   * That is where Drupal stores its processed, de-aliased, and sanitized
   * internal path.  We also pass the full request object to the URL Matcher,
   * since we want attributes to be available to the matcher and to controllers.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
      $this->urlMatcher->getContext()->fromRequest($request);
      $this->urlMatcher->setRequest($event->getRequest());
    }

    if ($request->attributes->has('_controller')) {
      // routing is already done
      return;
    }

    // add attributes based on the path info (routing)
    try {
      $parameters = $this->urlMatcher->match($request->attributes->get('system_path'));

      if (null !== $this->logger) {
          $this->logger->info(sprintf('Matched route "%s" (parameters: %s)', $parameters['_route'], $this->parametersToString($parameters)));
      }

      $request->attributes->add($parameters);
      unset($parameters['_route']);
      unset($parameters['_controller']);
      $request->attributes->set('_route_params', $parameters);
    }
    catch (ResourceNotFoundException $e) {
      $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getPathInfo());

      throw new NotFoundHttpException($message, $e);
    }
    catch (MethodNotAllowedException $e) {
      $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $request->getPathInfo(), strtoupper(implode(', ', $e->getAllowedMethods())));

      throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
    }
  }

}
