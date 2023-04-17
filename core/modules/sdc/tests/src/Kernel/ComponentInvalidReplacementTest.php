<?php

namespace Drupal\Tests\sdc\Kernel;

use Drupal\sdc\Exception\IncompatibleComponentSchema;

/**
 * Tests invalid render options for components.
 *
 * @group sdc
 *
 * @internal
 */
final class ComponentInvalidReplacementTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sdc_test_replacements_invalid'];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

  /**
   * Ensure that component replacement validates the schema compatibility.
   */
  public function testInvalidDefinitionTheme(): void {
    $this->expectException(IncompatibleComponentSchema::class);
    $this->manager->getDefinitions();
  }

}
