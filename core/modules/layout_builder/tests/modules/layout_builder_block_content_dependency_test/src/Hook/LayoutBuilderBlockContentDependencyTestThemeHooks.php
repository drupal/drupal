<?php

declare(strict_types=1);

namespace Drupal\layout_builder_block_content_dependency_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_builder_block_content_dependency_test.
 */
class LayoutBuilderBlockContentDependencyTestThemeHooks {

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(array $modules, bool $is_syncing): void {
    // @see Drupal\Tests\layout_builder\Kernel\LayoutBuilderBlockContentDependencyTest
    if (in_array('layout_builder', $modules)) {
      \Drupal::service('plugin.manager.block')->getDefinitions();
      \Drupal::service('module_installer')->install([
        'block_content',
      ]);
    }
  }

}
