<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\YamlFileLoader;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\YamlFileLoader
 * @group DependencyInjection
 */
class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase {

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
YAML;

    vfsStream::setup('drupal', NULL, [
      'modules/example/example.yml' => $yml,
    ]);

    $builder = new ContainerBuilder();
    $yaml_file_loader = new YamlFileLoader($builder);
    $yaml_file_loader->load('vfs://drupal/modules/example/example.yml');

    $this->assertEquals(['_provider' => [['provider' => 'example']]], $builder->getDefinition('example_service')->getTags());
  }

}
