<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the interaction between entity context and typed data.
 *
 * @group Context
 */
class EntityContextTypedDataTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Tests that entity contexts wrapping a config entity can be validated.
   */
  public function testValidateConfigEntityContext(): void {
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->save();

    $violations = EntityContext::fromEntity($display)->validate();
    $this->assertCount(0, $violations);
  }

}
