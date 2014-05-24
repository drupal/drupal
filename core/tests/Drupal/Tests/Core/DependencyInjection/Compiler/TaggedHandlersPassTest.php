<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\Compiler\TaggedHandlersPassTest.
 */

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the tagged handler compiler pass.
 *
 * @group Drupal
 * @group DependencyInjection
 *
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass
 */
class TaggedHandlersPassTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass',
      'description' => '',
      'group' => 'Dependency injection',
    );
  }

  protected function buildContainer($environment = 'dev') {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.environment', $environment);
    return $container;
  }

  /**
   * Tests without any consumers.
   *
   * @covers ::process
   */
  public function testProcessNoConsumers() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer');

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $this->assertCount(1, $container->getDefinitions());
    $this->assertFalse($container->getDefinition('consumer_id')->hasMethodCall('addHandler'));
  }

  /**
   * Tests consumer with missing interface in non-production environment.
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
   * @expectedExceptionMessage Service consumer 'consumer_id' class method Drupal\Tests\Core\DependencyInjection\Compiler\InvalidConsumer::addHandler() has to type-hint an interface.
   * @covers ::process
   */
  public function testProcessMissingInterface() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\InvalidConsumer')
      ->addTag('service_collector');

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);
  }

  /**
   * Tests one consumer and two handlers.
   *
   * @covers ::process
   */
  public function testProcess() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector');

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id');
    $container
      ->register('handler2', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id');

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(2, $method_calls);
  }

  /**
   * Tests handler priority sorting.
   *
   * @covers ::process
   */
  public function testProcessPriority() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector');

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id');
    $container
      ->register('handler2', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'priority' => 10,
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(2, $method_calls);
    $this->assertEquals(new Reference('handler2'), $method_calls[0][1][0]);
    $this->assertEquals(10, $method_calls[0][1][1]);
    $this->assertEquals(new Reference('handler1'), $method_calls[1][1][0]);
    $this->assertEquals(0, $method_calls[1][1][1]);
  }

  /**
   * Tests consumer method without priority parameter.
   *
   * @covers ::process
   */
  public function testProcessNoPriorityParam() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector', array(
        'call' => 'addNoPriority',
      ));

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id');
    $container
      ->register('handler2', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'priority' => 10,
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(2, $method_calls);
    $this->assertEquals(new Reference('handler2'), $method_calls[0][1][0]);
    $this->assertCount(1, $method_calls[0][1]);
    $this->assertEquals(new Reference('handler1'), $method_calls[1][1][0]);
    $this->assertCount(1, $method_calls[0][1]);
  }

  /**
   * Tests interface validation in non-production environment.
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
   * @covers ::process
   */
  public function testProcessInterfaceMismatch() {
    $container = $this->buildContainer();

    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector');
    $container
      ->register('handler1', __NAMESPACE__ . '\InvalidHandler')
      ->addTag('consumer_id');
    $container
      ->register('handler2', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'priority' => 10,
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);
  }

}

interface HandlerInterface {
}
class ValidConsumer {
  public function addHandler(HandlerInterface $instance, $priority = 0) {
  }
  public function addNoPriority(HandlerInterface $instance) {
  }
}
class InvalidConsumer {
  public function addHandler($instance, $priority = 0) {
  }
}
class ValidHandler implements HandlerInterface {
}
class InvalidHandler {
}

