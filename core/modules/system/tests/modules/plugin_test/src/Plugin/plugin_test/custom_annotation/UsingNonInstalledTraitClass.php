<?php

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\non_installed_module\NonExistingTrait;

/**
 * This class does not have a plugin attribute or plugin annotation on purpose.
 */
class UsingNonInstalledTraitClass {
  use NonExistingTrait;

  #[TrustedCallback]
  public function testMethod() {}

}
