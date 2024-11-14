<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\views\Views;

/**
 * Tests the template retrieval of views.
 *
 * @group views
 *
 * @see \Drupal\views_test_data\Plugin\views\style\StyleTemplateTest
 */
class ViewsTemplateTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_page_display', 'test_view_display_template'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    // Enable Twig debugging.
    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $container->setParameter('twig.config', $parameters);
  }

  /**
   * Tests render functionality.
   */
  public function testTemplate(): void {
    // Make sure that the rendering just calls the preprocess function once.
    $output = Views::getView('test_view_display_template')->preview();
    $renderer = $this->container->get('renderer');

    // Check that the rendered output uses the correct template file.
    $this->assertStringContainsString('This module defines its own display template.', (string) $renderer->renderRoot($output));
  }

  /**
   * @covers \Drupal\views\Hook\ViewsHooks::themeSuggestionsContainerAlter
   * @throws \Exception
   */
  public function testThemeSuggestionsContainerAlter(): void {
    $build = [
      '#type' => 'view',
      '#name' => 'test_page_display',
      '#display_id' => 'default',
      '#arguments' => [],
    ];

    $output = $this->render($build);
    $extension = '.html.twig';
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   ▪️ container--more-link--test-page-display--default' . $extension . PHP_EOL
      . '   ▪️ container--more-link--default' . $extension . PHP_EOL
      . '   ▪️ container--more-link--test-page-display' . $extension . PHP_EOL
      . '   ▪️ container--more-link' . $extension . PHP_EOL
      . '   ✅ container' . $extension . PHP_EOL
      . '-->' . PHP_EOL;
    $this->assertStringContainsString($expected, $output, 'Views more link container suggestions found in Twig debug output');
  }

}
