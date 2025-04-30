<?php

declare(strict_types=1);

namespace Drupal\module_test_oop_preprocess\Hook;

use Drupal\Core\Hook\Attribute\Preprocess;

/**
 * Hook implementations for module_test_oop_preprocess.
 */
class ModuleTestOopPreprocessThemeHooks {

  #[Preprocess]
  public function rootPreprocess($arg): mixed {
    return $arg;
  }

  #[Preprocess('test')]
  public function preprocessTest($arg): mixed {
    return $arg;
  }

}
