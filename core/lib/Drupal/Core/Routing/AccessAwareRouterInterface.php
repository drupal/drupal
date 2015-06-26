<?php
/**
 * @file
 * Contains \Drupal\Core\Routing\AccessAwareRouterInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Interface for a router class for Drupal with access check and upcasting.
 */
interface AccessAwareRouterInterface extends RouterInterface, RequestMatcherInterface {

  /**
   * Attribute name of the access result for the request..
   */
  const ACCESS_RESULT = '_access_result';

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when access checking failed.
   */
  public function matchRequest(Request $request);


  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when $access_check is enabled and access checking failed.
   */
  public function match($pathinfo);

}
