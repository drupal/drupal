<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the correct rendering of components.
 *
 * @group sdc
 * @group legacy
 *
 * @internal
*/
final class LibrariesBCLayerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc', 'sdc_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'sdc_theme_test';

  /**
   * Tests libraryOverrides.
   */
  public function testLibraryBCLayer(): void {
    $this->expectDeprecation('The module \'sdc\' is deprecated. See https://www.drupal.org/docs/core-modules-and-themes/deprecated-and-obsolete#s-single-directory-components-sdc');
    $this->expectDeprecation('The sdc/sdc_theme_test--my-card asset library is deprecated in Drupal 10.3.0 and will be removed in Drupal 11.0.0. Use the core/components.[component-id] library instead. See https://www.drupal.org/node/3410260');
    $build = [
      '#type' => 'inline_template',
      '#template' => "<h2>Foo</h2>{{ attach_library('sdc/sdc_theme_test--my-card') }}",
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $output = $this->drupalGet('sdc-test-component');
    // Ensure the CSS from the component is properly added to the page.
    $this->assertStringContainsString('my-card.css', $output);
  }

}
