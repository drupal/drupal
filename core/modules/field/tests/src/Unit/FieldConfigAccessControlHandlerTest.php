<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigAccessControlHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the field config access controller.
 */
#[CoversClass(FieldConfigAccessControlHandler::class)]
#[Group('field')]
class FieldConfigAccessControlHandlerTest extends FieldStorageConfigAccessControlHandlerTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = new FieldConfig([
      'field_name' => $this->entity->getName(),
      'entity_type' => 'node',
      'fieldStorage' => $this->entity,
      'bundle' => 'test_bundle',
      'field_type' => 'test_field',
    ], 'node');

    $this->accessControlHandler = new FieldConfigAccessControlHandler($this->entity->getEntityType());
    $this->accessControlHandler->setModuleHandler($this->moduleHandler);
  }

  /**
   * Ensures field config access is working properly.
   */
  public function testAccess(): void {
    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->member);
  }

}
