<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests nested composite validation constraints.
 */
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class NestedCompositeConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * Tests use of AtLeastOneOf validation constraint in config.
   */
  public function testConfigValidation(): void {
    $this->installConfig('config_test');

    $config = \Drupal::configFactory()->getEditable('config_test.validation');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');

    $config->set('composite.nested', 'green');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(0, $result);

    $config->set('composite.nested', 'green is a bit longer than 20');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(0, $result);

    $config->set('composite.nested', '');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('This value should not be blank.', $result->get(0)->getMessage());
    $this->assertEquals('composite.nested', $result->get(0)->getPropertyPath());

    $config->set('composite.nested', '12345678901');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('This value should satisfy at least one of the following constraints: [1] This value is too long. It should have 10 characters or less. [2] This value is too short. It should have 20 characters or more.', $result->get(0)->getMessage());
    $this->assertEquals('composite.nested', $result->get(0)->getPropertyPath());
  }

}
