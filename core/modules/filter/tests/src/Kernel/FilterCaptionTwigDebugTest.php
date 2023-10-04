<?php

namespace Drupal\Tests\filter\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\RenderContext;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the caption filter with Twig debugging on.
 *
 * @group filter
 */
class FilterCaptionTwigDebugTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Enable Twig debugging.
    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $container->setParameter('twig.config', $parameters);
  }

  /**
   * Tests the caption filter with Twig debugging on.
   */
  public function testCaptionFilter() {
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $filter = $bag->get('filter_caption');

    $renderer = $this->container->get('renderer');

    $test = function ($input) use ($filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter) {
        return $filter->process($input, 'und');
      });
    };

    // No data-caption attribute.
    $input = '<img src="llama.jpg" />';
    $expected = $input;
    $this->assertEquals($expected, $test($input)->getProcessedText());

    // Data-caption attribute.
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" />';
    $expected = '<img src="llama.jpg">' . "\n" . '<figcaption>Loquacious llama!</figcaption>';
    $output = $test($input)->getProcessedText();
    $this->assertStringContainsString($expected, $output);
    $this->assertStringContainsString("<!-- THEME HOOK: 'filter_caption' -->", $output);
  }

}
