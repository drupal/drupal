<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Views\Argument;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\Role;
use Drupal\user\Plugin\views\argument\RolesRid;

/**
 * @coversDefaultClass \Drupal\user\Plugin\views\argument\RolesRid
 * @group user
 */
class RolesRidTest extends UnitTestCase {

  /**
   * Tests the titleQuery method.
   *
   * @covers ::titleQuery
   */
  public function testTitleQuery() {
    $role1 = new Role([
      'id' => 'test_rid_1',
      'label' => 'test rid 1',
    ], 'user_role');
    $role2 = new Role([
      'id' => 'test_rid_2',
      'label' => 'test <strong>rid 2</strong>',
    ], 'user_role');

    // Creates a stub entity storage;
    $role_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $role_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturnMap([
        [[], []],
        [['test_rid_1'], ['test_rid_1' => $role1]],
        [
          ['test_rid_1', 'test_rid_2'],
          ['test_rid_1' => $role1, 'test_rid_2' => $role2],
        ],
      ]);

    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getKey')
      ->with('label')
      ->willReturn('label');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->equalTo('user_role'))
      ->willReturn($entity_type);

    $entity_type_manager
      ->expects($this->once())
      ->method('getStorage')
      ->with($this->equalTo('user_role'))
      ->willReturn($role_storage);

    // Set up a minimal container to satisfy Drupal\Core\Entity\EntityBase's
    // dependency on it.
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    $roles_rid_argument = new RolesRid([], 'user__roles_rid', [], $entity_type_manager);

    $roles_rid_argument->value = [];
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals([], $titles);

    $roles_rid_argument->value = ['test_rid_1'];
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals(['test rid 1'], $titles);

    $roles_rid_argument->value = ['test_rid_1', 'test_rid_2'];
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals(['test rid 1', 'test <strong>rid 2</strong>'], $titles);
  }

}
