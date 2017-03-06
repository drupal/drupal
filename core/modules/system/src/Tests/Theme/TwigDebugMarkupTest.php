<?php

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for Twig debug markup.
 *
 * @group Theme
 */
class TwigDebugMarkupTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['theme_test', 'node'];

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $extension = twig_extension();
    \Drupal::service('theme_handler')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();
    $this->drupalCreateContentType(['type' => 'page']);
    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $cache = $this->container->get('theme.registry')->get();
    // Create array of Twig templates.
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('theme', 'test_theme'));
    $templates += drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'node'));

    // Create a node and test different features of the debug markup.
    $node = $this->drupalCreateNode();
    $build = node_view($node);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup found in theme output when debug is enabled.');
    $this->setRawContent($output);
    $this->assertTrue(strpos($output, "THEME HOOK: 'node'") !== FALSE, 'Theme call information found.');
    $this->assertTrue(strpos($output, '* node--1--full' . $extension . PHP_EOL . '   x node--1' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   * node' . $extension) !== FALSE, 'Suggested template files found in order and node ID specific template shown as current template.');
    $this->assertEscaped('node--<script type="text/javascript">alert(\'yo\');</script>');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertTrue(strpos($output, "BEGIN OUTPUT from '$template_filename'") !== FALSE, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = $this->drupalCreateNode();
    $build = node_view($node2);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, '* node--2--full' . $extension . PHP_EOL . '   * node--2' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = $this->drupalCreateNode();
    $build = ['#theme' => 'node__foo__bar'];
    $build += node_view($node3);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, "THEME HOOK: 'node__foo__bar'") !== FALSE, 'Theme call information found.');
    $this->assertTrue(strpos($output, '* node--foo--bar' . $extension . PHP_EOL . '   * node--foo' . $extension . PHP_EOL . '   * node--&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;yo&#039;);&lt;/script&gt;' . $extension . PHP_EOL . '   * node--3--full' . $extension . PHP_EOL . '   * node--3' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $build = node_view($node);
    $output = $renderer->renderRoot($build);
    $this->assertFalse(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup not found in theme output when debug is disabled.');
  }

}
