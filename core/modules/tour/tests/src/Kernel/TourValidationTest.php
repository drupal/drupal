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

  /**
   * Tour IDs are atypical in that they allow dashes in the machine name.
   */
  public function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();
    // Remove the existing test case that verifies a machine name containing
    // periods is invalid.
    $this->assertSame(['dash-separated', FALSE], $cases['INVALID: dash separated']);
    unset($cases['INVALID: dash separated']);
    // And instead add a test case that verifies it is allowed for tours.
    $cases['VALID: dash separated'] = ['dash-separated', TRUE];
    return $cases;
  }

}
