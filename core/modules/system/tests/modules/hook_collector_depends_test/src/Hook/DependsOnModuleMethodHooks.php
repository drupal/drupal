<?php

declare(strict_types=1);

namespace Drupal\hook_collector_depends_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookDependsOnModule;

/**
 * Test Hooks with a dependency on the sole method.
 */
class DependsOnModuleMethodHooks {

  /**
   * Implements hook_with_dependency().
   */
  #[Hook('with_dependency')]
  #[HookDependsOnModule('aaa_hook_collector_test')]
  public function withDependency(): string {
    return __METHOD__;
  }

}
