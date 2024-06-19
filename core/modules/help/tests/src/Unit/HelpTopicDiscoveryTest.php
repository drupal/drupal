<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Unit;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\help\HelpTopicDiscovery;
use Drupal\help\HelpTopicTwig;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\help\HelpTopicDiscovery
 * @group help
 */
class HelpTopicDiscoveryTest extends UnitTestCase {

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionMissingLabel(): void {
    vfsStream::setup('root');

    vfsStream::create([
      'modules' => [
        'test' => [
          'help_topics' => [
            // The content of the help topic does not matter.
            'test.topic.html.twig' => '',
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['test' => vfsStream::url('root/modules/test/help_topics')]);

    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig does not contain the required key with name='label'");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionInvalidYamlKey(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: 'A label'
foo: bar
---
EOF;

    vfsStream::create([
      'modules' => [
        'test' => [
          'help_topics' => [
            'test.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['test' => vfsStream::url('root/modules/test/help_topics')]);

    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig contains invalid key='foo'");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionInvalidTopLevel(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: 'A label'
top_level: bar
---
EOF;

    vfsStream::create([
      'modules' => [
        'test' => [
          'help_topics' => [
            'test.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['test' => vfsStream::url('root/modules/test/help_topics')]);

    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig contains invalid value for 'top_level' key, the value must be a Boolean");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionInvalidRelated(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: 'A label'
related: "one, two"
---
EOF;

    vfsStream::create([
      'modules' => [
        'test' => [
          'help_topics' => [
            'test.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['test' => vfsStream::url('root/modules/test/help_topics')]);

    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig contains invalid value for 'related' key, the value must be an array of strings");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsExtensionProviderSpecialCase(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: Test
---
<h2>Test</h2>
EOF;

    vfsStream::create([
      'modules' => [
        'help' => [
          'help_topics' => [
            'core.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['help' => vfsStream::url('root/modules/help/help_topics')]);
    $this->assertArrayHasKey('core.topic', $discovery->getDefinitions());
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsInCore(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: Test
---
<h2>Test</h2>
EOF;

    vfsStream::create([
      'core' => [
        'help_topics' => [
          'core.topic.html.twig' => $topic_content,
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['core' => vfsStream::url('root/core/help_topics')]);
    $this->assertArrayHasKey('core.topic', $discovery->getDefinitions());
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsBrokenYaml(): void {
    vfsStream::setup('root');
    $topic_content = <<<EOF
---
foo : [bar}
---
<h2>Test</h2>
EOF;

    vfsStream::create([
      'modules' => [
        'help' => [
          'help_topics' => [
            'core.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['help' => vfsStream::url('root/modules/help/help_topics')]);
    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("Malformed YAML in help topic \"vfs://root/modules/help/help_topics/core.topic.html.twig\":");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsDefinition(): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    vfsStream::setup('root');
    $topic_content = <<<EOF
---
label: 'Test'
top_level: true
related:
  - one
  - two
  - three
---
<h2>Test</h2>
EOF;

    vfsStream::create([
      'modules' => [
        'foo' => [
          'help_topics' => [
            'foo.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['foo' => vfsStream::url('root/modules/foo/help_topics')]);
    $definition = $discovery->getDefinitions()['foo.topic'];
    $this->assertEquals('Test', $definition['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $definition['label']);
    $this->assertTrue($definition['top_level']);
    // Each related plugin ID should be trimmed.
    $this->assertSame(['one', 'two', 'three'], $definition['related']);
    $this->assertSame('foo', $definition['provider']);
    $this->assertSame(HelpTopicTwig::class, $definition['class']);
    $this->assertSame(vfsStream::url('root/modules/foo/help_topics/foo.topic.html.twig'), $definition['_discovered_file_path']);
    $this->assertSame('foo.topic', $definition['id']);
  }

}
