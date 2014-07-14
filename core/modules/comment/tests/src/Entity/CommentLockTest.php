<?php
/**
 * @file
 * Contains \Drupal\comment\Tests\Entity\CommentTest
 */

namespace Drupal\comment\Tests\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests comment acquires and releases the right lock.
 *
 * @group comment
 */
class CommentLockTest extends UnitTestCase {

  /**
   * Test the lock behavior.
   */
  public function testLocks() {
    $container = new ContainerBuilder();
    $container->set('module_handler', $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface'));
    $container->set('current_user', $this->getMock('Drupal\Core\Session\AccountInterface'));
    $container->set('cache.test', $this->getMock('Drupal\Core\Cache\CacheBackendInterface'));
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/'));
    $container->set('request_stack', $request_stack);
    $container->setParameter('cache_bins', array('cache.test' => 'test'));
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

    $entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $comment->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));
    $comment->expects($this->at(1))
      ->method('get')
      ->with('status')
      ->will($this->returnValue((object) array('value' => NULL)));
    $comment->expects($this->once())
      ->method('getCacheTag')
      ->will($this->returnValue(array('comment' => array($cid))));
    $comment->expects($this->once())
      ->method('getListCacheTags')
      ->will($this->returnValue(array('comments' => TRUE)));
    $storage = $this->getMock('Drupal\comment\CommentStorageInterface');
    $comment->preSave($storage);
    $comment->postSave($storage);
  }

}
