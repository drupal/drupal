<?php
/**
 * @file
 * Contains \Drupal\comment\Tests\Entity\CommentTest
 */

namespace Drupal\comment\Tests\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the comment entity lock behavior.
 *
 * @group Drupal
 * @group Comment
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
    $container->set('module_handler', $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface'));
    $container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $container->register('request', 'Symfony\Component\HttpFoundation\Request');
    $lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $cid = 2;
    $lock_name = "comment:$cid:.00/";
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
    $methods[] = 'onSaveOrDelete';
    $methods[] = 'onUpdateBundleEntity';
    $comment = $this->getMockBuilder('Drupal\comment\Entity\Comment')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
    $comment->expects($this->once())
      ->method('isNew')
      ->will($this->returnValue(TRUE));
    $comment->expects($this->once())
      ->method('hasParentComment')
      ->will($this->returnValue(TRUE));
    $comment->expects($this->once())
      ->method('getParentComment')
      ->will($this->returnValue($comment));
    $comment->expects($this->once())
      ->method('getCommentedEntityId')
      ->will($this->returnValue($cid));
    $comment->expects($this->any())
      ->method('getThread')
      ->will($this->returnValue(''));
    $comment->expects($this->at(0))
      ->method('get')
      ->with('status')
      ->will($this->returnValue((object) array('value' => NULL)));
    $storage_controller = $this->getMock('Drupal\comment\CommentStorageControllerInterface');
    $comment->preSave($storage_controller);
    $comment->postSave($storage_controller);
  }

}
