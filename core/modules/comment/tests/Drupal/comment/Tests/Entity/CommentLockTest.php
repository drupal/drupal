<?php
/**
 * @file
 * Contains \Drupal\comment\Tests\Entity\CommentTest
 */

namespace Drupal\comment\Tests\Entity {

use Drupal\comment\Entity\Comment;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the comment entity lock behavior.
 *
 * @group Drupal
 */
class CommentLockTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Comment locks',
      'description' => 'Test comment acquires and releases the right lock.',
      'group' => 'Comment',
    );
  }

  /**
   * Test the lock behavior.
   */
  public function testLocks() {
    $container = new ContainerBuilder();
    $container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $container->register('request', 'Symfony\Component\HttpFoundation\Request');
    $lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $cid = 2;
    $lock_name = "comment:$cid:01.00/";
    $lock->expects($this->at(0))
      ->method('acquire')
      ->with($lock_name, 30)
      ->will($this->returnValue(TRUE));
    $lock->expects($this->at(1))
      ->method('release')
      ->with($lock_name);
    $lock->expects($this->exactly(2))
      ->method($this->anything());
    $container->set('lock', $lock);
    \Drupal::setContainer($container);
    $methods = get_class_methods('Drupal\comment\Entity\Comment');
    unset($methods[array_search('preSave', $methods)]);
    unset($methods[array_search('postSave', $methods)]);
    $comment = $this->getMockBuilder('Drupal\comment\Entity\Comment')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    $comment->expects($this->once())
      ->method('isNew')
      ->will($this->returnValue(TRUE));
    foreach (array('status', 'pid', 'created', 'changed', 'entity_id', 'uid', 'thread', 'hostname') as $property) {
      $comment->$property = new \stdClass();
    }
    $comment->status->value = 1;
    $comment->entity_id->value = $cid;
    $comment->uid->target_id = 3;
    // Parent comment is the first in thread.
    $comment->pid->target_id = 42;
    $comment->pid->entity = new \stdClass();
    $comment->pid->entity->thread = (object) array('value' => '01/');
    $storage_controller = $this->getMock('Drupal\comment\CommentStorageControllerInterface');
    $comment->preSave($storage_controller);
    $comment->postSave($storage_controller);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

}
}
namespace {
if (!function_exists('module_invoke_all')) {
  function module_invoke_all() {}
}
}

