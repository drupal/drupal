<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Dom\HTMLDocument;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for Twig debug markup.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class TwigDebugMarkupTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Enable debug, rebuild the service container, and clear all caches.
   */
  protected function enableTwigDebug(): void {
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();
  }

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup(): void {
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();
    $this->enableTwigDebug();

    $cache = $this->container->get('theme.registry')->get();
    // Create array of Twig templates.
    $templates = drupal_find_theme_templates($cache, '.html.twig', $this->getThemePath('test_theme'));
    $templates += drupal_find_theme_templates($cache, '.html.twig', $this->getModulePath('node'));

    // Create a node and test different features of the debug markup.
    $node = $this->drupalCreateNode();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node);
    $output = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString("<!--\n\nTHEME DEBUG -->", $output, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertStringContainsString("THEME HOOK: 'node'", $output, 'Theme call information found.');
    $this->assertStringContainsString('▪️ node--1--full.html.twig' . PHP_EOL . '   ✅ node--1.html.twig' . PHP_EOL . '   ▪️ node--page--full.html.twig' . PHP_EOL . '   ▪️ node--page.html.twig' . PHP_EOL . '   ▪️ node--full.html.twig' . PHP_EOL . '   ▪️ node.html.twig', $output, 'Suggested template files found in order and node ID specific template shown as current template.');
    $this->assertStringContainsString(Html::escape('node--<script type="text/javascript">alert(\'yo\');</script>'), $output);
    $this->assertStringContainsString("<!--\nINVALID FILE NAME SUGGESTIONS:" . PHP_EOL . '   See https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!theme.api.php/function/hook_theme_suggestions_alter' . PHP_EOL . '   invalid_theme_suggestions' . PHP_EOL . '-->', $output, 'Twig debug markup found invalid suggestions.');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . '.html.twig';
    $this->assertStringContainsString("💡 BEGIN CUSTOM TEMPLATE OUTPUT from '$template_filename'", $output, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = $this->drupalCreateNode();
    $build = $builder->view($node2);
    $output = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString('▪️ node--2--full.html.twig' . PHP_EOL . '   ▪️ node--2.html.twig' . PHP_EOL . '   ▪️ node--page--full.html.twig' . PHP_EOL . '   ▪️ node--page.html.twig' . PHP_EOL . '   ▪️ node--full.html.twig' . PHP_EOL . '   ✅ node.html.twig', $output, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = $this->drupalCreateNode();
    $build = ['#theme' => 'node__foo__bar'];
    $build += $builder->view($node3);
    $output = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString("THEME HOOK: 'node__foo__bar'", $output, 'Theme call information found.');
    $this->assertStringContainsString('▪️ node--foo--bar.html.twig' . PHP_EOL . '   ▪️ node--foo.html.twig' . PHP_EOL . '   ▪️ node--&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;yo&#039;);&lt;/script&gt;.html.twig' . PHP_EOL . '   ▪️ node--3--full.html.twig' . PHP_EOL . '   ▪️ node--3.html.twig' . PHP_EOL . '   ▪️ node--page--full.html.twig' . PHP_EOL . '   ▪️ node--page.html.twig' . PHP_EOL . '   ▪️ node--full.html.twig' . PHP_EOL . '   ✅ node.html.twig', $output, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $build = $builder->view($node);
    $output = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringNotContainsString("<!--\n\nTHEME DEBUG -->", $output, 'Twig debug markup not found in theme output when debug is disabled.');
  }

  /**
   * Tests that the debug markup does not significantly alter the DOM.
   *
   * This is done by comparing the textContent property of the document body
   * with and without the debug output. Since textContent is the concatenation
   * of all child nodes, including whitespace and text nodes, it will
   * significantly differ if new whitespace is added by the HTML comments. The
   * comments themselves will not be part of the textContent.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Node/textContent
   */
  public function testTwigDebugMarkupDomParity(): void {
    $node = $this->drupalCreateNode();

    /**
     * @return array{textContent: string, innerHTML: string}
     */
    $get_text_and_html = function () use ($node): array {
      $html = $this->drupalGet(Url::fromRoute('entity.node.canonical', ['node' => $node->id()]));
      $dom = HTMLDocument::createFromString($html);
      $element = $dom->querySelector('body');
      return [
        'textContent' => trim($element->textContent),
        'innerHTML' => $element->innerHTML,
      ];
    };

    $twig_debug_output_needle = "<!--\n\nTHEME DEBUG -->";

    $text_and_html = $get_text_and_html();
    $this->assertStringNotContainsString($twig_debug_output_needle, $text_and_html['innerHTML']);

    $this->enableTwigDebug();
    $text_and_html_debug = $get_text_and_html();
    $this->assertStringContainsString($twig_debug_output_needle, $text_and_html_debug['innerHTML']);

    $this->assertEquals($text_and_html['textContent'], $text_and_html_debug['textContent']);

  }

}
