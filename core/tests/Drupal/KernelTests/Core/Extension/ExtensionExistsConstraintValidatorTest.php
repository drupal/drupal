<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ExtensionExists constraint validator.
 *
 * @group Validation
 *
 * @covers \Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionExistsConstraint
 * @covers \Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionExistsConstraintValidator
 */
class ExtensionExistsConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests the ExtensionExists constraint validator.
   */
  public function testValidation(): void {
    // Create a data definition that specifies the value must be a string with
    // the name of an installed module.
    $definition = DataDefinition::create('string')
      ->addConstraint('ExtensionExists', 'module');

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');

    // `core` provides many plugins without the need to install a module.
    $data = $typed_data->create($definition, 'core');
    $violations = $data->validate();
    $this->assertCount(0, $violations);

    $data->setValue('user');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("Module 'user' is not installed.", (string) $violations->get(0)->getMessage());

    $this->enableModules(['user']);
    $this->assertCount(0, $data->validate());

    // NULL should not trigger a validation error: a value may be nullable.
    $data->setValue(NULL);
    $this->assertCount(0, $data->validate());

    $definition->setConstraints(['ExtensionExists' => 'theme']);
    $data = $typed_data->create($definition, 'stark');

    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("Theme 'stark' is not installed.", (string) $violations->get(0)->getMessage());

    $this->assertTrue($this->container->get('theme_installer')->install(['stark']));
    // Installing the theme rebuilds the container, so we need to ensure the
    // constraint is instantiated with an up-to-date theme handler.
    $data = $this->container->get('kernel')
      ->getContainer()
      ->get('typed_data_manager')
      ->create($definition, 'stark');
    $this->assertCount(0, $data->validate());

    // `core` provides many plugins without the need to install a module, but it
    // does not work for themes.
    $data = $typed_data->create($definition, 'core');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("Theme 'core' is not installed.", (string) $violations->get(0)->getMessage());

    // NULL should not trigger a validation error: a value may be nullable.
    $data->setValue(NULL);
    $this->assertCount(0, $data->validate());

    // Anything but a module or theme should raise an exception.
    $definition->setConstraints(['ExtensionExists' => 'profile']);
    $this->expectExceptionMessage("Unknown extension type: 'profile'");
    $data->validate();
  }

}
