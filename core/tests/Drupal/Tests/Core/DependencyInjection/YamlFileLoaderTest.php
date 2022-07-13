<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\YamlFileLoader;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\YamlFileLoader
 * @group DependencyInjection
 */
class YamlFileLoaderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FileCacheFactory::setPrefix('example');
  }

  public function testParseDefinitionsWithProvider() {
    $yml = <<<YAML
services:
  example_service_1:
    class: \Drupal\Core\ExampleClass
  example_service_2: '@example_service_1'
  example_private_service:
    class: \Drupal\Core\ExampleClass
    public: false
  Drupal\Core\ExampleClass: ~
YAML;

    vfsStream::setup('drupal', NULL, [
      'modules' => [
        'example' => [
          'example.yml' => $yml,
        ],
      ],
    ]);

    $builder = new ContainerBuilder();
    $yaml_file_loader = new YamlFileLoader($builder);
    $yaml_file_loader->load('vfs://drupal/modules/example/example.yml');

    $this->assertEquals(['_provider' => [['provider' => 'example']]], $builder->getDefinition('example_service_1')->getTags());
    $this->assertEquals('example_service_1', $builder->getAlias('example_service_2')->__toString());
    $this->assertTrue($builder->getDefinition('example_service_1')->isPublic());
    $this->assertFalse($builder->getDefinition('example_private_service')->isPublic());
    $builder->compile();
    $this->assertTrue($builder->has('example_service_1'));
    $this->assertFalse($builder->has('example_private_service'));
    $this->assertTrue($builder->has('Drupal\Core\ExampleClass'));
    $this->assertSame('Drupal\Core\ExampleClass', $builder->getDefinition('Drupal\Core\ExampleClass')->getClass());
  }

  /**
   * @dataProvider providerTestExceptions
   */
  public function testExceptions($yml, $message) {
    vfsStream::setup('drupal', NULL, [
      'modules' => [
        'example' => [
          'example.yml' => $yml,
        ],
      ],
    ]);

    $builder = new ContainerBuilder();
    $yaml_file_loader = new YamlFileLoader($builder);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $yaml_file_loader->load('vfs://drupal/modules/example/example.yml');
  }

  public function providerTestExceptions() {
    return [
      '_defaults must be an array' => [<<<YAML
services:
  _defaults: string
YAML,
        'Service "_defaults" key must be an array, "string" given in "vfs://drupal/modules/example/example.yml".',
      ],
      'invalid _defaults key' => [<<<YAML
services:
  _defaults:
    invalid: string
YAML,
        'The configuration key "invalid" cannot be used to define a default value in "vfs://drupal/modules/example/example.yml". Allowed keys are "public", "tags", "autowire".',
      ],
      'default tags must be an array' => [<<<YAML
services:
  _defaults:
    tags: string
YAML,
        'Parameter "tags" in "_defaults" must be an array in "vfs://drupal/modules/example/example.yml". Check your YAML syntax.',
      ],
      'default tags must have a name' => [<<<YAML
services:
  _defaults:
    tags:
      - {}
YAML,
        'A "tags" entry in "_defaults" is missing a "name" key in "vfs://drupal/modules/example/example.yml".',
      ],
      'default tag name must not be empty' => [<<<YAML
services:
  _defaults:
    tags:
      - ''
YAML,
        'The tag name in "_defaults" must be a non-empty string in "vfs://drupal/modules/example/example.yml".',
      ],
      'default tag name must be a string' => [<<<YAML
services:
  _defaults:
    tags:
      - 123
YAML,
        'The tag name in "_defaults" must be a non-empty string in "vfs://drupal/modules/example/example.yml".',
      ],
      'default tag attribute must be scalar' => [<<<YAML
services:
  _defaults:
    tags:
      - { name: tag, value: [] }
YAML,
        'Tag "tag", attribute "value" in "_defaults" must be of a scalar-type in "vfs://drupal/modules/example/example.yml". Check your YAML syntax.',
      ],
      'tags must be an array' => [<<<YAML
services:
  service:
    tags: string
YAML,
        'Parameter "tags" must be an array for service "service" in "vfs://drupal/modules/example/example.yml". Check your YAML syntax.',
      ],
      'tags must have a name' => [<<<YAML
services:
  service:
    tags:
      - {}
YAML,
        'A "tags" entry is missing a "name" key for service "service" in "vfs://drupal/modules/example/example.yml".',
      ],
      'tag name must not be empty' => [<<<YAML
services:
  service:
    tags:
      - ''
YAML,
        'The tag name for service "service" in "vfs://drupal/modules/example/example.yml" must be a non-empty string.',
      ],
      'tag attribute must be scalar' => [<<<YAML
services:
  service:
    tags:
      - { name: tag, value: [] }
YAML,
        'A "tags" attribute must be of a scalar-type for service "service", tag "tag", attribute "value" in "vfs://drupal/modules/example/example.yml". Check your YAML syntax.',
      ],
      'service must be array or @service' => [<<<YAML
services:
  service: string
YAML,
        'A service definition must be an array or a string starting with "@" but string found for service "service" in vfs://drupal/modules/example/example.yml. Check your YAML syntax.',
      ],
    ];
  }

}
