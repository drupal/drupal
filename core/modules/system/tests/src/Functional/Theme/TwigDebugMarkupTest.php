<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Twig debug markup.
 *
 * @group Theme
 */
class TwigDebugMarkupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $extension = twig_extension();
    \Drupal::service('theme_installer')->install(['test_theme']);
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
    $templates = drupal_find_theme_templates($cache, $extension, $this->getThemePath('test_theme'));
    $templates += drupal_find_theme_templates($cache, $extension, $this->getModulePath('node'));

    // Create a node and test different features of the debug markup.
    $node = $this->drupalCreateNode();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node);
    $output = $renderer->renderRoot($build);
    $this->assertStringContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertStringContainsString("THEME HOOK: 'node'", $output, 'Theme call information found.');
    $this->assertStringContainsString('* node--1--full' . $extension . PHP_EOL . '   x node--1' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   * node' . $extension, $output, 'Suggested template files found in order and node ID specific template shown as current template.');
    $this->assertStringContainsString(Html::escape('node--<script type="text/javascript">alert(\'yo\');</script>'), (string) $output);
    $this->assertStringContainsString('<!-- INVALID FILE NAME SUGGESTIONS:' . PHP_EOL . '   See https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!theme.api.php/function/hook_theme_suggestions_alter' . PHP_EOL . '   invalid_theme_suggestions' . PHP_EOL . '-->', $output, 'Twig debug markup found invalid suggestions.');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertStringContainsString("BEGIN OUTPUT from '$template_filename'", $output, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = $this->drupalCreateNode();
    $build = $builder->view($node2);
    $output = $renderer->renderRoot($build);
    $this->assertStringContainsString('* node--2--full' . $extension . PHP_EOL . '   * node--2' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension, $output, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = $this->drupalCreateNode();
    $build = ['#theme' => 'node__foo__bar'];
    $build += $builder->view($node3);
    $output = $renderer->renderRoot($build);
    $this->assertStringContainsString("THEME HOOK: 'node__foo__bar'", $output, 'Theme call information found.');
    $this->assertStringContainsString('* node--foo--bar' . $extension . PHP_EOL . '   * node--foo' . $extension . PHP_EOL . '   * node--&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;yo&#039;);&lt;/script&gt;' . $extension . PHP_EOL . '   * node--3--full' . $extension . PHP_EOL . '   * node--3' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension, $output, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $build = $builder->view($node);
    $output = $renderer->renderRoot($build);
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup not found in theme output when debug is disabled.');
  }

}
