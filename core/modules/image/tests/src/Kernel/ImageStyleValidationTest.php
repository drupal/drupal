<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of image_style entities.
 */
#[Group('image')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class ImageStyleValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->entity = ImageStyle::create([
      'name' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

}
