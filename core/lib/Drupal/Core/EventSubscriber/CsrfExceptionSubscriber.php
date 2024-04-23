<?php

declare(strict_types=1);

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Handles exceptions related to CSRF access.
 *
 * Redirects CSRF 403 exceptions to a _csrf_confirm_form_route.
 */
class CsrfExceptionSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats(): array {
    return ['html'];
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event): void {
    $request = $event->getRequest();
    $routeMatch = RouteMatch::createFromRequest($request);
    $route = $routeMatch->getRouteObject();
    if (!$route->hasRequirement('_csrf_token') || empty($route->getOption('_csrf_confirm_form_route'))) {
      return;
    }
    $event->setResponse(new RedirectResponse(Url::fromRoute($route->getOption('_csrf_confirm_form_route'))->toString()));
  }

}
