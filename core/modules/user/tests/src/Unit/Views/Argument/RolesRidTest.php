<?php

namespace Drupal\Tests\user\Unit\Views\Argument;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
    $role1 = new Role(array(
      'id' => 'test_rid_1',
      'label' => 'test rid 1'
    ), 'user_role');
    $role2 = new Role(array(
      'id' => 'test_rid_2',
      'label' => 'test <strong>rid 2</strong>',
    ), 'user_role');

    // Creates a stub entity storage;
    $role_storage = $this->getMockForAbstractClass('Drupal\Core\Entity\EntityStorageInterface');
    $role_storage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValueMap(array(
        array(array(), array()),
        array(array('test_rid_1'), array('test_rid_1' => $role1)),
        array(array('test_rid_1', 'test_rid_2'), array('test_rid_1' => $role1, 'test_rid_2' => $role2)),
      )));

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getKey')
      ->with('label')
      ->will($this->returnValue('label'));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->equalTo('user_role'))
      ->will($this->returnValue($entity_type));

    $entity_manager
      ->expects($this->once())
      ->method('getStorage')
      ->with($this->equalTo('user_role'))
      ->will($this->returnValue($role_storage));

    // @todo \Drupal\Core\Entity\Entity::entityType() uses a global call to
    //   entity_get_info(), which in turn wraps \Drupal::entityManager(). Set
    //   the entity manager until this is fixed.
    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    \Drupal::setContainer($container);

    $roles_rid_argument = new RolesRid(array(), 'user__roles_rid', array(), $entity_manager);

    $roles_rid_argument->value = array();
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals(array(), $titles);

    $roles_rid_argument->value = array('test_rid_1');
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals(array('test rid 1'), $titles);

    $roles_rid_argument->value = array('test_rid_1', 'test_rid_2');
    $titles = $roles_rid_argument->titleQuery();
    $this->assertEquals(array('test rid 1', 'test <strong>rid 2</strong>'), $titles);
  }

}
