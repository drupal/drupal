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
  protected function setUp() {
    parent::setUp();

    FileCacheFactory::setPrefix('example');
  }

  public function testParseDefinitionsWithProvider() {
    $yml = <<<YAML
services:
  example_service:
    class: \Drupal\Core\ExampleClass
  example_private_service:
    class: \Drupal\Core\ExampleClass
    public: false
YAML;

    vfsStream::setup('drupal', NULL, [
      'modules/example/example.yml' => $yml,
    ]);

    $builder = new ContainerBuilder();
    $yaml_file_loader = new YamlFileLoader($builder);
    $yaml_file_loader->load('vfs://drupal/modules/example/example.yml');

    $this->assertEquals(['_provider' => [['provider' => 'example']]], $builder->getDefinition('example_service')->getTags());
    $this->assertTrue($builder->getDefinition('example_service')->isPublic());
    $this->assertFalse($builder->getDefinition('example_private_service')->isPublic());
    $builder->compile();
    $this->assertTrue($builder->has('example_service'));
    $this->assertFalse($builder->has('example_private_service'));
  }

}
