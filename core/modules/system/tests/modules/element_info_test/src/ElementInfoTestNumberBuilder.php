<?php

declare(strict_types=1);

namespace Drupal\element_info_test;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a trusted callback to alter the element_info_test number element.
 *
 * @see element_info_test_element_info_alter()
 */
class ElementInfoTestNumberBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Sets element_info_test - #pre_render callback.
   */
  public static function preRender(array $element): array {
    return $element;
  }

}
