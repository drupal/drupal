<?php

declare(strict_types=1);

namespace Drupal\Tests\field_layout\Kernel;

use Drupal\Tests\layout_builder\Kernel\LayoutBuilderCompatibilityTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Field Layout Uninstall.
 */
#[Group('field_layout')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class FieldLayoutUninstallTest extends LayoutBuilderCompatibilityTestBase {

  /**
   * Ensures field layout can be uninstalled with layout builder enabled.
   */
  public function testFieldLayoutUninstall(): void {
    // Setup user schema so user hook uninstall hook doesn't break.
    $this->installSchema('user', 'users_data');

    // Setup layout builder and same displays.
    $this->installLayoutBuilder();

    // Ensure install hook can handle displays without a layout.
    $this->container->get('module_installer')->install(['field_layout']);

    // Ensure uninstall hook can handle displays without a layout.
    $this->container->get('module_installer')->uninstall(['field_layout']);
  }

}
