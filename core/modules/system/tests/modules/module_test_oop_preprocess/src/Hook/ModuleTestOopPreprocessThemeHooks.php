<?php

declare(strict_types=1);

namespace Drupal\module_test_oop_preprocess\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_test_oop_preprocess.
 */
class ModuleTestOopPreprocessThemeHooks {

  #[Hook('preprocess')]
  public function rootPreprocess($arg): mixed {
    return $arg;
  }

  #[Hook('preprocess_test')]
  public function preprocessTest($arg): mixed {
    return $arg;
  }

}
