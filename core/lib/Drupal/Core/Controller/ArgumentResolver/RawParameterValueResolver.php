<?php

namespace Drupal\Core\Controller\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

@trigger_error(__NAMESPACE__ . '\RawParameterValueResolver is deprecated in Drupal 8.8.1 and will be removed before Drupal 9.0.0. This class exists to prevent problems with updating core using Drush 8. There is no replacement.', E_USER_DEPRECATED);

/**
 * Exists to prevent problems with updating core using Drush 8.
 *
 * @deprecated in Drupal 8.8.1 and will be removed before Drupal 9.0.0. There is
 *   no replacement.
 */
final class RawParameterValueResolver implements ArgumentValueResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function supports(Request $request, ArgumentMetadata $argument) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Request $request, ArgumentMetadata $argument) {
    // This will never be called as self::supports() returns FALSE.
  }

}
