<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the interaction between entity context and typed data.
 */
#[Group('Context')]
#[RunTestsInSeparateProcesses]
class EntityContextTypedDataTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

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
