<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the ExtensionName constraint.
 *
 * @group Validation
 *
 * @covers \Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionNameConstraint
 */
class ExtensionNameConstraintTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests the ExtensionName constraint.
   */
  public function testValidation(): void {
    // Create a data definition that specifies the value must be a string with
    // the name of a valid extension.
    $definition = DataDefinition::create('string')
      ->addConstraint('ExtensionName');

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');
    $data = $typed_data->create($definition, 'user');

    $this->assertCount(0, $data->validate());

    $data->setValue('invalid-name');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('This value is not a valid extension name.', (string) $violations->get(0)->getMessage());
  }

}
