<?php

namespace Drupal\autowire_test;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * A service that is autowired.
 */
class TestInjection3 implements TrustedCallbackInterface, TestInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [];
  }

}
