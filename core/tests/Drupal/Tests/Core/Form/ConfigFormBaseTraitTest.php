<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\ConfigFormBaseTrait
 * @group Form
 */
class ConfigFormBaseTraitTest extends UnitTestCase {

  /**
   * @covers ::config
   */
  public function testConfig() {

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
   * @covers ::config
   */
  public function testConfigFactoryException() {
    $testObject = new ConfiguredTrait();

    // There is no config factory available this should result in an exception.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No config factory available for ConfigFormBaseTrait');
    $config_method = new \ReflectionMethod($testObject, 'config');
    $config_method->invoke($testObject, 'editable.config');
  }

  /**
   * @covers ::config
   */
  public function testConfigFactoryExceptionInvalidProperty() {
    $testObject = new ConfiguredTrait();

    // There is no config factory available this should result in an exception.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No config factory available for ConfigFormBaseTrait');
    $config_method = new \ReflectionMethod($testObject, 'config');
    $config_method->invoke($testObject, 'editable.config');
  }

}

class ConfiguredTrait {
  use ConfigFormBaseTrait;
  public $configFactory;

  protected function getEditableConfigNames() {}

}
