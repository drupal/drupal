<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\PluginAwareInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginFormFactory;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\PluginFormFactory
 * @group Plugin
 */
class PluginFormFactoryTest extends UnitTestCase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $classResolver;

  /**
   * The manager being tested.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactory
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->classResolver = $this->prophesize(ClassResolverInterface::class);
    $this->manager = new PluginFormFactory($this->classResolver->reveal());
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstance(): void {
    $plugin_form = $this->prophesize(PluginFormInterface::class);
    $expected = $plugin_form->reveal();

    $this->classResolver->getInstanceFromDefinition(get_class($expected))->willReturn($expected);

    $plugin = $this->prophesize(PluginWithFormsInterface::class);
    $plugin->hasFormClass('standard_class')->willReturn(TRUE);
    $plugin->getFormClass('standard_class')->willReturn(get_class($expected));

    $form_object = $this->manager->createInstance($plugin->reveal(), 'standard_class');
    $this->assertSame($expected, $form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstanceUsingPlugin(): void {
    $this->classResolver->getInstanceFromDefinition(Argument::cetera())->shouldNotBeCalled();

    $plugin = $this->prophesize(PluginWithFormsInterface::class)->willImplement(PluginFormInterface::class);
    $plugin->hasFormClass('configure')->willReturn(TRUE);
    $plugin->getFormClass('configure')->willReturn(get_class($plugin->reveal()));

    $form_object = $this->manager->createInstance($plugin->reveal(), 'configure');
    $this->assertSame($plugin->reveal(), $form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstanceUsingPluginWithSlashes(): void {
    $this->classResolver->getInstanceFromDefinition(Argument::cetera())->shouldNotBeCalled();

    $plugin = $this->prophesize(PluginWithFormsInterface::class)->willImplement(PluginFormInterface::class);
    $plugin->hasFormClass('configure')->willReturn(TRUE);
    $plugin->getFormClass('configure')->willReturn('\\' . get_class($plugin->reveal()));

    $form_object = $this->manager->createInstance($plugin->reveal(), 'configure');
    $this->assertSame($plugin->reveal(), $form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstanceDefaultFallback(): void {
    $this->classResolver->getInstanceFromDefinition(Argument::cetera())->shouldNotBeCalled();

    $plugin = $this->prophesize(PluginWithFormsInterface::class)->willImplement(PluginFormInterface::class);
    $plugin->hasFormClass('missing')->willReturn(FALSE);
    $plugin->hasFormClass('fallback')->willReturn(TRUE);
    $plugin->getFormClass('fallback')->willReturn(get_class($plugin->reveal()));

    $form_object = $this->manager->createInstance($plugin->reveal(), 'missing', 'fallback');
    $this->assertSame($plugin->reveal(), $form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstancePluginAware(): void {
    $plugin_form = $this->prophesize(PluginFormInterface::class)->willImplement(PluginAwareInterface::class);

    $expected = $plugin_form->reveal();

    $this->classResolver->getInstanceFromDefinition(get_class($expected))->willReturn($expected);

    $plugin = $this->prophesize(PluginWithFormsInterface::class);
    $plugin->hasFormClass('operation_aware')->willReturn(TRUE);
    $plugin->getFormClass('operation_aware')->willReturn(get_class($expected));

    $plugin_form->setPlugin($plugin->reveal())->shouldBeCalled();

    $form_object = $this->manager->createInstance($plugin->reveal(), 'operation_aware');
    $this->assertSame($expected, $form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstanceDefinitionException(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "the_plugin_id" plugin did not specify a "anything" form class');

    $plugin = $this->prophesize(PluginWithFormsInterface::class);
    $plugin->getPluginId()->willReturn('the_plugin_id');
    $plugin->hasFormClass('anything')->willReturn(FALSE);

    $form_object = $this->manager->createInstance($plugin->reveal(), 'anything');
    $this->assertNull($form_object);
  }

  /**
   * @covers ::createInstance
   */
  public function testCreateInstanceInvalidException(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "the_plugin_id" plugin did not specify a valid "invalid" form class, must implement \Drupal\Core\Plugin\PluginFormInterface');

    $expected = new \stdClass();
    $this->classResolver->getInstanceFromDefinition(get_class($expected))->willReturn($expected);

    $plugin = $this->prophesize(PluginWithFormsInterface::class);
    $plugin->getPluginId()->willReturn('the_plugin_id');
    $plugin->hasFormClass('invalid')->willReturn(TRUE);
    $plugin->getFormClass('invalid')->willReturn(get_class($expected));

    $form_object = $this->manager->createInstance($plugin->reveal(), 'invalid');
    $this->assertNull($form_object);
  }

}
