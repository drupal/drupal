<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Form\ConfigFormBaseTrait.
 */
#[CoversClass(ConfigFormBaseTrait::class)]
#[Group('Form')]
class ConfigFormBaseTraitTest extends UnitTestCase {

  /**
   * Tests config.
   */
  public function testConfig(): void {

    $trait = $this->createPartialMock(ConfiguredTrait::class, ['getEditableConfigNames']);
    // Set up some configuration in a mocked config factory.
    $trait->configFactory = $this->getConfigFactoryStub([
      'editable.config' => [],
      'immutable.config' => [],
    ]);

    $trait->expects($this->any())
      ->method('getEditableConfigNames')
      ->willReturn(['editable.config']);

    $config_method = new \ReflectionMethod($trait, 'config');

    // Ensure that configuration that is expected to be mutable is.
    $result = $config_method->invoke($trait, 'editable.config');
    $this->assertInstanceOf('\Drupal\Core\Config\Config', $result);
    $this->assertNotInstanceOf('\Drupal\Core\Config\ImmutableConfig', $result);

    // Ensure that configuration that is expected to be immutable is.
    $result = $config_method->invoke($trait, 'immutable.config');
    $this->assertInstanceOf('\Drupal\Core\Config\ImmutableConfig', $result);
  }

  /**
   * Tests config factory exception.
   */
  public function testConfigFactoryException(): void {
    $testObject = new ConfiguredTrait();

    // There is no config factory available this should result in an exception.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No config factory available for ConfigFormBaseTrait');
    $config_method = new \ReflectionMethod($testObject, 'config');
    $config_method->invoke($testObject, 'editable.config');
  }

  /**
   * Tests config factory exception invalid property.
   */
  public function testConfigFactoryExceptionInvalidProperty(): void {
    $testObject = new ConfiguredTrait();

    // There is no config factory available this should result in an exception.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No config factory available for ConfigFormBaseTrait');
    $config_method = new \ReflectionMethod($testObject, 'config');
    $config_method->invoke($testObject, 'editable.config');
  }

}

/**
 * Test class for testing ConfigFormBaseTrait.
 */
class ConfiguredTrait {
  use ConfigFormBaseTrait;

  /**
   * The configuration factory.
   *
   * @var null
   */
  public $configFactory;

  protected function getEditableConfigNames() {}

}
