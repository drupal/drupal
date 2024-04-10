<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecating variables passed to twig templates.
 *
 * @see \Drupal\Core\Template\TwigExtension::checkDeprecations()
 * @see \Drupal\Core\Template\TwigNodeVisitorCheckDeprecations
 * @see \Drupal\Core\Template\TwigNodeCheckDeprecations
 * @group Twig
 * @group legacy
 * @group #slow
 */
class TwigDeprecationsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'theme_test'];

  /**
   * Test deprecating variables at definition in hook_theme().
   */
  public function testHookThemeDeprecations(): void {
    $element = [
      '#theme' => 'theme_test_deprecations_hook_theme',
      '#foo' => 'foo',
      '#bar' => 'bar',
    ];
    // Both 'foo' and 'bar' are deprecated in theme_test_hook_theme(),
    // but 'bar' is not used in theme-test-deprecations-hook-theme.html.twig.
    $this->expectDeprecation($this->getDeprecationMessage('foo'));
    $this->assertEquals('foo', $this->container->get('renderer')->renderRoot($element));
  }

  /**
   * Test theme_test_deprecations_preprocess renders without deprecations.
   */
  public function testThemeTestDeprecations(): void {
    $this->assertRendered('foo|set_var|bar', []);
  }

  /**
   * Test deprecation of undefined variable triggers no error.
   */
  public function testUndefinedDeprecation(): void {
    $preprocess = [
      'deprecations' => [
        'undefined' => $this->getDeprecationMessage('undefined'),
      ],
    ];
    $this->assertRendered('foo|set_var|bar', $preprocess);
  }

  /**
   * Test deprecation of single variable triggers error.
   */
  public function testSingleDeprecation(): void {
    $preprocess = [
      'deprecations' => [
        'foo' => $this->getDeprecationMessage('foo'),
      ],
    ];
    $this->expectDeprecation($this->getDeprecationMessage('foo'));
    $this->assertRendered('foo|set_var|bar', $preprocess);
  }

  /**
   * Test deprecation of empty variable triggers error.
   */
  public function testEmptyDeprecation(): void {
    $preprocess = [
      'foo' => '',
      'deprecations' => [
        'foo' => $this->getDeprecationMessage('foo'),
      ],
    ];
    $this->expectDeprecation($this->getDeprecationMessage('foo'));
    $this->assertRendered('|set_var|bar', $preprocess);
  }

  /**
   * Test deprecation of multiple variables triggers errors.
   */
  public function testMultipleDeprecations(): void {
    $preprocess = [
      'deprecations' => [
        'foo' => $this->getDeprecationMessage('foo'),
        'bar' => $this->getDeprecationMessage('bar'),
      ],
    ];
    $this->expectDeprecation($this->getDeprecationMessage('foo'));
    $this->expectDeprecation($this->getDeprecationMessage('bar'));
    $this->assertRendered('foo|set_var|bar', $preprocess);
  }

  /**
   * Test deprecation of variables assigned inside template triggers no error.
   */
  public function testAssignedVariableDeprecation(): void {
    $preprocess = [
      'contents' => ['content'],
      'deprecations' => [
        'set_var' => $this->getDeprecationMessage('set_var'),
        'for_var' => $this->getDeprecationMessage('for_var'),
      ],
    ];
    $this->assertRendered('foo|set_var|content|bar', $preprocess);
  }

  /**
   * Test deprecation of variables in parent does not leak to child.
   */
  public function testParentVariableDeprecation(): void {
    // 'foo' is used before the child template is processed, so this test
    // shows that processing the child does not lead to parent usage being
    // forgotten.
    // 'gaz' is used in the child template but only deprecated in the parent
    // template, so no deprecation error is triggered for it.
    $preprocess = [
      'contents' => [
        'child' => [
          '#theme' => 'theme_test_deprecations_child',
          '#foo' => 'foo-child',
          '#bar' => 'bar-child',
          '#gaz' => 'gaz-child',
        ],
      ],
      'deprecations' => [
        'foo' => $this->getDeprecationMessage('foo'),
        'gaz' => $this->getDeprecationMessage('gaz'),
      ],
    ];
    $this->assertRendered('foo|set_var|foo-child|gaz-child|bar', $preprocess);
  }

  /**
   * Assert that 'theme_test_deprecations_preprocess' renders expected text.
   *
   * @param string $expected
   *   The expected text.
   * @param array $preprocess
   *   An array to merge in theme_test_deprecations_preprocess_preprocess().
   */
  protected function assertRendered($expected, array $preprocess): void {
    \Drupal::state()->set('theme_test.theme_test_deprecations_preprocess', $preprocess);
    $element = [
      '#theme' => 'theme_test_deprecations_preprocess',
      '#foo' => 'foo',
      '#bar' => 'bar',
      '#gaz' => 'gaz',
      '#set_var' => 'overridden',
    ];
    $this->assertEquals($expected, $this->container->get('renderer')->renderRoot($element));
  }

  /**
   * Get an example deprecation message for a named variable.
   */
  protected function getDeprecationMessage($variable): string {
    return "'{$variable}' is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use 'new_{$variable}' instead. See https://www.example.com.";
  }

}
