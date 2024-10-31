<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Core\Render\Component\Exception\IncompatibleComponentSchema;

/**
 * Tests invalid render options for components.
 *
 * @group sdc
 */
class ComponentInvalidReplacementTest extends ComponentKernelTestBase {

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
