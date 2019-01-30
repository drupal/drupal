<?php

namespace Drupal\big_pipe\Controller;

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\LocalRedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Returns responses for BigPipe module routes.
 */
class BigPipeController {

  /**
   * Sets a BigPipe no-JS cookie, redirects back to the original location.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Routing\LocalRedirectResponse
   *   A response that sets the no-JS cookie and redirects back to the original
   *   location.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the no-JS cookie is already set or when there is no session.
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Thrown when the original location is missing, i.e. when no 'destination'
   *   query argument is set.
   *
   * @see \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy
   */
  public function setNoJsCookie(Request $request) {
    // This controller may only be accessed when the browser does not support
    // JavaScript. It is accessed automatically when that's the case thanks to
    // big_pipe_page_attachments(). When this controller is executed, deny
    // access when either:
    // - the no-JS cookie is already set: this indicates a redirect loop, since
    //   the cookie was already set, yet the user is executing this controller;
    // - there is no session, in which case BigPipe is not enabled anyway, so it
    //   is pointless to set this cookie.
    if ($request->cookies->has(BigPipeStrategy::NOJS_COOKIE) || !$request->hasSession()) {
      throw new AccessDeniedHttpException();
    }

    if (!$request->query->has('destination')) {
      throw new HttpException(400, 'The original location is missing.');
    }

    $response = new LocalRedirectResponse($request->query->get('destination'));
    // Set cookie without httpOnly, so that JavaScript can delete it.
    $response->headers->setCookie(new Cookie(BigPipeStrategy::NOJS_COOKIE, TRUE, 0, '/', NULL, FALSE, FALSE, FALSE, NULL));
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . BigPipeStrategy::NOJS_COOKIE, 'session.exists']));
    return $response;
  }

}
