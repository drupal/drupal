<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of filter_format entities.
 */
#[Group('filter')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class FilterFormatValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = FilterFormat::create([
      'format' => 'test',
      'name' => 'Test',
    ]);
    $this->entity->save();
  }

}
