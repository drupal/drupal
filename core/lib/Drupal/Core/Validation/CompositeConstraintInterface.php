<?php

namespace Drupal\Core\Validation;

/**
 * An interface to provide a bridge to Symfony composite constraints.
 */
interface CompositeConstraintInterface {

  /**
   * Returns the name of the property or properties that contain constraints.
   *
   * This method should be a static implementation of
   * Composite::getCompositeOption().
   *
   * @return array|string
   *   The name of the property or properties that contain constraints.
   *
   * @see \Symfony\Component\Validator\Constraints\Composite::getCompositeOption()
   */
  public static function getCompositeOptionStatic(): array|string;

}
