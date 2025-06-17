<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Component;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the correct rendering of components.
 *
 * @group sdc
 */
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

}
