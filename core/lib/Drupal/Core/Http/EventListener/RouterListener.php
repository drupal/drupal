<?php

namespace Drupal\Core\Http\EventListener;

use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Routing\Exception\CacheableResourceNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Sets request attributes based on a matching route.
 *
 * Also initializes the context from the request.
 *
 * In contrast to Symfony's RouterListener this converts a
 * CacheableResourceNoFoundException (thrown by Drupal's Router) into a
 * CacheableNotFoundHttpException. (Symfony already does the conversion of
 * ResourceNoFoundException into NotFoundHttpException.) Additionally, the
 * following functional things are changed (in addition to the code-style) to
 * reduce the complexity:
 * - It does not support URL matchers, only request matchers
 * - It does not support logging
 * - It does not return a welcome response
 *
 * @see \Symfony\Component\HttpKernel\EventListener\RouterListener
 * @see \Drupal\Core\Routing\Exception\CacheableResourceNotFoundException
 * @see \Drupal\Core\Routing\Router::matchRequest()
 */
class RouterListener implements EventSubscriberInterface {

  public function __construct(
    protected RequestMatcherInterface $matcher,
    protected RequestStack $requestStack,
    protected RequestContext $context,
  ) {}

  /**
   * Sets the request on the request context.
   *
   * @param ?\Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  protected function setCurrentRequest(?Request $request): void {
    if ($request !== NULL) {
      try {
        $this->context->fromRequest($request);
      }
      catch (\UnexpectedValueException $e) {
        throw new BadRequestHttpException($e->getMessage(), $e, $e->getCode());
      }
    }
  }

  /**
   * Resets the routing context after a sub-request is done.
   */
  public function onKernelFinishRequest(): void {
    $this->setCurrentRequest($this->requestStack->getParentRequest());
  }

  /**
   * Sets request attributes based on a matching route.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $request = $event->getRequest();

    $this->setCurrentRequest($request);

    if ($request->attributes->has('_controller')) {
      // Routing is already done.
      return;
    }

    // Add routing attributes based on the request.
    try {
      $parameters = $this->matcher->matchRequest($request);

      $attributes = $parameters;
      $mapping = $parameters['_route_mapping'] ?? FALSE;
      if ($mapping) {
        unset($parameters['_route_mapping']);
        $mappedAttributes = [];
        $attributes = [];

        foreach ($parameters as $parameter => $value) {
          if (!isset($mapping[$parameter])) {
            $attribute = $parameter;
          }
          elseif (is_array($mapping[$parameter])) {
            [$attribute, $parameter] = $mapping[$parameter];
            $mappedAttributes[$attribute] = '';
          }
          else {
            $attribute = $mapping[$parameter];
          }

          if (!isset($mappedAttributes[$attribute])) {
            $attributes[$attribute] = $value;
            $mappedAttributes[$attribute] = $parameter;
          }
          elseif ($mappedAttributes[$attribute] !== '') {
            $attributes[$attribute] = [
              $mappedAttributes[$attribute] => $attributes[$attribute],
              $parameter => $value,
            ];
            $mappedAttributes[$attribute] = '';
          }
          else {
            $attributes[$attribute][$parameter] = $value;
          }
        }

        $attributes['_route_mapping'] = $mapping;
      }

      $request->attributes->add($attributes);
      unset($parameters['_route'], $parameters['_controller']);
      $request->attributes->set('_route_params', $parameters);
    }
    catch (CacheableResourceNotFoundException $exception) {
      $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getUriForPath($request->getPathInfo()));

      $referer = $request->headers->get('referer');
      if ($referer) {
        $message .= sprintf(' (from "%s")', $referer);
      }

      throw new CacheableNotFoundHttpException($exception, $message, $exception);
    }
    catch (ResourceNotFoundException $exception) {
      $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getUriForPath($request->getPathInfo()));

      $referer = $request->headers->get('referer');
      if ($referer) {
        $message .= sprintf(' (from "%s")', $referer);
      }

      throw new NotFoundHttpException($message, $exception);
    }
    catch (MethodNotAllowedException $exception) {
      $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $request->getUriForPath($request->getPathInfo()), implode(', ', $exception->getAllowedMethods()));

      throw new MethodNotAllowedHttpException($exception->getAllowedMethods(), $message, $exception);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => [['onKernelRequest', 32]],
      KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
    ];
  }

}
