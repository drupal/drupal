<?php

namespace Drupal\Tests\serialization\Unit\CompilerPass;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\serialization\RegisterSerializationClassesCompilerPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\RegisterSerializationClassesCompilerPass
 * @group serialization
 */
class RegisterSerializationClassesCompilerPassTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::process
   */
  public function testEncoders() {
    $container = new ContainerBuilder();
    $container->setDefinition('serializer', new Definition(Serializer::class, [[], []]));

    $definition = new Definition('TestClass');
    $definition->addTag('encoder', ['format' => 'xml']);
    $definition->addTag('_provider', ['provider' => 'test_provider_a']);
    $container->setDefinition('encoder_1', $definition);

    $definition = new Definition('TestClass');
    $definition->addTag('encoder', ['format' => 'json']);
    $definition->addTag('_provider', ['provider' => 'test_provider_a']);
    $container->setDefinition('encoder_2', $definition);

    $definition = new Definition('TestClass');
    $definition->addTag('encoder', ['format' => 'hal_json']);
    $definition->addTag('_provider', ['provider' => 'test_provider_b']);
    $container->setDefinition('encoder_3', $definition);

    $compiler_pass = new RegisterSerializationClassesCompilerPass();
    $compiler_pass->process($container);

    $this->assertEquals(['xml', 'json', 'hal_json'], $container->getParameter('serializer.formats'));
    $this->assertEquals(['xml' => 'test_provider_a', 'json' => 'test_provider_a', 'hal_json' => 'test_provider_b'], $container->getParameter('serializer.format_providers'));
  }

}
