<?php

namespace Drupal\Tests\tour\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\tour\Entity\Tour;

/**
 * Tests validation of tour entities.
 *
 * @group tour
 */
class TourValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tour'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Tour::create([
      'id' => 'test',
      'label' => 'Test',
      'module' => 'system',
    ]);
    $this->entity->save();
  }

}
