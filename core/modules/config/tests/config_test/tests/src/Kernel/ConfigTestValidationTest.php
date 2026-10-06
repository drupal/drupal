<?php

declare(strict_types=1);

namespace Drupal\Tests\config_test\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of config_test entities.
 */
#[Group('config_test')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class ConfigTestValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->installConfig('config_test');

    $this->entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => 'test',
      'label' => 'test',
    ]);
    $this->entity->save();
  }

}
