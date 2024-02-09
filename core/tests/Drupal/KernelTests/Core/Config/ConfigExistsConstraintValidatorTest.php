<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ConfigExists constraint validator.
 *
 * @group config
 * @group Validation
 *
 * @covers \Drupal\Core\Config\Plugin\Validation\Constraint\ConfigExistsConstraint
 * @covers \Drupal\Core\Config\Plugin\Validation\Constraint\ConfigExistsConstraintValidator
 */
class ConfigExistsConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests the ConfigExists constraint validator.
   */
  public function testValidation(): void {
    // Create a data definition that specifies the value must be a string with
    // the name of an existing piece of config.
    $definition = DataDefinition::create('string')
      ->addConstraint('ConfigExists');

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');
    $data = $typed_data->create($definition, 'system.site');

    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'system.site' config does not exist.", (string) $violations->get(0)->getMessage());

    $this->installConfig('system');
    $this->assertCount(0, $data->validate());

    // NULL should not trigger a validation error: a value may be nullable.
    $data->setValue(NULL);
    $this->assertCount(0, $data->validate());
  }

}
