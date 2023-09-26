<?php

namespace Drupal\Core\Controller\ArgumentResolver;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Yields a RouteMatch object based on the request object passed along.
 */
final class RouteMatchValueResolver implements ValueResolverInterface {

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0.
   *    There is no replacement.
   *
   * @see https://www.drupal.org/node/3383585
   */
  public function supports(Request $request, ArgumentMetadata $argument): bool {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3383585', E_USER_DEPRECATED);
    return $argument->getType() == RouteMatchInterface::class || is_subclass_of($argument->getType(), RouteMatchInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Request $request, ArgumentMetadata $argument): array {
    return $argument->getType() === RouteMatchInterface::class || is_subclass_of($argument->getType(), RouteMatchInterface::class) ? [RouteMatch::createFromRequest($request)] : [];
  }

}
