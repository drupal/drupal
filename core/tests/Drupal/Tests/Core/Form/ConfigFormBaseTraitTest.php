<?php

namespace Drupal\Tests\Core\Form;

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

    $trait = $this->getMockForTrait('Drupal\Core\Form\ConfigFormBaseTrait');
    // Set up some configuration in a mocked config factory.
    $trait->configFactory = $this->getConfigFactoryStub([
      'editable.config' => [],
      'immutable.config' => []
    ]);

    $trait->expects($this->any())
      ->method('getEditableConfigNames')
      ->willReturn(['editable.config']);

    $config_method = new \ReflectionMethod($trait, 'config');
    $config_method->setAccessible(TRUE);

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
   * @expectedException \LogicException
   * @expectedExceptionMessage No config factory available for ConfigFormBaseTrait
   */
  public function testConfigFactoryException() {
    $trait = $this->getMockForTrait('Drupal\Core\Form\ConfigFormBaseTrait');
    $config_method = new \ReflectionMethod($trait, 'config');
    $config_method->setAccessible(TRUE);

    // There is no config factory available this should result in an exception.
    $config_method->invoke($trait, 'editable.config');
  }

  /**
   * @covers ::config
   * @expectedException \LogicException
   * @expectedExceptionMessage No config factory available for ConfigFormBaseTrait
   */
  public function testConfigFactoryExceptionInvalidProperty() {
    $trait = $this->getMockForTrait('Drupal\Core\Form\ConfigFormBaseTrait');
    $trait->configFactory = TRUE;
    $config_method = new \ReflectionMethod($trait, 'config');
    $config_method->setAccessible(TRUE);

    // There is no config factory available this should result in an exception.
    $config_method->invoke($trait, 'editable.config');
  }

}
