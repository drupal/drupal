<?php

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry;
use Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface;
use Drupal\plugin_test\Plugin\plugin_test\fruit\Kale;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Factory\DefaultFactory
 * @group Plugin
 */
class DefaultFactoryTest extends TestCase {

  /**
   * Tests getPluginClass() with a valid array plugin definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithValidArrayPluginDefinition() {
    $plugin_class = Cherry::class;
    $class = DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class]);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a valid object plugin definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithValidObjectPluginDefinition() {
    $plugin_class = Cherry::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $class = DefaultFactory::getPluginClass('cherry', $plugin_definition);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a missing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithMissingClassWithArrayPluginDefinition() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
      $this->expectExceptionMessage('The plugin (cherry) did not specify an instance class.');
    }
    else {
      $this->setExpectedException(PluginException::class, 'The plugin (cherry) did not specify an instance class.');
    }
    DefaultFactory::getPluginClass('cherry', []);
  }

  /**
   * Tests getPluginClass() with a missing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithMissingClassWithObjectPluginDefinition() {
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
      $this->expectExceptionMessage('The plugin (cherry) did not specify an instance class.');
    }
    else {
      $this->setExpectedException(PluginException::class, 'The plugin (cherry) did not specify an instance class.');
    }
    DefaultFactory::getPluginClass('cherry', $plugin_definition);
  }

  /**
   * Tests getPluginClass() with a not existing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithNotExistingClassWithArrayPluginDefinition() {
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
      $this->expectExceptionMessage('Plugin (kiwifruit) instance class "\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit" does not exist.');
    }
    else {
      $this->setExpectedException(PluginException::class, 'Plugin (kiwifruit) instance class "\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit" does not exist.');
    }
    DefaultFactory::getPluginClass('kiwifruit', ['class' => '\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit']);
  }

  /**
   * Tests getPluginClass() with a not existing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithNotExistingClassWithObjectPluginDefinition() {
    $plugin_class = '\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit';
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
    }
    else {
      $this->setExpectedException(PluginException::class);
    }
    DefaultFactory::getPluginClass('kiwifruit', $plugin_definition);
  }

  /**
   * Tests getPluginClass() with a required interface.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceWithArrayPluginDefinition() {
    $plugin_class = Cherry::class;
    $class = DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class], FruitInterface::class);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a required interface.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceWithObjectPluginDefinition() {
    $plugin_class = Cherry::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $class = DefaultFactory::getPluginClass('cherry', $plugin_definition, FruitInterface::class);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a required interface but no implementation.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceAndInvalidClassWithArrayPluginDefinition() {
    $plugin_class = Kale::class;
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
      $this->expectExceptionMessage('Plugin "cherry" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) must implement interface Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface.');
    }
    else {
      $this->setExpectedException(PluginException::class, 'Plugin "cherry" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) must implement interface Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface.');
    }
    DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class, 'provider' => 'core'], FruitInterface::class);
  }

  /**
   * Tests getPluginClass() with a required interface but no implementation.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceAndInvalidClassWithObjectPluginDefinition() {
    $plugin_class = Kale::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    if (method_exists($this, 'expectException')) {
      $this->expectException(PluginException::class);
    }
    else {
      $this->setExpectedException(PluginException::class);
    }
    DefaultFactory::getPluginClass('cherry', $plugin_definition, FruitInterface::class);
  }

}
