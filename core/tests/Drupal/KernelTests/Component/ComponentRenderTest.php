<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Component;

use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the correct rendering of components.
 */
#[Group('sdc')]
#[RunTestsInSeparateProcesses]
final class ComponentRenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc_test'];

  /**
   * Tests the CSS load order.
   */
  public function testCssOrder(): void {
    $this->container->get('theme_installer')->install(['sdc_theme_test']);
    $build = [
      '#type' => 'component',
      '#component' => 'sdc_theme_test:css-load-order',
      '#props' => [],
    ];
    \Drupal::state()->set('sdc_test_component', $build);

    $request = Request::create('/sdc-test-component');
    $response = $this->container->get('http_kernel')->handle($request);

    $output = $response->getContent();

    $crawler = new Crawler($output);
    // Assert that both CSS files are attached to the page.
    $this->assertNotEmpty($crawler->filter('link[rel="stylesheet"][href*="css-load-order.css"]'));
    $this->assertNotEmpty($crawler->filter('link[rel="stylesheet"][href*="css-order-dependent.css"]'));
    $all_stylesheets = $crawler->filter('link[rel="stylesheet"]');
    $component_position = NULL;
    $dependent_position = NULL;
    foreach ($all_stylesheets as $index => $stylesheet) {
      $href = $stylesheet->attributes->getNamedItem('href')->nodeValue;
      if (str_contains($href, 'css-load-order.css')) {
        $component_position = $index;
      }
      if (str_contains($href, 'css-order-dependent.css')) {
        $dependent_position = $index;
      }
    }

    // This will assert that css-order-dependent.css is loaded before the
    // component's css-load-order.css.
    $this->assertGreaterThan($dependent_position, $component_position);
  }

  /**
   * Tests libraryOverrides.
   */
  public function testLibraryOverrides(): void {
    \Drupal::service(ThemeInstallerInterface::class)
      ->install(['sdc_theme_test']);
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_theme_test:lib-overrides') }}",
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $output = $this->drupalGet('sdc-test-component');
    $this->assertStringContainsString('another-stylesheet.css', $output);
    $this->assertStringContainsString('test-component-font.woff2', $output);
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
