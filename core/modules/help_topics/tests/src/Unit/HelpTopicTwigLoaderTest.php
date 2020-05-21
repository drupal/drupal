<?php

namespace Drupal\Tests\help_topics\Unit;

use Drupal\help_topics\HelpTopicTwigLoader;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Twig\Error\LoaderError;

/**
 * Unit test for the HelpTopicTwigLoader class.
 *
 * @coversDefaultClass \Drupal\help_topics\HelpTopicTwigLoader
 * @group help_topics
 */
class HelpTopicTwigLoaderTest extends UnitTestCase {

  /**
   * The help topic loader instance to test.
   *
   * @var \Drupal\help_topics\HelpTopicTwigLoader
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
  protected function setUp() {
    $this->setUpVfs();
    $this->helpLoader = new HelpTopicTwigLoader('\fake\root\path',
      $this->getHandlerMock('module'),
      $this->getHandlerMock('theme')
    );
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

    $source = $this->helpLoader->getSourceContext('@' . HelpTopicTwigLoader::MAIN_NAMESPACE . '/test.invalid_yaml.html.twig');
  }

  /**
   * Creates a mock module or theme handler class for the test.
   *
   * @param string $type
   *   Type of handler to return: 'module' or 'theme'.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mock of module or theme handler.
   */
  protected function getHandlerMock($type) {
    if ($type == 'module') {
      $class = 'Drupal\Core\Extension\ModuleHandlerInterface';
      $method = 'getModuleDirectories';
    }
    else {
      $class = 'Drupal\Core\Extension\ThemeHandlerInterface';
      $method = 'getThemeDirectories';
    }

    $handler = $this
      ->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->getMock();

    $handler
      ->method($method)
      ->willReturn($this->directories[$type]);

    return $handler;
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
