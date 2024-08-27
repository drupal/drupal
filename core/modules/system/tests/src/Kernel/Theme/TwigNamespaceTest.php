<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use Twig\TemplateWrapper;

/**
 * Tests Twig namespaces.
 *
 * @group Theme
 */
class TwigNamespaceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'twig_theme_test',
    'twig_namespace_a',
    'twig_namespace_b',
    'node',
  ];

  /**
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme', 'olivero']);
    $this->twig = \Drupal::service('twig');
  }

  /**
   * Checks to see if a value is a twig template.
   *
   * @internal
   */
  public function assertTwigTemplate($value, string $message = ''): void {
    $this->assertInstanceOf(TemplateWrapper::class, $value, $message);
  }

  /**
   * Tests template discovery using namespaces.
   */
  public function testTemplateDiscovery(): void {
    // Tests resolving namespaced templates in modules.
    $this->assertTwigTemplate($this->twig->load('@node/node.html.twig'), 'Found node.html.twig in node module.');

    // Tests resolving namespaced templates in themes.
    $this->assertTwigTemplate($this->twig->load('@olivero/layout/page.html.twig'), 'Found page.html.twig in Olivero theme.');
  }

  /**
   * Tests template extension and includes using namespaces.
   */
  public function testTwigNamespaces(): void {
    // Test twig @extends and @include in template files.
    $test = ['#theme' => 'twig_namespace_test'];
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($test));

    $this->assertText('This line is from twig_namespace_a/templates/test.html.twig');
    $this->assertText('This line is from twig_namespace_b/templates/test.html.twig');
  }

}
