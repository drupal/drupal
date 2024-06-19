<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Component\FrontMatter\FrontMatterTest as ComponentFrontMatterTest;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Error\Error;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * Tests Twig front matter support.
 *
 * @group Twig
 */
class FrontMatterTest extends KernelTestBase {

  /**
   * A broken source.
   */
  const BROKEN_SOURCE = '<div>Hello {{ world</div>';

  /**
   * Twig service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->twig = \Drupal::service('twig');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $definition = new Definition(FilesystemLoader::class, [[sys_get_temp_dir()]]);
    $definition->setPublic(TRUE);
    $container->setDefinition('twig_loader__file_system', $definition)
      ->addTag('twig.loader');
  }

  /**
   * Creates a new temporary Twig file.
   *
   * @param string $content
   *   The contents of the Twig file to save.
   *
   * @return string
   *   The absolute path to the temporary file.
   */
  protected function createTwigTemplate(string $content = ''): string {
    $file = tempnam(sys_get_temp_dir(), 'twig') . ".html.twig";
    file_put_contents($file, $content);
    return $file;
  }

  /**
   * Tests broken front matter.
   *
   * @covers \Drupal\Core\Template\TwigEnvironment::getTemplateMetadata
   * @covers \Drupal\Component\FrontMatter\Exception\FrontMatterParseException
   */
  public function testFrontMatterBroken(): void {
    $source = "---\ncollection:\n-  key: foo\n  foo: bar\n---\n" . ComponentFrontMatterTest::SOURCE;
    $file = $this->createTwigTemplate($source);
    $this->expectException(SyntaxError::class);
    $this->expectExceptionMessage('An error occurred when attempting to parse front matter data on line 4 in ' . $file);
    $this->twig->getTemplateMetadata(basename($file));
  }

  /**
   * Test Twig template front matter.
   *
   * @param array|null $yaml
   *   The YAML used for metadata in a Twig template.
   * @param int $line
   *   The expected line number where the source code starts.
   * @param string $content
   *   The content to use for testing purposes.
   *
   * @covers \Drupal\Core\Template\TwigEnvironment::compileSource
   * @covers \Drupal\Core\Template\TwigEnvironment::getTemplateMetadata
   *
   * @dataProvider \Drupal\Tests\Component\FrontMatter\FrontMatterTest::providerFrontMatterData
   */
  public function testFrontMatter($yaml, $line, $content = ComponentFrontMatterTest::SOURCE): void {
    // Create a temporary Twig template.
    $source = ComponentFrontMatterTest::createFrontMatterSource($yaml, $content);
    $file = $this->createTwigTemplate($source);
    $name = basename($file);

    // Ensure the proper metadata is returned.
    $metadata = $this->twig->getTemplateMetadata($name);
    $this->assertEquals($yaml ?? [], $metadata);

    // Ensure the metadata is never rendered.
    $output = $this->twig->load($name)->render();
    $this->assertEquals($content, $output);

    // Create a temporary Twig template.
    $source = ComponentFrontMatterTest::createFrontMatterSource($yaml, static::BROKEN_SOURCE);
    $file = $this->createTwigTemplate($source);
    $name = basename($file);

    try {
      $this->twig->load($name);
    }
    catch (Error $error) {
      $this->assertEquals($line, $error->getTemplateLine());
    }

    // Ensure string based templates work too.
    try {
      $this->twig->createTemplate($source)->render();
    }
    catch (Error $error) {
      $this->assertEquals($line, $error->getTemplateLine());
    }
  }

}
