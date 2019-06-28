<?php

namespace Drupal\Tests\help_topics\Unit;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\help_topics\HelpTopicDiscovery;
use Drupal\help_topics\HelpTopicTwig;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\help_topics\HelpTopicDiscovery
 * @group help_topics
 */
class HelpTopicDiscoveryTest extends UnitTestCase {

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionProviderMismatch() {
    vfsStream::setup('root');
    vfsStream::create([
      'modules' => [
        'foo' => [
          'help_topics' => [
            // The content of the help topic does not matter.
            'test.topic.html.twig' => '',
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['foo' => vfsStream::url('root/modules/foo/help_topics')]);

    $this->expectException(DiscoveryException::class);
    $this->expectExceptionMessage("vfs://root/modules/foo/help_topics/test.topic.html.twig should begin with 'foo.'");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionMissingLabelMetaTag() {
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
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig does not contain the required meta tag with name='help_topic:label'");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionInvalidMetaTag() {
    vfsStream::setup('root');
    // Note a blank line is required after the last meta tag otherwise the last
    // meta tag is not parsed.
    $topic_content = <<<EOF
<meta name="help_topic:label" content="A label"/>
<meta name="help_topic:foo" content="bar"/>

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
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig contains invalid meta tag with name='foo'");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testDiscoveryExceptionInvalidTopLevel() {
    vfsStream::setup('root');
    // Note a blank line is required after the last meta tag otherwise the last
    // meta tag is not parsed.
    $topic_content = <<<EOF
<meta name="help_topic:label" content="A label"/>
<meta name="help_topic:top_level" content="bar"/>

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
    $this->expectExceptionMessage("vfs://root/modules/test/help_topics/test.topic.html.twig contains invalid meta tag with name='help_topic:top_level', the 'content' property should not exist");
    $discovery->getDefinitions();
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsExtensionProviderSpecialCase() {
    vfsStream::setup('root');
    $topic_content = <<<EOF
<meta name="help_topic:label" content="Test"/>
<h2>Test</h2>
EOF;

    vfsStream::create([
      'modules' => [
        'help_topics' => [
          'help_topics' => [
            'core.topic.html.twig' => $topic_content,
          ],
        ],
      ],
    ]);
    $discovery = new HelpTopicDiscovery(['help_topics' => vfsStream::url('root/modules/help_topics/help_topics')]);
    $this->assertArrayHasKey('core.topic', $discovery->getDefinitions());
  }

  /**
   * @covers ::findAll
   */
  public function testHelpTopicsDefinition() {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    vfsStream::setup('root');
    $topic_content = <<<EOF
<meta name="help_topic:label" content="Test"/>
<meta name="help_topic:top_level"/>
<meta name="help_topic:related" content="one, two ,three"/>
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
    $this->assertSame(TRUE, $definition['top_level']);
    // Each related plugin ID should be trimmed.
    $this->assertSame(['one', 'two', 'three'], $definition['related']);
    $this->assertSame('foo', $definition['provider']);
    $this->assertSame(HelpTopicTwig::class, $definition['class']);
    $this->assertSame(vfsStream::url('root/modules/foo/help_topics/foo.topic.html.twig'), $definition['_discovered_file_path']);
    $this->assertSame('foo.topic', $definition['id']);
  }

}
