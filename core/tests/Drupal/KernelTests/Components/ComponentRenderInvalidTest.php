<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Tests\Core\Theme\Component\ComponentKernelTestBase;

/**
 * Tests invalid render options for components.
 *
 * @group sdc
 */
class ComponentRenderInvalidTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sdc_test_invalid'];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['starterkit_theme'];

  /**
   * Ensure that components in modules without schema fail validation.
   *
   * The module sdc_test_invalid contains the my-card-no-schema component. This
   * component does not have schema definitions.
   */
  public function testInvalidDefinitionModule(): void {
    $this->expectException(InvalidComponentException::class);
    $this->expectExceptionMessage('The component "sdc_test_invalid:my-card-no-schema" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.');
    $this->manager->getDefinitions();
  }

  /**
   * Ensure that components in modules without schema fail validation.
   *
   * The theme sdc_theme_test_enforce_schema_invalid is set as enforcing schemas
   * but provides a component without schema.
   */
  public function testInvalidDefinitionTheme(): void {
    \Drupal::service('theme_installer')->install(['sdc_theme_test_enforce_schema_invalid']);
    $active_theme = \Drupal::service('theme.initialization')->initTheme('sdc_theme_test_enforce_schema_invalid');
    \Drupal::service('theme.manager')->setActiveTheme($active_theme);
    $this->expectException(InvalidComponentException::class);
    $this->manager->getDefinitions();
  }

}
