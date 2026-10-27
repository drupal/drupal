<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_field_type;

/**
 * A test gadget class for verifying that object injection is blocked.
 *
 * If unserialize() is called without allowed_classes: false, this class will
 * be instantiated and __wakeup() will throw an Exception. The presence of the
 * Exception proves that Object Injection was successful.
 *
 * @internal
 */
class TestGadget {

  /**
   * {@inheritdoc}
   */
  public function __wakeup(): void {
    throw new \RuntimeException('JSONAPI_TEST_GADGET_WAKEUP_CALLED');
  }

}
