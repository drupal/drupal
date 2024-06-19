<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Tests\Component\Plugin\Fixtures\vegetable\Broccoli;
use Drupal\Tests\Component\Plugin\Fixtures\vegetable\Corn;
use Drupal\Tests\Component\Plugin\Fixtures\vegetable\VegetableInterface;
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
  public function testGetPluginClassWithValidArrayPluginDefinition(): void {
    $plugin_class = Corn::class;
    $class = DefaultFactory::getPluginClass('corn', ['class' => $plugin_class]);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a valid object plugin definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithValidObjectPluginDefinition(): void {
    $plugin_class = Corn::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $class = DefaultFactory::getPluginClass('corn', $plugin_definition);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a missing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithMissingClassWithArrayPluginDefinition(): void {
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('The plugin (corn) did not specify an instance class.');
    DefaultFactory::getPluginClass('corn', []);
  }

  /**
   * Tests getPluginClass() with a missing class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithMissingClassWithObjectPluginDefinition(): void {
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)
      ->getMock();
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('The plugin (corn) did not specify an instance class.');
    DefaultFactory::getPluginClass('corn', $plugin_definition);
  }

  /**
   * Tests getPluginClass() with a non-existent class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithNotExistingClassWithArrayPluginDefinition(): void {
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('Plugin (carrot) instance class "Drupal\Tests\Component\Plugin\Fixtures\vegetable\Carrot" does not exist.');
    DefaultFactory::getPluginClass('carrot', ['class' => 'Drupal\Tests\Component\Plugin\Fixtures\vegetable\Carrot']);
  }

  /**
   * Tests getPluginClass() with a non-existent class definition.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithNotExistingClassWithObjectPluginDefinition(): void {
    $plugin_class = 'Drupal\Tests\Component\Plugin\Fixtures\vegetable\Carrot';
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $this->expectException(PluginException::class);
    DefaultFactory::getPluginClass('carrot', $plugin_definition);
  }

  /**
   * Tests getPluginClass() with a required interface.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceWithArrayPluginDefinition(): void {
    $plugin_class = Corn::class;
    $class = DefaultFactory::getPluginClass('corn', ['class' => $plugin_class], VegetableInterface::class);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a required interface.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceWithObjectPluginDefinition(): void {
    $plugin_class = Corn::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $class = DefaultFactory::getPluginClass('corn', $plugin_definition, VegetableInterface::class);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a required interface but no implementation.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceAndInvalidClassWithArrayPluginDefinition(): void {
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('Plugin "corn" (Drupal\Tests\Component\Plugin\Fixtures\vegetable\Broccoli) must implement interface Drupal\Tests\Component\Plugin\Fixtures\vegetable\VegetableInterface.');
    DefaultFactory::getPluginClass('corn', ['class' => Broccoli::class], VegetableInterface::class);
  }

  /**
   * Tests getPluginClass() with a required interface but no implementation.
   *
   * @covers ::getPluginClass
   */
  public function testGetPluginClassWithInterfaceAndInvalidClassWithObjectPluginDefinition(): void {
    $plugin_class = Broccoli::class;
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)->getMock();
    $plugin_definition->expects($this->atLeastOnce())
      ->method('getClass')
      ->willReturn($plugin_class);
    $this->expectException(PluginException::class);
    DefaultFactory::getPluginClass('corn', $plugin_definition, VegetableInterface::class);
  }

}
