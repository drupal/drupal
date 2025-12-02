<?php

declare(strict_types=1);

namespace Drupal\Tests\config_test\Kernel;

use Drupal\config_test\ConfigValidation;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the UriHost validator.
 */
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class ConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('config_test');
  }

  /**
   * This test ensures that validation is not recursed too many times.
   */
  public function testValidationCallCount(): void {
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    ConfigValidation::$calledValidators = [];
    $typed_config = $typed_config_manager->get('config_test.validation');
    $results = $typed_config->validate();

    $this->assertCount(0, $results);
    $called_validators = ConfigValidation::$calledValidators;
    $this->assertArrayHasKey('validateMapping', $called_validators);
    $this->assertArrayHasKey('validateCats', $called_validators);
    $this->assertArrayHasKey('validateCatCount', $called_validators);
    $this->assertSame(1, $called_validators['validateMapping']);
    $this->assertSame(1, $called_validators['validateCats']);
    $this->assertSame(1, $called_validators['validateCatCount']);
  }

}
