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
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass
 * @group DependencyInjection
 */
class TaggedHandlersPassTest extends UnitTestCase {

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
   * Tests a required consumer with no handlers.
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
   * @expectedExceptionMessage At least one service tagged with 'consumer_id' is required.
   * @covers ::process
   */
  public function testProcessRequiredHandlers() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector', array(
        'required' => TRUE,
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);
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
   * Tests consumer method with an ID parameter.
   *
   * @covers ::process
   */
  public function testProcessWithIdParameter() {
    $container = $this->buildContainer();
    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumer')
      ->addTag('service_collector', array(
        'call' => 'addWithId',
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
    $this->assertEquals('handler2', $method_calls[0][1][1]);
    $this->assertEquals(10, $method_calls[0][1][2]);
    $this->assertEquals(new Reference('handler1'), $method_calls[1][1][0]);
    $this->assertEquals('handler1', $method_calls[1][1][1]);
    $this->assertEquals(0, $method_calls[1][1][2]);
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

  /**
   * Tests consumer method with extra parameters.
   *
   * @covers ::process
   */
  public function testProcessWithExtraArguments() {
    $container = $this->buildContainer();

    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumerWithExtraArguments')
      ->addTag('service_collector');

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
          'extra1' => 'extra1',
          'extra2' => 'extra2',
        ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(4, $method_calls[0][1]);
    $this->assertEquals(new Reference('handler1'), $method_calls[0][1][0]);
    $this->assertEquals(0, $method_calls[0][1][1]);
    $this->assertEquals('extra1', $method_calls[0][1][2]);
    $this->assertEquals('extra2', $method_calls[0][1][3]);
  }

  /**
   * Tests consumer method with extra parameters and no priority.
   *
   * @covers ::process
   */
  public function testProcessNoPriorityAndExtraArguments() {
    $container = $this->buildContainer();

    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumerWithExtraArguments')
      ->addTag('service_collector', array(
        'call' => 'addNoPriority'
      ));

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'extra' => 'extra',
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(2, $method_calls[0][1]);
    $this->assertEquals(new Reference('handler1'), $method_calls[0][1][0]);
    $this->assertEquals('extra', $method_calls[0][1][1]);
  }

  /**
   * Tests consumer method with priority, id and extra parameters.
   *
   * @covers ::process
   */
  public function testProcessWithIdAndExtraArguments() {
    $container = $this->buildContainer();

    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumerWithExtraArguments')
      ->addTag('service_collector', array(
        'call' => 'addWithId'
      ));

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'extra1' => 'extra1',
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(5, $method_calls[0][1]);
    $this->assertEquals(new Reference('handler1'), $method_calls[0][1][0]);
    $this->assertEquals('handler1', $method_calls[0][1][1]);
    $this->assertEquals(0, $method_calls[0][1][2]);
    $this->assertEquals('extra1', $method_calls[0][1][3]);
    $this->assertNull($method_calls[0][1][4]);
  }

  /**
   * Tests consumer method with priority and extra parameters in different order.
   *
   * @covers ::process
   */
  public function testProcessWithDifferentArgumentsOrderAndDefaultValue() {
    $container = $this->buildContainer();

    $container
      ->register('consumer_id', __NAMESPACE__ . '\ValidConsumerWithExtraArguments')
      ->addTag('service_collector', array(
        'call' => 'addWithDifferentOrder'
      ));

    $container
      ->register('handler1', __NAMESPACE__ . '\ValidHandler')
      ->addTag('consumer_id', array(
        'priority' => 0,
        'extra1' => 'extra1',
        'extra3' => 'extra3'
      ));

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $method_calls = $container->getDefinition('consumer_id')->getMethodCalls();
    $this->assertCount(5, $method_calls[0][1]);
    $expected = [new Reference('handler1'), 'extra1', 0, 'default2', 'extra3'];
    $this->assertEquals($expected, array_values($method_calls[0][1]));
  }

}

interface HandlerInterface {
}
class ValidConsumer {
  public function addHandler(HandlerInterface $instance, $priority = 0) {
  }
  public function addNoPriority(HandlerInterface $instance) {
  }
  public function addWithId(HandlerInterface $instance, $id, $priority = 0) {
  }
}
class InvalidConsumer {
  public function addHandler($instance, $priority = 0) {
  }
}
class ValidConsumerWithExtraArguments {
  public function addHandler(HandlerInterface $instance, $priority = 0, $extra1 = '', $extra2 = '') {
  }
  public function addNoPriority(HandlerInterface $instance, $extra) {
  }
  public function addWithId(HandlerInterface $instance, $id, $priority = 0, $extra1 = '', $extra2 = NULL) {
  }
  public function addWithDifferentOrder(HandlerInterface $instance, $extra1, $priority = 0, $extra2 = 'default2', $extra3 = 'default3') {
  }
}
class ValidHandler implements HandlerInterface {
}
class InvalidHandler {
}
