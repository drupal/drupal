<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\non_installed_module\NonExisting;

/**
 * This class does not have a plugin attribute or plugin annotation on purpose.
 */
#[\Attribute]
class ExtendingNonInstalledClass extends NonExisting {

  /**
   * Provides an empty test method for testing.
   */
  #[TrustedCallback]
  public function testMethod() {}

}
