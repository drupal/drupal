<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Twig\Error\LoaderError;

/**
 * Tests including files in Twig templates.
 *
 * @group Theme
 */
class TwigIncludeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The Twig configuration to set the container parameter to during rebuilds.
   *
   * @var array
   */
  private $twigConfig = [];

  /**
   * Tests template inclusion extension checking.
   *
   * @see \Drupal\Core\Template\Loader\FilesystemLoader::findTemplate()
   */
  public function testTemplateInclusion(): void {
    $this->enableModules(['system']);
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $element['test'] = [
      '#type' => 'inline_template',
      '#template' => "{% include '@system/container.html.twig' %}",
    ];
    $this->assertSame("<div></div>\n", (string) $renderer->renderRoot($element));

    // Test that SQL files cannot be included in Twig templates by default.
    $element = [];
    $element['test'] = [
      '#type' => 'inline_template',
      '#template' => "{% include '@__main__/core/tests/fixtures/files/sql-2.sql' %}",
    ];
    try {
      $renderer->renderRoot($element);
      $this->fail('Expected exception not thrown');
    }
    catch (LoaderError $e) {
      $this->assertStringContainsString('Template "@__main__/core/tests/fixtures/files/sql-2.sql" is not defined', $e->getMessage());
    }
    /** @var \Drupal\Core\Template\Loader\FilesystemLoader $loader */
    $loader = \Drupal::service('twig.loader.filesystem');
    try {
      $loader->getSourceContext('@__main__\/core/tests/fixtures/files/sql-2.sql');
      $this->fail('Expected exception not thrown');
    }
    catch (LoaderError $e) {
      $this->assertStringContainsString('Template @__main__\/core/tests/fixtures/files/sql-2.sql has an invalid file extension (sql). Only templates ending in one of css, html, js, svg, twig are allowed. Set the twig.config.allowed_file_extensions container parameter to customize the allowed file extensions', $e->getMessage());
    }

    // Allow SQL files to be included.
    $twig_config = $this->container->getParameter('twig.config');
    $twig_config['allowed_file_extensions'][] = 'sql';
    $this->twigConfig = $twig_config;
    // @todo This used to call shutdown() and boot(). rebuildContainer() is
    // needed until we stop pushing the request twice and only popping it once.
    // @see https://www.drupal.org/i/2613044
    $this->container->get('kernel')->rebuildContainer();
    /** @var \Drupal\Core\Template\Loader\FilesystemLoader $loader */
    $loader = \Drupal::service('twig.loader.filesystem');
    $source = $loader->getSourceContext('@__main__\/core/tests/fixtures/files/sql-2.sql');
    $this->assertSame(file_get_contents('core/tests/fixtures/files/sql-2.sql'), $source->getCode());

    // Test the fallback to the default list of extensions provided by the
    // class.
    $this->assertSame(['css', 'html', 'js', 'svg', 'twig', 'sql'], \Drupal::getContainer()->getParameter('twig.config')['allowed_file_extensions']);
    unset($twig_config['allowed_file_extensions']);
    $this->twigConfig = $twig_config;
    // @todo This used to call shutdown() and boot(). rebuildContainer() is
    // needed until we stop pushing the request twice and only popping it once.
    // @see https://www.drupal.org/i/2613044
    $this->container->get('kernel')->rebuildContainer();
    $this->assertArrayNotHasKey('allowed_file_extensions', \Drupal::getContainer()->getParameter('twig.config'));
    /** @var \Drupal\Core\Template\Loader\FilesystemLoader $loader */
    $loader = \Drupal::service('twig.loader.filesystem');
    try {
      $loader->getSourceContext('@__main__\/core/tests/fixtures/files/sql-2.sql');
      $this->fail('Expected exception not thrown');
    }
    catch (LoaderError $e) {
      $this->assertStringContainsString('Template @__main__\/core/tests/fixtures/files/sql-2.sql has an invalid file extension (sql). Only templates ending in one of css, html, js, svg, twig are allowed. Set the twig.config.allowed_file_extensions container parameter to customize the allowed file extensions', $e->getMessage());
    }

    // Test a file with no extension.
    file_put_contents($this->siteDirectory . '/test_file', 'This is a test!');
    /** @var \Drupal\Core\Template\Loader\FilesystemLoader $loader */
    $loader = \Drupal::service('twig.loader.filesystem');
    try {
      $loader->getSourceContext('@__main__\/' . $this->siteDirectory . '/test_file');
      $this->fail('Expected exception not thrown');
    }
    catch (LoaderError $e) {
      $this->assertStringContainsString('test_file has an invalid file extension (no file extension). Only templates ending in one of css, html, js, svg, twig are allowed. Set the twig.config.allowed_file_extensions container parameter to customize the allowed file extensions', $e->getMessage());
    }

    // Allow files with no extension.
    $twig_config['allowed_file_extensions'] = ['twig', ''];
    $this->twigConfig = $twig_config;
    // @todo This used to call shutdown() and boot(). rebuildContainer() is
    // needed until we stop pushing the request twice and only popping it once.
    // @see https://www.drupal.org/i/2613044
    $this->container->get('kernel')->rebuildContainer();
    /** @var \Drupal\Core\Template\Loader\FilesystemLoader $loader */
    $loader = \Drupal::service('twig.loader.filesystem');
    $source = $loader->getSourceContext('@__main__\/' . $this->siteDirectory . '/test_file');
    $this->assertSame('This is a test!', $source->getCode());

    // Ensure the error message makes sense when no file extension is allowed.
    try {
      $loader->getSourceContext('@__main__\/core/tests/fixtures/files/sql-2.sql');
      $this->fail('Expected exception not thrown');
    }
    catch (LoaderError $e) {
      $this->assertStringContainsString('Template @__main__\/core/tests/fixtures/files/sql-2.sql has an invalid file extension (sql). Only templates ending in one of twig, or no file extension are allowed. Set the twig.config.allowed_file_extensions container parameter to customize the allowed file extensions', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    if (!empty($this->twigConfig)) {
      $container->setParameter('twig.config', $this->twigConfig);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    // Use a real file system and not VFS so that we can include files from the
    // site using @__main__ in a template.
    $public_file_directory = $this->siteDirectory . '/files';
    $private_file_directory = $this->siteDirectory . '/private';

    mkdir($this->siteDirectory, 0775);
    mkdir($this->siteDirectory . '/files', 0775);
    mkdir($this->siteDirectory . '/private', 0775);
    mkdir($this->siteDirectory . '/files/config/sync', 0775, TRUE);

    $this->setSetting('file_public_path', $public_file_directory);
    $this->setSetting('file_private_path', $private_file_directory);
    $this->setSetting('config_sync_directory', $this->siteDirectory . '/files/config/sync');
  }

}
