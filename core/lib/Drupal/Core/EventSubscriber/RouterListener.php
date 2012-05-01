<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\EventListener\RouterListener as SymfonyRouterListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Description of RouterListener
 *
 * @author crell
 */
class RouterListener extends SymfonyRouterListener {

  protected $urlMatcher;
  protected $logger;

  public function __construct(UrlMatcherInterface $urlMatcher, LoggerInterface $logger = null) {
    parent::__construct($urlMatcher, $logger);
    $this->urlMatcher = $urlMatcher;
    $this->logger = $logger;
  }

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
      //$parameters = $this->urlMatcher->match($request->getPathInfo());
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
