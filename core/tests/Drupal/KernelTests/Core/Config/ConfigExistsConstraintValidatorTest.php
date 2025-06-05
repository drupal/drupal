<?php

declare(strict_types=1);

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
   *
   * @testWith [{}, "system.site", "system.site"]
   *           [{"prefix": "system."}, "site", "system.site"]
   *           [{"prefix": "system.[%parent.reference]."}, "admin", "system.menu.admin"]
   */
  public function testValidation(array $constraint_options, string $value, string $expected_config_name): void {
    // Create a data definition that specifies the value must be a string with
    // the name of an existing piece of config.
    $definition = DataDefinition::create('string')
      ->addConstraint('ConfigExists', $constraint_options);

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');

    // Create a data definition for the parent data.
    $parent_data_definition = $typed_data->createDataDefinition('map');
    $parent_data = $typed_data->create($parent_data_definition, ['reference' => 'menu']);
    $data = $typed_data->create($definition, $value, 'data_name', $parent_data);

    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The '$expected_config_name' config does not exist.", (string) $violations->get(0)->getMessage());

    $this->installConfig('system');
    $this->assertCount(0, $data->validate());

    // NULL should not trigger a validation error: a value may be nullable.
    $data->setValue(NULL);
    $this->assertCount(0, $data->validate());
  }

}
