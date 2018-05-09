<?php

namespace Drupal\Core\Controller\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Yields an argument's value from the request's _raw_variables attribute.
 */
final class RawParameterValueResolver implements ArgumentValueResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function supports(Request $request, ArgumentMetadata $argument) {
    return !$argument->isVariadic() && $request->attributes->has('_raw_variables') && array_key_exists($argument->getName(), $request->attributes->get('_raw_variables'));
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Request $request, ArgumentMetadata $argument) {
    yield $request->attributes->get('_raw_variables')[$argument->getName()];
  }

}
