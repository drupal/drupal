<?php

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\AuthenticationProviderPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\AuthenticationProviderPass
 * @group DependencyInjection
 */
class AuthenticationProviderPassTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::process
   */
  public function testEncoders() {
    $container = new ContainerBuilder();
    $container->setDefinition('serializer', new Definition(Serializer::class, [[], []]));

    $definition = new Definition('TestClass');
    $definition->addTag('authentication_provider', ['provider_id' => 'bunny_auth']);
    $definition->addTag('_provider', ['provider' => 'test_provider_a']);
    $container->setDefinition('test_provider_a.authentication.bunny_auth', $definition);

    $definition = new Definition('TestClass');
    $definition->addTag('authentication_provider', ['provider_id' => 'llama_auth', 'priority' => 100]);
    $definition->addTag('_provider', ['provider' => 'test_provider_a']);
    $container->setDefinition('test_provider_a.authentication.llama_auth', $definition);

    $definition = new Definition('TestClass');
    $definition->addTag('authentication_provider', ['provider_id' => 'camel_auth', 'priority' => -100]);
    $definition->addTag('_provider', ['provider' => 'test_provider_b']);
    $container->setDefinition('test_provider_b.authentication.camel_auth', $definition);

    $compiler_pass = new AuthenticationProviderPass();
    $compiler_pass->process($container);

    $this->assertEquals(['bunny_auth' => 'test_provider_a', 'llama_auth' => 'test_provider_a', 'camel_auth' => 'test_provider_b'], $container->getParameter('authentication_providers'));
  }

}
