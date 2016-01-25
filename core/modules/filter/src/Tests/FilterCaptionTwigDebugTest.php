<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterCaptionTwigDebugTest.
 */

namespace Drupal\filter\Tests;

use Drupal\Core\Render\RenderContext;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\FilterPluginCollection;

/**
 * Tests the caption filter with Twig debugging on.
 *
 * @group filter
 */
class FilterCaptionTwigDebugTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter'];

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * Enables Twig debugging.
   */
  protected function debugOn() {
    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    if (!$parameters['debug']) {
      $parameters['debug'] = TRUE;
      $this->setContainerParameter('twig.config', $parameters);
      $this->rebuildContainer();
      $this->resetAll();
    }
  }

  /**
   * Disables Twig debugging.
   */
  protected function debugOff() {
    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    if ($parameters['debug']) {
      $parameters['debug'] = FALSE;
      $this->setContainerParameter('twig.config', $parameters);
      $this->rebuildContainer();
      $this->resetAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->debugOn();

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filters = $bag->getAll();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->debugOff();
  }

  /**
   * Test the caption filter with Twig debugging on.
   */
  function testCaptionFilter() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $filter = $this->filters['filter_caption'];

    $test = function ($input) use ($filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter) {
        return $filter->process($input, 'und');
      });
    };

    // No data-caption attribute.
    $input = '<img src="llama.jpg" />';
    $expected = $input;
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Data-caption attribute.
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" />';
    $expected = '<img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption>';
    $output = $test($input);
    $output = $output->getProcessedText();
    $this->assertTrue(strpos($output, $expected) !== FALSE, "\"$output\" contains \"$expected\"");
    $this->assertTrue(strpos($output, '<!-- THEME HOOK: \'filter_caption\' -->') !== FALSE, 'filter_caption theme hook debug comment is present.');
  }

}
