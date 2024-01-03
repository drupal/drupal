<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\help\HelpTopicTwigLoader;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Twig\Error\LoaderError;

/**
 * Unit test for the HelpTopicTwigLoader class.
 *
 * @coversDefaultClass \Drupal\help\HelpTopicTwigLoader
 * @group help
 */
class HelpTopicTwigLoaderTest extends UnitTestCase {

  /**
   * The help topic loader instance to test.
   *
   * @var \Drupal\help\HelpTopicTwigLoader
   */
  protected $helpLoader;

  /**
   * The virtual directories to use in testing.
   *
   * @var array
   */
  protected $directories;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpVfs();

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler
      ->method('getModuleDirectories')
      ->willReturn($this->directories['module']);

    /** @var \Drupal\Core\Extension\ThemeHandlerInterface|\Prophecy\Prophecy\ObjectProphecy $module_handler */
    $theme_handler = $this->createMock(ThemeHandlerInterface::class);
    $theme_handler
      ->method('getThemeDirectories')
      ->willReturn($this->directories['theme']);

    $this->helpLoader = new HelpTopicTwigLoader('\fake\root\path', $module_handler, $theme_handler);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructor() {
    // Verify that the module/theme directories were added in the constructor,
    // and non-existent directories were omitted.
    $paths = $this->helpLoader->getPaths(HelpTopicTwigLoader::MAIN_NAMESPACE);
    $this->assertCount(2, $paths);
    $this->assertContains($this->directories['module']['test'] . '/help_topics', $paths);
    $this->assertContains($this->directories['theme']['test'] . '/help_topics', $paths);
  }

  /**
   * @covers ::getSourceContext
   */
  public function testGetSourceContext() {
    $source = $this->helpLoader->getSourceContext('@' . HelpTopicTwigLoader::MAIN_NAMESPACE . '/test.topic.html.twig');
    $this->assertEquals('{% line 4 %}<h2>Test</h2>', $source->getCode());
  }

  /**
   * @covers ::getSourceContext
   */
  public function testGetSourceContextException() {
    $this->expectException(LoaderError::class);
    $this->expectExceptionMessage("Malformed YAML in help topic \"vfs://root/modules/test/help_topics/test.invalid_yaml.html.twig\":");

    $this->helpLoader->getSourceContext('@' . HelpTopicTwigLoader::MAIN_NAMESPACE . '/test.invalid_yaml.html.twig');
  }

  /**
   * Sets up the virtual file system.
   */
  protected function setUpVfs() {
    $content = <<<EOF
---
label: Test
---
<h2>Test</h2>
EOF;
    $invalid_content = <<<EOF
---
foo : [bar}
---
<h2>Test</h2>
EOF;
    $help_topics_dir = [
      'help_topics' => [
        'test.topic.html.twig' => $content,
        'test.invalid_yaml.html.twig' => $invalid_content,
      ],
    ];

    vfsStream::setup('root');
    vfsStream::create([
      'modules' => [
        'test' => $help_topics_dir,
      ],
      'themes' => [
        'test' => $help_topics_dir,
      ],
    ]);

    $this->directories = [
      'root' => vfsStream::url('root'),
      'module' => [
        'test' => vfsStream::url('root/modules/test'),
        'not_a_dir' => vfsStream::url('root/modules/not_a_dir'),
      ],
      'theme' => [
        'test' => vfsStream::url('root/themes/test'),
        'not_a_dir' => vfsStream::url('root/themes/not_a_dir'),
      ],
    ];
  }

}
