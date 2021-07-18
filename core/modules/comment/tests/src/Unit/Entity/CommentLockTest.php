<?php

namespace Drupal\Tests\comment\Unit\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * Tests the lock behavior.
   */
  public function testLocks() {
    $container = new ContainerBuilder();
    $container->set('module_handler', $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface'));
    $container->set('current_user', $this->createMock('Drupal\Core\Session\AccountInterface'));
    $container->set('cache.test', $this->createMock('Drupal\Core\Cache\CacheBackendInterface'));
    $container->set('comment.statistics', $this->createMock('Drupal\comment\CommentStatisticsInterface'));
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/'));
    $container->set('request_stack', $request_stack);
    $container->setParameter('cache_bins', ['cache.test' => 'test']);
    $lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $cid = 2;
    $lock_name = "comment:$cid:.00/";
    $lock->expects($this->once())
      ->method('acquire')
      ->with($lock_name, 30)
      ->will($this->returnValue(TRUE));
    $lock->expects($this->once())
      ->method('release')
      ->with($lock_name);
    $lock->expects($this->exactly(2))
      ->method($this->anything());
    $container->set('lock', $lock);

    $cache_tag_invalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidator');
    $container->set('cache_tags.invalidator', $cache_tag_invalidator);

    \Drupal::setContainer($container);
    $methods = get_class_methods('Drupal\comment\Entity\Comment');
    unset($methods[array_search('preSave', $methods)]);
    unset($methods[array_search('postSave', $methods)]);
    $methods[] = 'invalidateTagsOnSave';
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

    $anon_user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $anon_user->expects($this->any())
      ->method('isAnonymous')
      ->will($this->returnValue(TRUE));
    $comment->expects($this->any())
      ->method('getOwner')
      ->will($this->returnValue($anon_user));

    $parent_entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $parent_entity->expects($this->atLeastOnce())
      ->method('getCacheTagsToInvalidate')
      ->willReturn(['node:1']);
    $comment->expects($this->once())
      ->method('getCommentedEntity')
      ->willReturn($parent_entity);

    $entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $comment->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));
    $storage = $this->createMock('Drupal\comment\CommentStorageInterface');

    // preSave() should acquire the lock. (This is what's really being tested.)
    $comment->preSave($storage);
    // Release the acquired lock before exiting the test.
    $comment->postSave($storage);
  }

}
