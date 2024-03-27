<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Components;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the correct rendering of components.
 *
 * @group sdc
 */
class ComponentRenderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'sdc_theme_test';

  /**
   * Tests libraryOverrides.
   */
  public function testLibraryOverrides(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_theme_test:lib-overrides') }}",
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $output = $this->drupalGet('sdc-test-component');
    $this->assertStringContainsString('another-stylesheet.css', $output);
    // Since libraryOverrides is taking control of CSS, and it's not listing
    // lib-overrides.css, then it should not be there. Even if it's the CSS
    // that usually gets auto-attached.
    $this->assertStringNotContainsString('lib-overrides.css', $output);
    // Ensure that libraryDependencies adds the expected assets.
    $this->assertStringContainsString('dialog.position.js', $output);
    // Ensure that libraryOverrides processes attributes properly.
    $this->assertMatchesRegularExpression('@<script.*src="[^"]*lib-overrides\.js\?v=1[^"]*".*defer.*bar="foo"></script>@', $output);
    // Ensure that libraryOverrides processes external CSS properly.
    $this->assertMatchesRegularExpression('@<link.*href="https://drupal\.org/fake-dependency/styles\.css" />@', $output);
    // Ensure that libraryOverrides processes external JS properly.
    $this->assertMatchesRegularExpression('@<script.*src="https://drupal\.org/fake-dependency/index\.min\.js"></script>@', $output);
  }

}
